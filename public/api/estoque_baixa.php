<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    die(json_encode(["ok" => false, "error" => "Falha na conexão"]));
}

$item_nome   = trim($_POST['saida_item'] ?? ''); 
$qtd_retirar = (int)($_POST['saida_qtd'] ?? 0);
$colaborador = trim($_POST['colaborador'] ?? 'Não informado');
$setor       = trim($_POST['setor_destino'] ?? 'Não informado');
$observacao  = trim($_POST['observacao'] ?? '');

if (empty($item_nome)) {
    die(json_encode(["ok" => false, "error" => "Selecione um item."]));
}

// 4. BUSCA usando 'estoque_atual' (nome que estava na sua tabela)
$sql_busca = "SELECT id, estoque_atual FROM produtos WHERE descricao = '$item_nome' LIMIT 1";
$res_busca = $conn->query($sql_busca);

if ($res_busca && $res_busca->num_rows > 0) {
    $produto = $res_busca->fetch_assoc();
    $saldo_em_estoque = (int)$produto['estoque_atual']; // Saldo vindo do banco
    $id_prod = $produto['id'];

    if ($saldo_em_estoque < $qtd_retirar) {
        die(json_encode(["ok" => false, "error" => "Estoque insuficiente! Saldo: " . $saldo_em_estoque]));
    }

    $novo_saldo = $saldo_em_estoque - $qtd_retirar;
    
    // 6. UPDATE usando 'estoque_atual'
    $sql_update = "UPDATE produtos SET estoque_atual = $novo_saldo WHERE id = $id_prod";
    
    if ($conn->query($sql_update) === TRUE) {
        
        // 7. INSERT na tabela de histórico (que criamos agora)
        
        $sql_mov = "INSERT INTO movimentacoes (produto_id, tipo, quantidade, colaborador, setor, observacao) 
            VALUES ('$id_prod', 'SAIDA', '$qtd_retirar', '$colaborador', '$setor', '$observacao')";
    $conn->query($sql_mov);

        echo json_encode(["ok" => true, "novo_saldo" => $novo_saldo]);
    } else {
        echo json_encode(["ok" => false, "error" => "Erro no banco: " . $conn->error]);
    }
} else {
    echo json_encode(["ok" => false, "error" => "Item não encontrado."]);
}

$conn->close();
?>
