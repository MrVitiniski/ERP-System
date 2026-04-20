<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

// Captura a placa enviada pelo JavaScript
$placa = $_GET['placa'] ?? '';

if (empty($placa)) {
    echo json_encode(["ok" => false, "error" => "Placa não informada"]);
    exit;
}

try {
    $pdo = db();
    
    // CORREÇÃO: Buscamos na tabela 'balanca_pesagens' (a mesma do salvar_entrada)
    // E usamos os nomes de colunas corretos: motorista_nome e motorista_doc
    $sql = "SELECT transportadora, motorista_nome, motorista_doc, placa_carreta 
            FROM balanca_pesagens 
            WHERE placa_cavalo = ? 
            ORDER BY id DESC LIMIT 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$placa]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    // Retorna os dados encontrados (ou null se for a primeira vez do caminhão)
    echo json_encode([
        "ok" => true,
        "dados" => $dados ?: null
    ]);

} catch (Exception $e) {
    echo json_encode([
        "ok" => false, 
        "error" => "Erro na busca: " . $e->getMessage()
    ]);
}
