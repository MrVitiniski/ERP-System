<?php
// api/oficina_abrir_os.php
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');
include_once "../includes/db.php"; 

try {
    $pdo = db();

    // Captura os nomes que vêm do formulário HTML
    $data_abert = $_POST['data_abertura'] ?? date('Y-m-d H:i:s');
    $equip      = $_POST['equipamento'] ?? '';
    $prio       = $_POST['prioridade'] ?? 'Normal';
    $motorista  = $_POST['motorista'] ?? ''; 
    $mecanico   = $_POST['mecanico'] ?? ''; 
    $desc_prob  = $_POST['descricao_problema'] ?? '';

    
    $sql = "INSERT INTO oficina_os (
                data_abertura, 
                equipamento, 
                prioridade, 
                motorista_operador, 
                mecanico, 
                descricao_problema, 
                status
            ) VALUES (
                :data, 
                :equip, 
                :prio, 
                :solic, 
                :meca, 
                :desc, 
                'ABERTA'
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':data'  => $data_abert,
        ':equip' => $equip,
        ':prio'  => $prio,
        ':solic' => $motorista,
        ':meca'  => $mecanico,
        ':desc'  => $desc_prob
    ]);

    echo json_encode(["ok" => true, "id" => $pdo->lastInsertId()]);

} catch (Exception $e) {
    
    echo json_encode(["ok" => false, "error" => "Erro no Banco: " . $e->getMessage()]);
}
