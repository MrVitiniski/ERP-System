<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();
    
    // --- NOVIDADE AQUI: TRATAMENTO DO PESO ANTES DE SALVAR ---
    // Removemos os pontos que a máscara do JavaScript coloca (ex: "11.450" vira "11450")
    $peso_raw = $_POST['peso_entrada'] ?? '0';
    $peso_limpo = str_replace('.', '', $peso_raw); 
    // --------------------------------------------------------

    $sql = "INSERT INTO balanca_pesagens (
                placa_cavalo, 
                placa_carreta, 
                motorista_nome, 
                motorista_doc, 
                transportadora, 
                peso_entrada, 
                nf_numero, 
                material_tipo, 
                tipo_operacao, 
                cliente_nome, 
                data_entrada, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'aberto')";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        $_POST['placa_cavalo'] ?? '',
        $_POST['placa_carreta'] ?? '',
        $_POST['motorista_nome'] ?? '',
        $_POST['motorista_doc'] ?? '',
        $_POST['transportadora'] ?? '',
        (float)$peso_limpo, // <--- AJUSTADO: Agora usamos o peso sem os pontos de milhar
        $_POST['nf_numero'] ?? '',
        $_POST['material_tipo'] ?? '',
        $_POST['tipo_operacao'] ?? '',
        $_POST['cliente_nome'] ?? ''  
    ]);

    $novoID = $pdo->lastInsertId();

    echo json_encode([
        "ok" => true, 
        "mensagem" => "✅ Entrada #$novoID registrada com sucesso!",
        "id" => $novoID
    ]);

} catch (Exception $e) {
    echo json_encode([
        "ok" => false, 
        "error" => "Erro no Banco: " . $e->getMessage()
    ]);
}