<?php
require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$data = $_GET["data"] ?? "";
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Data inválida."]);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT saldo_inicial, observacao FROM fin_caixa_diario WHERE data = ?");
  $stmt->execute([$data]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $saldoInicial = $row ? (float)$row["saldo_inicial"] : 0.0;
  $obs = $row["observacao"] ?? null;

  $r = $pdo->prepare("
    SELECT COALESCE(SUM(valor),0) AS total
    FROM fin_lancamentos
    WHERE tipo='receber' AND status='quitado' AND data_quitacao = ?
  ");
  $r->execute([$data]);
  $recebido = (float)($r->fetch(PDO::FETCH_ASSOC)["total"] ?? 0);

  $p = $pdo->prepare("
    SELECT COALESCE(SUM(valor),0) AS total
    FROM fin_lancamentos
    WHERE tipo='pagar' AND status='quitado' AND data_quitacao = ?
  ");
  $p->execute([$data]);
  $pago = (float)($p->fetch(PDO::FETCH_ASSOC)["total"] ?? 0);

  $saldoFinal = $saldoInicial + $recebido - $pago;

  echo json_encode([
    "ok" => true,
    "data" => $data,
    "saldo_inicial" => $saldoInicial,
    "recebido_dia" => $recebido,
    "pago_dia" => $pago,
    "saldo_final" => $saldoFinal,
    "observacao" => $obs
  ]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}