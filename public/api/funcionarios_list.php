<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "sst", "rh"]);

try {
  $pdo = db();
  $q = trim((string)($_GET["q"] ?? ""));

  if ($q !== "") {
    $stmt = $pdo->prepare("SELECT id, nome, setor, status FROM funcionarios WHERE nome LIKE ? ORDER BY nome ASC LIMIT 200");
    $stmt->execute(["%$q%"]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    $rows = $pdo->query("SELECT id, nome, setor, status FROM funcionarios ORDER BY nome ASC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
  }

  echo json_encode(["ok" => true, "data" => $rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro no servidor."]);
}