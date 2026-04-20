<?php
header('Content-Type: application/json');

// Conexão (ajuste se necessário)
$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão"]));
}

// Recebe os dados do formulário
$cod      = $_POST['cod_barras'] ?? '';
$desc     = $_POST['descricao'] ?? '';
$nf       = $_POST['nf'] ?? '';
$qtd      = (int)($_POST['quantidade'] ?? 0);
$min      = $_POST['estoque_minimo'] ?? 0;
$seg      = $_POST['segmento'] ?? '';
$setor    = $_POST['setor_destino'] ?? '';
$loc      = $_POST['localizacao'] ?? '';
$andar    = $_POST['andar_nivel'] ?? '';

// 1. Grava o item usando os nomes exatos das colunas do seu banco
$sql = "INSERT INTO produtos (cod_barras, codigo, nome, descricao, nf, estoque_atual, estoque_minimo, segmento, setor_destino, localizacao, andar_nivel) 
        VALUES ('$cod', '$cod', '$desc', '$desc', '$nf', '$qtd', '$min', '$seg', '$setor', '$loc', '$andar')";

if ($conn->query($sql) === TRUE) {
    $produto_id = $conn->insert_id;
    
    // 2. Grava a movimentação (verifique se a tabela movimentacoes tem a coluna 'quantidade')
    $sql_mov = "INSERT INTO movimentacoes (produto_id, tipo, quantidade, colaborador, setor) 
                VALUES ('$produto_id', 'ENTRADA', '$qtd', 'Sistema', '$setor')";
    $conn->query($sql_mov);

    echo json_encode(["ok" => true]);
} else {
    // Se der erro aqui, ele vai te dizer exatamente qual coluna está errada
    echo json_encode(["ok" => false, "error" => "Erro no SQL: " . $conn->error]);
}


$conn->close();
?>
