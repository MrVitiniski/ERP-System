<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

$id = $_GET['id'] ?? null;
$placa = $_GET['placa'] ?? null;

try {
    $pdo = db();
    
    // SQL ATUALIZADO: Incluímos 'nf_numero' e 'material_tipo' no SELECT
    $sql = "SELECT id, motorista_nome, transportadora, peso_entrada, material_tipo, nf_numero 
            FROM balanca_pesagens 
            WHERE UPPER(TRIM(status)) = 'ABERTO' ";

    if (!empty($id)) {
        $sql .= " AND id = :id ";
        $params = [':id' => $id];
    } else if (!empty($placa)) {
        $sql .= " AND UPPER(placa_cavalo) = UPPER(:placa) ";
        $params = [':placa' => trim($placa)];
    } else {
        echo json_encode(["ok" => false, "error" => "Informe Ticket ou Placa"]);
        exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    // Retorna os dados para o JavaScript
    echo json_encode([
        "ok" => true, 
        "dados" => $dados ?: null
    ]);

} catch (Exception $e) {
    echo json_encode([
        "ok" => false, 
        "error" => "Erro no banco: " . $e->getMessage()
    ]);
}
