<?php
header('Content-Type: application/json');
require_once "../includes/db.php";

$placa = $_GET['placa'] ?? '';
try {
    $pdo = db();
    // Busca o último registro finalizado desse caminhão
    $stmt = $pdo->prepare("SELECT motorista_nome, motorista_doc, transportadora, placa_carreta 
                           FROM balanca_pesagens 
                           WHERE placa_cavalo = ? 
                           ORDER BY id DESC LIMIT 1");
    $stmt->execute([$placa]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "dados" => $dados ?: null]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
