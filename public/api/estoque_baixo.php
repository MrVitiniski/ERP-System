<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sistema";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão"]));
}

// 2. Query CORRIGIDA: Trocado 'quantidade' por 'estoque_atual'
$sql = "SELECT * FROM produtos WHERE estoque_atual <= estoque_minimo";
$result = $conn->query($sql);

$items = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Ajuste no cálculo também usando o nome correto da coluna
        $row['sugestao_compra'] = (int)$row['estoque_minimo'] - (int)$row['estoque_atual'];
        $items[] = $row;
    }
}

echo json_encode([
    "ok" => true,
    "items" => $items,
    "total" => count($items)
]);

$conn->close();
?>
