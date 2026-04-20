<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$u = current_user();
if (!$u) {
  http_response_code(401);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(["ok" => false, "error" => "Não autenticado"], JSON_UNESCAPED_UNICODE);
  exit;
}

header("Content-Type: application/json; charset=utf-8");

$limit = (int)($_GET["limit"] ?? 120);
if ($limit <= 0 || $limit > 200) $limit = 120;

try {
  $pdo = db();

  $sql = "SELECT id,
                 data_hora,
                 litros,
                 fornecedor,
                 nf,
                 motorista,
                 placa_caminhao,
                 obs,
                 usuario
          FROM diesel_recebimentos
          ORDER BY data_hora DESC, id DESC
          LIMIT {$limit}";
  $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao listar recebimentos", "debug" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}