<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "sst", "almoxarifado"]);

try {
  $pdo = db();

  $funcionarioId = (int)($_GET["funcionario_id"] ?? 0);
  if ($funcionarioId <= 0) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "funcionario_id inválido"], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $stmt = $pdo->prepare("
    SELECT id, pedido_id, epi, qtd, entregue_em, obs
    FROM epi_entregas
    WHERE funcionario_id = ?
    ORDER BY entregue_em DESC, id DESC
    LIMIT 500
  ");
  $stmt->execute([$funcionarioId]);

  echo json_encode(["ok" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro no servidor."], JSON_UNESCAPED_UNICODE);
  exit;
}