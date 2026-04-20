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

$de = trim((string)($_GET["de"] ?? ""));
$ate = trim((string)($_GET["ate"] ?? ""));

if (!$de || !$ate) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Parâmetros 'de' e 'ate' são obrigatórios"], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $sql = "SELECT COUNT(*) AS qtd,
                 COALESCE(SUM(a.litros), 0) AS total_litros
          FROM frota_abastecimentos a
          WHERE a.data_hora >= :de
            AND a.data_hora <  DATE_ADD(:ate, INTERVAL 1 DAY)";

  $st = $pdo->prepare($sql);
  $st->execute([
    ":de" => $de . " 00:00:00",
    ":ate" => $ate . " 00:00:00",
  ]);

  $row = $st->fetch(PDO::FETCH_ASSOC) ?: ["qtd" => 0, "total_litros" => 0];
  echo json_encode(["ok" => true, "data" => $row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao gerar resumo"], JSON_UNESCAPED_UNICODE);
}