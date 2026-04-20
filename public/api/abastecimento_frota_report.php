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

  // inclui o dia inteiro do "até"
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
          WHERE a.data_hora >= :de
            AND a.data_hora <  DATE_ADD(:ate, INTERVAL 1 DAY)
          ORDER BY a.data_hora DESC, a.id DESC";

  $st = $pdo->prepare($sql);
  $st->execute([
    ":de" => $de . " 00:00:00",
    ":ate" => $ate . " 00:00:00",
  ]);

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(["ok" => true, "data" => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao gerar relatório"], JSON_UNESCAPED_UNICODE);
}