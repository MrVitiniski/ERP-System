<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();

    // Buscamos os usuários ordenados pelo nome
    $stmt = $pdo->query("SELECT id, nome, usuario, role, status, data_criacao FROM usuarios ORDER BY nome ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "items" => $items ?: []]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
