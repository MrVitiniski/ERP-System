<?php
// api/oficina_get_relatorio.php
header('Content-Type: application/json');

$caminho_db = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db.php';

if (file_exists($caminho_db)) {
    include_once $caminho_db;
} else {
    echo json_encode(["erro" => "Arquivo db.php nao encontrado"]);
    exit;
}

$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

if (!$inicio || !$fim) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = db(); 

    // TABELA ALTERADA PARA: oficina_os
    // ADICIONADO: setor e motorista_operador
    $sql = "SELECT 
            id, 
            equipamento, 
            mecanico, 
            status, 
            data_abertura, 
            data_encerramento,
            motorista_operador,
            setor,
            prioridade,
            descricao_problema,
            servico_executado,
            observacao
        FROM oficina_os 
        WHERE data_abertura BETWEEN :inicio AND :fim 
        ORDER BY data_abertura DESC";

    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':inicio' => $inicio . " 00:00:00",
        ':fim'    => $fim    . " 23:59:59"
    ]);

    $dados = $stmt->fetchAll();

    echo json_encode($dados);

} catch (PDOException $e) {
    echo json_encode(["erro" => "Erro no Banco (Oficina): " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["erro" => "Erro Geral: " . $e->getMessage()]);
}
