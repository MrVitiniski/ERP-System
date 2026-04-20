<?php
require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$in = json_decode(file_get_contents("php://input"), true);
$data = (string)($in["data"] ?? "");
$saldoInicial = (float)($in["saldo_inicial"] ?? 0);
$obs = trim((string)($in["observacao"] ?? ""));

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Data inválida."]);
  exit;
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

try {
  $stmt = $pdo->prepare("
    INSERT INTO fin_caixa_diario (data, saldo_inicial, observacao, criado_por)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE saldo_inicial = VALUES(saldo_inicial), observacao = VALUES(observacao)
  ");
  $stmt->execute([$data, $saldoInicial, ($obs === "" ? null : $obs), $userName]);

  echo json_encode(["ok" => true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}