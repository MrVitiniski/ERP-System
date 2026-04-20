<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();
    // Busca o maior ID atual
    $stmt = $pdo->query("SELECT MAX(id) as ultimo FROM balanca_pesagens");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $proximo = ($res['ultimo'] ?? 0) + 1;

    echo json_encode(["ok" => true, "proximo" => $proximo]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
