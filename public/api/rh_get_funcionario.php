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

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
  respond(400, ['ok' => false, 'error' => 'JSON inválido']);
}

/**
 * ====== AJUSTE AQUI CONFORME SEU BANCO ======
 * Se você já tem um arquivo de conexão pronto, substitua esta parte
 * por um require_once dele e pegue o $pdo.
 */
$dbHost = 'localhost';
$dbName = 'sistema';     // <- ajuste
$dbUser = 'root';        // <- ajuste
$dbPass = '';            // <- ajuste
$dbCharset = 'utf8mb4';

// Tabela/colunas (ajuste se os seus nomes forem diferentes)
$table = 'funcionarios';          // <- ajuste
$colId = 'id';                    // <- ajuste
$colNome = 'nome_completo';       // <- ajuste
$colCpf = 'cpf';                  // <- ajuste
$colAdmissao = 'data_admissao';   // <- ajuste
// ==========================================

try {
  $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
  $pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (Throwable $e) {
  respond(500, ['ok' => false, 'error' => 'Falha ao conectar no banco']);
}

// Entrada: ou { "id": 123 } ou { "cpf": "05674008914" }
$id = $data['id'] ?? null;
$cpf = $data['cpf'] ?? null;

if ($id === null && $cpf === null && isset($data['funcionario_ref'])) {
  // fallback (caso você mande "funcionario_ref" do front)
  $ref = trim((string)$data['funcionario_ref']);
  $digits = preg_replace('/\D+/', '', $ref);
  if ($digits !== '' && strlen($digits) === 11) $cpf = $digits;
  else $id = $ref;
}

if ($cpf !== null) {
  $cpf = preg_replace('/\D+/', '', (string)$cpf);
  if (strlen($cpf) !== 11) {
    respond(422, ['ok' => false, 'error' => 'CPF inválido']);
  }

  $sql = "SELECT {$colId} AS id, {$colNome} AS nome_completo, {$colCpf} AS cpf, {$colAdmissao} AS data_admissao
          FROM {$table}
          WHERE REPLACE(REPLACE(REPLACE({$colCpf}, '.', ''), '-', ''), ' ', '') = :cpf
          LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':cpf' => $cpf]);
  $row = $stmt->fetch();

  if (!$row) {
    respond(404, ['ok' => false, 'error' => 'Funcionário não encontrado']);
  }

  // normaliza cpf para somente dígitos
  $row['cpf'] = preg_replace('/\D+/', '', (string)($row['cpf'] ?? ''));
  respond(200, ['ok' => true, 'item' => $row]);
}

if ($id !== null) {
  $idStr = trim((string)$id);
  if ($idStr === '' || !preg_match('/^\d+$/', $idStr)) {
    respond(422, ['ok' => false, 'error' => 'ID inválido']);
  }

  $sql = "SELECT {$colId} AS id, {$colNome} AS nome_completo, {$colCpf} AS cpf, {$colAdmissao} AS data_admissao
          FROM {$table}
          WHERE {$colId} = :id
          LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => (int)$idStr]);
  $row = $stmt->fetch();

  if (!$row) {
    respond(404, ['ok' => false, 'error' => 'Funcionário não encontrado']);
  }

  $row['cpf'] = preg_replace('/\D+/', '', (string)($row['cpf'] ?? ''));
  respond(200, ['ok' => true, 'item' => $row]);
}

respond(422, ['ok' => false, 'error' => 'Informe id ou cpf']);