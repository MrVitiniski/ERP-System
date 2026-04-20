<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "rh", "sst"]);

try {
  $pdo = db();
  $q = trim((string)($_GET["q"] ?? ""));

  if ($q !== "") {
    $stmt = $pdo->prepare("
      SELECT id, nome_completo, cpf, cargo, setor, data_admissao, status, created_at
      FROM funcionarios
      WHERE nome_completo LIKE ? OR cpf LIKE ? OR cargo LIKE ? OR setor LIKE ?
      ORDER BY nome_completo ASC
      LIMIT 500
    ");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like, $like]);
  } else {
    $stmt = $pdo->query("
      SELECT id, nome_completo, cpf, cargo, setor, data_admissao, status, created_at
      FROM funcionarios
      ORDER BY nome_completo ASC
      LIMIT 500
    ");
  }

  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(["ok" => true, "items" => $items], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
  exit;
}