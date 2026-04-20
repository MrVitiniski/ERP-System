<?php
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/fin_caixa_adapter.php";

header("Content-Type: application/json; charset=utf-8");

$data = $_GET["data"] ?? "";
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Data inválida."]);
  exit;
}

try {
  $saldo = fin_get_saldo_dia($pdo, $data);
  echo json_encode(["ok" => true, "saldo" => $saldo]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}