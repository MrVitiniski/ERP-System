<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_role(["admin", "rh", "sst", "almoxarifado"]);

$pdo = db();

$id = (int)($_GET["id"] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  json_out(["ok" => false, "error" => "id inválido"]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT id, nome_completo, cpf, cargo, setor, data_admissao, status, created_at
  FROM funcionarios
  WHERE id = ?
  LIMIT 1
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
  http_response_code(404);
  json_out(["ok" => false, "error" => "funcionario_not_found"]);
  exit;
}

json_out(["ok" => true, "item" => $item]);