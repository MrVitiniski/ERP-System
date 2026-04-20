<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();
    
    // Capturamos os dados enviados pelo formulário de saída
    $id = $_POST['id_pesagem'] ?? null;
    
    // --- AJUSTE DE MILHAR: Limpamos o ponto da máscara ---
    $pesoRaw = $_POST['peso_saida'] ?? '0';
    $pesoLimpo = str_replace('.', '', $pesoRaw); 
    // ----------------------------------------------------

    if (!$id) {
        throw new Exception("ID da pesagem não identificado.");
    }

    // Atualiza o registro: insere o peso de saída, a data/hora atual e finaliza o status
    $sql = "UPDATE balanca_pesagens SET 
                peso_saida = ?, 
                data_saida = NOW(), 
                status = 'finalizado' 
            WHERE id = ? AND status = 'aberto'";
    
    $stmt = $pdo->prepare($sql);
    // Usamos o $pesoLimpo convertido para float
    $stmt->execute([ (float)$pesoLimpo, $id ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Não foi possível finalizar. O ticket pode já estar encerrado.");
    }

    echo json_encode([
        "ok" => true, 
        "mensagem" => "✅ Pesagem #$id finalizada com sucesso!"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false, 
        "error" => "Erro ao finalizar: " . $e->getMessage()
    ]);
}