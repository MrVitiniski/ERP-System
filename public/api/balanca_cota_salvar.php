<?php
header('Content-Type: application/json');

// Caminho correto para o db.php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = db();   // Chama a função db() que está em includes/db.php

    $cliente = $_POST['cliente_nome'] ?? '';
    $limite  = $_POST['limite_ton'] ?? 0;
    $inicio  = $_POST['data_inicio'] ?? '';
    $fim     = $_POST['data_fim'] ?? '';

    if (empty($cliente) || $limite <= 0) {
        throw new Exception("Cliente e limite (em toneladas) são obrigatórios.");
    }

    if (empty($inicio) || empty($fim)) {
        throw new Exception("Datas de início e fim são obrigatórias.");
    }

    $sql = "INSERT INTO balanca_cotas_config 
            (cliente_nome, limite_ton, data_inicio, data_fim) 
            VALUES (?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([$cliente, (int)$limite, $inicio, $fim]);

    echo json_encode([
        'ok' => $ok,
        'mensagem' => $ok ? 'Cota salva com sucesso!' : 'Erro ao salvar cota.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'erro' => $e->getMessage()
    ]);
}