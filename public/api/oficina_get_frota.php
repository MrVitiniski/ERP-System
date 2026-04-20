<?php
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();
    // Busca todos os campos para a ficha técnica
    $sql = "SELECT * FROM oficina_frota ORDER BY placa ASC";
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "items" => $dados]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
