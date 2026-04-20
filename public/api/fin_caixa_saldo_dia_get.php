<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$pdo = db();

$dia = (string)($_GET["dia"] ?? "");
$conta = trim((string)($_GET["conta"] ?? "principal"));

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dia)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Dia inválido (YYYY-MM-DD)."]);
  exit;
}
if ($conta === "") $conta = "principal";

$stmt = $pdo->prepare("
  SELECT dia, conta, saldo_bancario, informado_por, informado_em
  FROM fin_caixa_saldos_dia
  WHERE dia=? AND conta=?
  LIMIT 1
");
$stmt->execute([$dia, $conta]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(["ok"=>true, "item"=>$row]);