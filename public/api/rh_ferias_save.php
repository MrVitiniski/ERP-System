<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'JSON inválido']);

$funcionarioId = $data['funcionario_id'] ?? null;
$aquisIni = $data['aquisitivo_inicio'] ?? null;
$aquisFim = $data['aquisitivo_fim'] ?? null;
$gozoIni = $data['gozo_inicio'] ?? null;
$dias = (int)($data['dias'] ?? 30);
$obs = $data['observacoes'] ?? null;

if (!$funcionarioId || !preg_match('/^\d+$/', (string)$funcionarioId)) respond(422, ['ok' => false, 'error' => 'funcionario_id inválido']);
foreach (['aquisitivo_inicio' => $aquisIni, 'aquisitivo_fim' => $aquisFim, 'gozo_inicio' => $gozoIni] as $k => $v) {
  if (!is_string($v) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) respond(422, ['ok' => false, 'error' => "$k inválido"]);
}
if ($dias <= 0 || $dias > 60) respond(422, ['ok' => false, 'error' => 'dias inválido']);

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

$gozoIniDt = DateTimeImmutable::createFromFormat('Y-m-d', $gozoIni);
$fimDt = $gozoIniDt->modify('+' . ($dias - 1) . ' days');
$retornoDt = $fimDt->modify('+1 day');

try {
  // evita duplicar o mesmo aquisitivo
  $stmt = $pdo->prepare("
    SELECT id FROM ferias
    WHERE funcionario_id = :fid AND aquisitivo_inicio = :ai AND aquisitivo_fim = :af AND status <> 'cancelado'
    LIMIT 1
  ");
  $stmt->execute([':fid' => (int)$funcionarioId, ':ai' => $aquisIni, ':af' => $aquisFim]);
  $exists = $stmt->fetch();

  if ($exists) {
    respond(409, ['ok' => false, 'error' => 'Já existe férias registrada para este período aquisitivo.']);
  }

  $stmt = $pdo->prepare("
    INSERT INTO ferias (funcionario_id, aquisitivo_inicio, aquisitivo_fim, gozo_inicio, gozo_fim, retorno, dias, status, observacoes)
    VALUES (:fid, :ai, :af, :gi, :gf, :rt, :dias, 'concedido', :obs)
  ");

  $stmt->execute([
    ':fid' => (int)$funcionarioId,
    ':ai' => $aquisIni,
    ':af' => $aquisFim,
    ':gi' => $gozoIni,
    ':gf' => $fimDt->format('Y-m-d'),
    ':rt' => $retornoDt->format('Y-m-d'),
    ':dias' => $dias,
    ':obs' => $obs,
  ]);

  respond(200, ['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
  respond(500, ['ok' => false, 'error' => 'Erro ao salvar férias']);
}