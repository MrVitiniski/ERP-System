<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();

// Se quiser travar para ALMOX/admin, descomente:
// require_role(["admin", "almoxarifado"]);

try {
  $pdo = db();

  $sql = "
    SELECT
      id, colaborador, setor, epi, qtd, data_pedido, obs, status, created_at
    FROM epi_pedidos
    WHERE status = 'PENDENTE_ALMOX'
    ORDER BY id DESC
  ";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok" => true, "data" => $rows]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro no servidor."]);
  exit;
}