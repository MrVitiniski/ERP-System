<?php
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();
    $inicio = $_GET['inicio'] ?? null;
    $fim = $_GET['fim'] ?? null;

    $sql = "SELECT *, DATE_FORMAT(data_registro, '%d/%m/%Y %H:%i:%s') as data_registro_formatada 
            FROM lab_analises";
    $params = [];

    if ($inicio && $fim) {
        $sql .= " WHERE data_producao BETWEEN :inicio AND :fim";
        $params[':inicio'] = $inicio;
        $params[':fim'] = $fim;
    }

    $sql .= " ORDER BY data_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["ok" => true, "items" => $dados ?: []]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
