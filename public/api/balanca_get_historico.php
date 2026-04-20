<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();
    
    // SQL que busca TODOS os campos, incluindo NF e Material
    $sql = "SELECT id, placa_cavalo, motorista_nome, transportadora, 
               peso_entrada, peso_saida, status, 
               nf_numero, material_tipo, tipo_operacao,  -- GARANTA QUE ESTÃO AQUI
               DATE_FORMAT(data_entrada, '%d/%m/%Y %H:%i') as data_entrada_fmt,
               DATE_FORMAT(data_saida, '%d/%m/%Y %H:%i') as data_saida_fmt
        FROM balanca_pesagens 
        ORDER BY id DESC LIMIT 100";
            
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "items" => $items ?: []]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
