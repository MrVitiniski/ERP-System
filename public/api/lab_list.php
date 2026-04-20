<?php
declare(strict_types=1);
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$pdo = db();
$data = $_GET["data"] ?? "";
$tipo = $_GET["tipo"] ?? "";
$where = []; $args = [];

if ($data !== "") { $where[] = "data_producao = ?"; $args[] = $data; }
if ($tipo !== "") { $where[] = "descricao LIKE ?"; $args[] = "%$tipo%"; }

$sql = "SELECT *, DATE_FORMAT(data_registro, '%d/%m/%Y %H:%i') as data_registro_formatada FROM lab_analises";
if ($where) { $sql .= " WHERE " . implode(" AND ", $where); }
$sql .= " ORDER BY data_registro DESC LIMIT 200";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    echo json_encode(["ok" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
