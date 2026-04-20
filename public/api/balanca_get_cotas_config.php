<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = db();   // Chama a função db() do arquivo db.php

    $sql = "SELECT id, cliente_nome, limite_ton, data_inicio, data_fim 
            FROM balanca_cotas_config 
            ORDER BY id DESC";

    $stmt = $pdo->query($sql);
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($res ?: []);   // Retorna array vazio se não tiver registros

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => $e->getMessage(),
        "arquivo" => "balanca_get_cotas_config.php"   // ajuda na hora de debugar
    ]);
}