<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$pdo = db();

$conta = "SCAVARE MINERAÇÃO";

$limit = (int)($_GET["limit"] ?? 60);
if ($limit <= 0) $limit = 60;
if ($limit > 365) $limit = 365;

$stmt = $pdo->prepare("
  SELECT dia, conta, saldo_bancario, informado_por, informado_em
  FROM fin_caixa_saldos_dia
  WHERE conta = ?
  ORDER BY dia DESC
  LIMIT $limit
");
$stmt->execute([$conta]);

echo json_encode(["ok" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);