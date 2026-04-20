<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function addMonths(DateTimeImmutable $d, int $months): DateTimeImmutable {
  return $d->modify(($months >= 0 ? '+' : '') . $months . ' months');
}
function addDays(DateTimeImmutable $d, int $days): DateTimeImmutable {
  return $d->modify(($days >= 0 ? '+' : '') . $days . ' days');
}
function iso(DateTimeImmutable $d): string { return $d->format('Y-m-d'); }

// Próxima segunda-feira (ou hoje se já for segunda)
function nextMonday(DateTimeImmutable $d): DateTimeImmutable {
  $dow = (int)$d->format('N'); // 1=Mon ... 7=Sun
  $add = ($dow === 1) ? 0 : (8 - $dow);
  return addDays($d, $add);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'JSON inválido']);

$funcionarioId = $data['funcionario_id'] ?? null;
if (!$funcionarioId || !preg_match('/^\d+$/', (string)$funcionarioId)) {
  respond(422, ['ok' => false, 'error' => 'funcionario_id inválido']);
}

$dbHost = 'localhost';
$dbName = 'sistema'; // ajuste se necessário
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

try {
  $pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
  );
} catch (Throwable $e) {
  respond(500, ['ok' => false, 'error' => 'Falha ao conectar no banco']);
}

$stmt = $pdo->prepare("SELECT id, nome_completo, cpf, data_admissao, status FROM funcionarios WHERE id = :id LIMIT 1");
$stmt->execute([':id' => (int)$funcionarioId]);
$f = $stmt->fetch();
if (!$f) respond(404, ['ok' => false, 'error' => 'Funcionário não encontrado']);

$admissao = DateTimeImmutable::createFromFormat('Y-m-d', $f['data_admissao']);
if (!$admissao) respond(500, ['ok' => false, 'error' => 'Data de admissão inválida no cadastro']);

$today = new DateTimeImmutable('today');

// Aquisitivo “atual” (MVP): último período aquisitivo ainda não registrado
// Estratégia: contar quantos períodos completos de 12 meses se passaram desde admissão,
// e escolher o período mais recente completo OU o em andamento.
$monthsSince = ((int)$today->format('Y') - (int)$admissao->format('Y')) * 12 + ((int)$today->format('n') - (int)$admissao->format('n'));
$periodIndex = max(0, intdiv($monthsSince, 12)); // 0,1,2...

$aquisInicio = addMonths($admissao, $periodIndex * 12);
$aquisFim = addDays(addMonths($aquisInicio, 12), -1);
$podeAPartir = addMonths($aquisInicio, 12);

// Vencida: hoje > (aquisFim + 11 meses) e não existe registro para esse aquisitivo
$concessivoFim = addMonths($aquisFim, 11);

$stmt = $pdo->prepare("
  SELECT COUNT(*) AS c
  FROM ferias
  WHERE funcionario_id = :fid
    AND aquisitivo_inicio = :ai
    AND aquisitivo_fim = :af
    AND status <> 'cancelado'
");
$stmt->execute([
  ':fid' => (int)$funcionarioId,
  ':ai' => iso($aquisInicio),
  ':af' => iso($aquisFim),
]);
$jaTem = ((int)($stmt->fetch()['c'] ?? 0)) > 0;

$feriasVencida = (!$jaTem && $today > $concessivoFim);

// Sugestão de início:
// - se já pode tirar: próxima segunda
// - se não pode ainda: no dia que completa 12 meses
$inicioSug = ($today >= $podeAPartir) ? nextMonday($today) : $podeAPartir;

// Dias padrão 30 (MVP)
$dias = 30;
$fimSug = addDays($inicioSug, $dias - 1);
$retornoSug = addDays($fimSug, 1);

respond(200, [
  'ok' => true,
  'funcionario' => [
    'id' => (int)$f['id'],
    'nome_completo' => $f['nome_completo'],
    'cpf' => $f['cpf'],
    'data_admissao' => $f['data_admissao'],
    'status' => $f['status'],
  ],
  'calculo' => [
    'aquisitivo_inicio' => iso($aquisInicio),
    'aquisitivo_fim' => iso($aquisFim),
    'pode_a_partir' => iso($podeAPartir),
    'concessivo_fim' => iso($concessivoFim),
    'ferias_vencida' => $feriasVencida,
    'ja_registrada_para_periodo' => $jaTem,
    'inicio_sugerido' => iso($inicioSug),
    'fim_sugerido' => iso($fimSug),
    'retorno_sugerido' => iso($retornoSug),
    'dias_sugerido' => $dias,
  ],
]);