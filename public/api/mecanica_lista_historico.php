<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão"]));
}

// BUSCA TUDO: Dados da Abertura (Problema/Solicitante) e Dados do Fechamento (Serviço/Data Fim)
$sql = "SELECT id, data_abertura, data_encerramento, equipamento, setor, solicitante, mecanico, descricao_problema, servico_executado, observacao 
        FROM ordens_servico 
        WHERE status = 'ENCERRADA' 
        ORDER BY data_encerramento DESC";

$result = $conn->query($sql);
$items = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

echo json_encode(["ok" => true, "items" => $items]);
$conn->close();
?>
