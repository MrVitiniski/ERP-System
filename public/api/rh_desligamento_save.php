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
$tipo = (string)($data['tipo'] ?? '');
$dataDeslig = (string)($data['data_desligamento'] ?? '');
$motivo = trim((string)($data['motivo'] ?? ''));
$obs = $data['observacoes'] ?? null;

if (!$funcionarioId || !preg_match('/^\d+$/', (string)$funcionarioId)) respond(422, ['ok' => false, 'error' => 'funcionario_id inválido']);
if (!in_array($tipo, ['demissao','justa_causa','sem_justa_causa'], true)) respond(422, ['ok' => false, 'error' => 'tipo inválido']);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDeslig)) respond(422, ['ok' => false, 'error' => 'data_desligamento inválida']);
if ($motivo === '' || mb_strlen($motivo) > 255) respond(422, ['ok' => false, 'error' => 'motivo inválido']);

$dbHost = 'localhost';
$dbName = 'sistema';
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

try {
  $pdo->beginTransaction();

  // valida funcionário existe
  $stmt = $pdo->prepare("SELECT id, status FROM funcionarios WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => (int)$funcionarioId]);
  $f = $stmt->fetch();
  if (!$f) {
    $pdo->rollBack();
    respond(404, ['ok' => false, 'error' => 'Funcionário não encontrado']);
  }

  // insere desligamento
  $stmt = $pdo->prepare("
    INSERT INTO desligamentos (funcionario_id, tipo, data_desligamento, motivo, observacoes)
    VALUES (:fid, :tipo, :dd, :motivo, :obs)
  ");
  $stmt->execute([
    ':fid' => (int)$funcionarioId,
    ':tipo' => $tipo,
    ':dd' => $dataDeslig,
    ':motivo' => $motivo,
    ':obs' => $obs,
  ]);

  $newId = (int)$pdo->lastInsertId();

  // inativa funcionário (regra pedida)
  $stmt = $pdo->prepare("UPDATE funcionarios SET status = 'inativo' WHERE id = :id");
  $stmt->execute([':id' => (int)$funcionarioId]);

  $pdo->commit();
  respond(200, ['ok' => true, 'id' => $newId, 'funcionario_status' => 'inativo']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  respond(500, ['ok' => false, 'error' => 'Erro ao salvar desligamento']);
}