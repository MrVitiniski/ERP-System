<?php
require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

try {
    $pdo = db();
    // Verifique se o nome é 'compras_ordens' ou 'compras_orders' no seu phpMyAdmin
    // Baseado no seu print anterior, o nome correto é: compras_ordens
    $stmt = $pdo->query("SELECT * FROM compras_ordens ORDER BY id DESC LIMIT 500");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // O JavaScript espera exatamente esta estrutura: { "ok": true, "items": [...] }
    echo json_encode([
        "ok"    => true,
        "items" => $items
    ]);
} catch (Throwable $e) {
    echo json_encode([
        "ok"    => false,
        "error" => $e->getMessage()
    ]);
}
