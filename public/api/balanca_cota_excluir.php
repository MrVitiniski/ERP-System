<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
try {
    $pdo = db();
    $id = $_GET['id'] ?? 0;

    $sql = "DELETE FROM balanca_cotas_config WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([$id]);
    
    echo json_encode(['ok' => $ok]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
