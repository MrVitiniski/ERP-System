<?php
header("Content-Type: application/json");
error_reporting(0); 

try {
    $conn = new mysqli("localhost", "root", "", "sistema");

    if ($conn->connect_error) throw new Exception("Erro de conexão.");

    $inicio = $_GET['inicio'] ?? '';
    $fim = $_GET['fim'] ?? '';

    // 🔥 SELECT * busca tudo e ORDER BY data ASC coloca em ordem crescente
    $sql = "SELECT * FROM producao_relatorios 
            WHERE data BETWEEN ? AND ? 
            ORDER BY data ASC, turno ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $inicio, $fim);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while($row = $res->fetch_assoc()){
        // Decodifica o JSON das paradas para o JS ler como lista
        $row['lista_paradas'] = json_decode($row['lista_paradas'] ?? '[]', true);
        $items[] = $row;
    }

    echo json_encode(["ok" => true, "items" => $items]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
}
