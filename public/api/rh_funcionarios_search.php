<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q) < 2) {
  respond(200, ['ok' => true, 'items' => []]);
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

$digits = preg_replace('/\D+/', '', $q);

if ($digits !== '' && strlen($digits) >= 3) {
  // busca por CPF (parcial)
  $stmt = $pdo->prepare("
    SELECT id, nome_completo, cpf, data_admissao, status
    FROM funcionarios
    WHERE REPLACE(REPLACE(cpf,'.',''),'-','') LIKE :cpf
    ORDER BY nome_completo ASC
    LIMIT 10
  ");
  $stmt->execute([':cpf' => $digits . '%']);
} else {
  // busca por nome (parcial)
  $stmt = $pdo->prepare("
    SELECT id, nome_completo, cpf, data_admissao, status
    FROM funcionarios
    WHERE nome_completo LIKE :nome
    ORDER BY nome_completo ASC
    LIMIT 10
  ");
  $stmt->execute([':nome' => '%' . $q . '%']);
}

$items = $stmt->fetchAll();
respond(200, ['ok' => true, 'items' => $items]);