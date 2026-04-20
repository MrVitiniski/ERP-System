<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Conexão falhou"]));
}

// Busca todos os itens ordenando pelo mais recente
$sql = "SELECT * FROM produtos ORDER BY id DESC";
$result = $conn->query($sql);

$items = [];
while($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(["ok" => true, "items" => $items]);
$conn->close();
?>
