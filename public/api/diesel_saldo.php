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

try {
  $pdo = db();

  $inRow = $pdo->query("SELECT COALESCE(SUM(litros),0) AS entradas FROM diesel_recebimentos")
               ->fetch(PDO::FETCH_ASSOC);
  $outRow = $pdo->query("SELECT COALESCE(SUM(litros),0) AS saidas FROM frota_abastecimentos")
                ->fetch(PDO::FETCH_ASSOC);

  $entradas = (float)($inRow["entradas"] ?? 0);
  $saidas = (float)($outRow["saidas"] ?? 0);
  $saldo = $entradas - $saidas;

  $threshold = 2000.0;
  $low = ($saldo <= $threshold);

  echo json_encode([
    "ok" => true,
    "data" => [
      "entradas" => $entradas,
      "saidas" => $saidas,
      "saldo" => $saldo,
      "threshold" => $threshold,
      "low" => $low
    ]
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao calcular saldo"], JSON_UNESCAPED_UNICODE);
}