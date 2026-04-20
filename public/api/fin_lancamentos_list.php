<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$pdo = db();

$tipo = (string)($_GET["tipo"] ?? "");
$status = (string)($_GET["status"] ?? "");
$q = trim((string)($_GET["q"] ?? ""));
$de = (string)($_GET["de"] ?? "");
$ate = (string)($_GET["ate"] ?? "");
$limit = (int)($_GET["limit"] ?? 200);
$limit = max(1, min(500, $limit));

$where = [];
$params = [];

if (in_array($tipo, ["pagar", "receber"], true)) {
  $where[] = "tipo = ?";
  $params[] = $tipo;
}
if (in_array($status, ["aberto", "quitado", "cancelado"], true)) {
  $where[] = "status = ?";
  $params[] = $status;
}
if ($q !== "") {
  $where[] = "(pessoa LIKE ? OR descricao LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $de)) {
  $where[] = "data_prevista >= ?";
  $params[] = $de;
}
if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $ate)) {
  $where[] = "data_prevista <= ?";
  $params[] = $ate;
}

$sql = "
  SELECT
    id, tipo, status, pessoa, descricao, valor, data_prevista, data_quitacao,
    criado_por, criado_em,
    grupo_id, parcela_num, parcela_total
  FROM fin_lancamentos
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY data_prevista ASC, id DESC LIMIT $limit";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(["ok" => true, "items" => $stmt->fetchAll()]);