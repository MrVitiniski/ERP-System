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

$limit = (int)($_GET["limit"] ?? 50);
if ($limit <= 0 || $limit > 200) $limit = 50;

try {
  $pdo = db();

  $sql = "SELECT a.id,
                 a.frentista,
                 a.data_hora,
                 a.operador,
                 a.horimetro,
                 a.litros,
                 f.id AS frota_id,
                 f.placa,
                 f.modelo
          FROM frota_abastecimentos a
          JOIN oficina_frota f ON f.id = a.frota_id
          ORDER BY a.data_hora DESC, a.id DESC
          LIMIT {$limit}";
  $st = $pdo->query($sql);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(["ok" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "ok" => false,
    "error" => "Erro ao listar abastecimentos",
    "debug" => $e->getMessage(),   // <- deixe assim só enquanto testa
  ], JSON_UNESCAPED_UNICODE);
}