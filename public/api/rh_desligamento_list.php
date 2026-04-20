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
$tipo = trim((string)($_GET['tipo'] ?? ''));
$limit = (int)($_GET['limit'] ?? 50);
if ($limit <= 0 || $limit > 200) $limit = 50;

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

$where = [];
$params = [];

if ($q !== '') {
  $digits = preg_replace('/\D+/', '', $q);
  if ($digits !== '') {
    $where[] = "REPLACE(REPLACE(f.cpf,'.',''),'-','') LIKE :cpf";
    $params[':cpf'] = $digits . '%';
  } else {
    $where[] = "f.nome_completo LIKE :nome";
    $params[':nome'] = '%' . $q . '%';
  }
}

if ($tipo !== '') {
  if (!in_array($tipo, ['demissao','justa_causa','sem_justa_causa'], true)) {
    respond(422, ['ok' => false, 'error' => 'tipo inválido']);
  }
  $where[] = "d.tipo = :tipo";
  $params[':tipo'] = $tipo;
}

$sql = "
  SELECT
    d.id,
    d.funcionario_id,
    d.tipo,
    d.data_desligamento,
    d.motivo,
    d.observacoes,
    COALESCE(d.status, 'registrado') AS status,
    d.created_at,
    f.nome_completo,
    f.cpf,
    f.data_admissao,
    f.status AS status_funcionario
  FROM desligamentos d
  JOIN funcionarios f ON f.id = d.funcionario_id
";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);

$sql .= " ORDER BY d.data_desligamento DESC, d.id DESC LIMIT {$limit}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

respond(200, ['ok' => true, 'items' => $items]);