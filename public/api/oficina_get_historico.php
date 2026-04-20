<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();
    $sql = "SELECT id, data_abertura, data_encerramento, equipamento, setor, 
                   descricao_problema, servico_executado, observacao, mecanico, motorista_operador 
            FROM oficina_os 
            WHERE status = 'ENCERRADA' 
            ORDER BY data_encerramento DESC";
            
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "items" => $dados ?: []]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
