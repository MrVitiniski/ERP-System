<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sistema");

// Busca os detalhes para o mecânico saber o que fazer
$sql = "SELECT id, data_abertura, equipamento, setor, solicitante, mecanico, descricao_problema, prioridade 
        FROM ordens_servico 
        WHERE status = 'ABERTA' 
        ORDER BY id DESC";

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
