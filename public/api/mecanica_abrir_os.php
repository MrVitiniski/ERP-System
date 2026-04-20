<?php
header('Content-Type: application/json');

// Configura o fuso horário para evitar divergências
date_default_timezone_set('America/Sao_Paulo');

$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão"]));
}

// 1. Recebe os dados do formulário
$equip      = $_POST['equipamento'] ?? '';
$solic      = $_POST['solicitante'] ?? '';
$setor      = $_POST['setor'] ?? ''; // Campo novo
$mecanico   = $_POST['mecanico'] ?? ''; 
$desc_prob  = $_POST['descricao_problema'] ?? ''; // Campo novo (ajustado conforme o JS)
$prioridade = $_POST['prioridade'] ?? 'normal';

// 2. Recebe a DATA MANUAL de abertura (se não vier, usa a atual)
$data_abertura = $_POST['data_abertura'] ?? date('Y-m-d H:i:s');

// 3. Grava no banco de dados incluindo os novos campos
$sql = "INSERT INTO ordens_servico (equipamento, solicitante, setor, mecanico, descricao_problema, prioridade, data_abertura, status) 
        VALUES ('$equip', '$solic', '$setor', '$mecanico', '$desc_prob', '$prioridade', '$data_abertura', 'ABERTA')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["ok" => true, "id" => $conn->insert_id]);
} else {
    // Se der erro aqui, verifique se as colunas 'setor' e 'descricao_problema' existem no seu Banco de Dados
    echo json_encode(["ok" => false, "error" => "Erro ao salvar: " . $conn->error]);
}

$conn->close();
?>
