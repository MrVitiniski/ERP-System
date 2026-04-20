<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "rh"]);

try {
  $pdo = db();

  $stmt = $pdo->query("
    SELECT
      id,
      nome_completo,
      cpf,
      cargo,
      setor,
      data_admissao,
      status,
      created_at
    FROM funcionarios
    ORDER BY id DESC
    LIMIT 500
  ");

  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok" => true, "items" => $items], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
  exit;
}