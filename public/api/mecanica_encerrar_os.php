<?php
header('Content-Type: application/json');

// 1. Configura o fuso horário para Brasília
date_default_timezone_set('America/Sao_Paulo');

$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão com o banco"]));
}

// 2. Recebe os dados do formulário/prompt
$id       = (int)($_POST['os'] ?? 0);
$servico  = mysqli_real_escape_string($conn, $_POST['servico'] ?? '');
$obs      = mysqli_real_escape_string($conn, $_POST['obs'] ?? '');

// 3. Recebe a DATA MANUAL de encerramento enviada pelo JS
// Se não for informada, o sistema usará a data e hora atual do servidor
$data_fim = $_POST['data_fim'] ?? date('Y-m-d H:i:s');

// 4. Validação básica
if ($id <= 0) {
    die(json_encode(["ok" => false, "error" => "ID da Ordem de Serviço inválido."]));
}

// 5. Atualiza o banco usando a data informada ($data_fim)
$sql = "UPDATE ordens_servico SET 
        status = 'ENCERRADA', 
        servico_executado = '$servico', 
        observacao = '$obs',
        data_encerramento = '$data_fim' 
        WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "error" => "Erro ao encerrar OS: " . $conn->error]);
}

$conn->close();
?>
