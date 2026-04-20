<?php
// api/oficina_get_lista_os.php
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();
    
    $sql = "SELECT id, data_abertura, equipamento, motorista_operador, setor, descricao_problema 
            FROM oficina_os 
            WHERE status = 'ABERTA' 
            ORDER BY data_abertura DESC";
            
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    echo json_encode(["ok" => true, "items" => $dados ?: []]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
