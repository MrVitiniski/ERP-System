<?php
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();
    // 1. Captura o ID da OS e o texto da observação
    $id      = (int)($_POST['os'] ?? 0);
    $servico = $_POST['servico'] ?? '';
    $obs     = $_POST['obs'] ?? ''; // <--- Pega o 'name="obs"' do HTML
    $data_f  = $_POST['data_fim'] ?? date('Y-m-d H:i:s');

    // 2. Faz o UPDATE na tabela oficina_os
    $sql = "UPDATE oficina_os SET 
            status = 'ENCERRADA', 
            servico_executado = :serv, 
            observacao = :obs, 
            data_encerramento = :fim 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':serv' => $servico, 
        ':obs'  => $obs, 
        ':fim'  => $data_f, 
        ':id'   => $id
    ]);

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
