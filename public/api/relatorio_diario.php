<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sistema");

// Busca movimentações de HOJE
$sql = "SELECT m.*, p.descricao 
        FROM movimentacoes m 
        JOIN produtos p ON m.produto_id = p.id 
        WHERE DATE(m.data_hora) = CURDATE() 
        ORDER BY m.data_hora DESC";

$res = $conn->query($sql);
$movs = [];
$e = 0; $s = 0;

while($row = $res->fetch_assoc()) {
    if($row['tipo'] == 'ENTRADA') $e += $row['quantidade'];
    else $s += $row['quantidade'];
    $movs[] = $row;
}

echo json_encode([
    "ok" => true, 
    "items" => $movs, 
    "resumo" => ["entradas" => $e, "saidas" => $s, "total" => count($movs)],
    "data" => date('d/m/Y')
]);
?>
