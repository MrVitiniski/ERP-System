<?php
include_once "../../includes/auth.php"; // Se tiver autenticação
include_once "../../includes/db.php";   // Sua conexão com o banco

$inicio = $_GET['inicio'];
$fim = $_GET['fim'];

// Prepara a query para evitar SQL Injection
$sql = "SELECT id, data_abertura, equipamento, mecanico, status, data_fim 
        FROM ordens_servico 
        WHERE data_abertura BETWEEN ? AND ? 
        ORDER BY data_abertura DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $inicio, $fim);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

// Retorna tudo como JSON para o seu HTML
header('Content-Type: application/json');
echo json_encode($dados);
