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

$desligamentoId = $data['desligamento_id'] ?? null;
if (!$desligamentoId || !preg_match('/^\d+$/', (string)$desligamentoId)) {
  respond(422, ['ok' => false, 'error' => 'desligamento_id inválido']);
}

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

  // pega o registro
  $stmt = $pdo->prepare("SELECT id, funcionario_id, COALESCE(status,'registrado') AS status FROM desligamentos WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => (int)$desligamentoId]);
  $d = $stmt->fetch();

  if (!$d) {
    $pdo->rollBack();
    respond(404, ['ok' => false, 'error' => 'Desligamento não encontrado']);
  }

  if (($d['status'] ?? '') === 'cancelado') {
    $pdo->rollBack();
    respond(409, ['ok' => false, 'error' => 'Este desligamento já está cancelado']);
  }

  // cancela desligamento
  $stmt = $pdo->prepare("UPDATE desligamentos SET status = 'cancelado' WHERE id = :id");
  $stmt->execute([':id' => (int)$desligamentoId]);

  // reativa funcionário
  $stmt = $pdo->prepare("UPDATE funcionarios SET status = 'ativo' WHERE id = :fid");
  $stmt->execute([':fid' => (int)$d['funcionario_id']]);

  $pdo->commit();

  respond(200, ['ok' => true, 'desligamento_id' => (int)$desligamentoId, 'funcionario_status' => 'ativo']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  respond(500, ['ok' => false, 'error' => 'Erro ao cancelar desligamento']);
}