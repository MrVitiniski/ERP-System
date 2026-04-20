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

$top = (int)($_GET["top"] ?? 5);
if ($top <= 0 || $top > 12) $top = 5;

try {
  $pdo = db();

  // 1) quais equipamentos vamos mostrar? (top N por litros na semana)
  $sqlTop = "SELECT f.id, f.placa, f.modelo, COALESCE(SUM(a.litros),0) AS litros
             FROM frota_abastecimentos a
             JOIN oficina_frota f ON f.id = a.frota_id
             WHERE a.data_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               AND a.data_hora <  DATE_ADD(CURDATE(), INTERVAL 1 DAY)
             GROUP BY f.id, f.placa, f.modelo
             ORDER BY litros DESC
             LIMIT {$top}";
  $topRows = $pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

  $equipIds = array_map(fn($r) => (int)$r["id"], $topRows);
  if (count($equipIds) === 0) {
    echo json_encode(["ok" => true, "data" => ["days" => [], "series" => []]], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // 2) litros por dia por equipamento (somente top N)
  $in = implode(",", array_fill(0, count($equipIds), "?"));

  $sql = "SELECT DATE(a.data_hora) AS dia,
                 f.id AS frota_id,
                 f.placa,
                 f.modelo,
                 COALESCE(SUM(a.litros), 0) AS litros
          FROM frota_abastecimentos a
          JOIN oficina_frota f ON f.id = a.frota_id
          WHERE a.data_hora >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            AND a.data_hora <  DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND f.id IN ($in)
          GROUP BY DATE(a.data_hora), f.id, f.placa, f.modelo
          ORDER BY dia ASC, f.placa ASC";

  $st = $pdo->prepare($sql);
  $st->execute($equipIds);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // 3) montar os 7 dias fixos
  $days = [];
  for ($i = 6; $i >= 0; $i--) {
    $days[] = (new DateTimeImmutable("today"))->sub(new DateInterval("P{$i}D"))->format("Y-m-d");
  }

  // map dia->equip->litros
  $map = [];
  foreach ($rows as $r) {
    $d = $r["dia"];
    $eid = (int)$r["frota_id"];
    $map[$d][$eid] = (float)$r["litros"];
  }

  // series (uma por equipamento)
  $series = [];
  foreach ($topRows as $e) {
    $eid = (int)$e["id"];
    $label = trim($e["placa"] . " - " . $e["modelo"]);
    $data = [];
    foreach ($days as $d) {
      $data[] = $map[$d][$eid] ?? 0;
    }
    $series[] = [
      "frota_id" => $eid,
      "label" => $label,
      "data" => $data
    ];
  }

  echo json_encode(["ok" => true, "data" => ["days" => $days, "series" => $series]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao gerar gráfico semanal por equipamento"], JSON_UNESCAPED_UNICODE);
}