<?php
header("Content-Type: application/json");
// error_reporting(E_ALL); // Ative para testar se houver erro, depois mude para 0

$conn = new mysqli("localhost", "root", "", "sistema");

if ($conn->connect_error) {
    echo json_encode(["ok" => false, "erro" => "Falha na conexão"]);
    exit;
}

$inicio = $_GET['inicio'] ?? '';
$fim = $_GET['fim'] ?? '';

// 1. Busca os relatórios no intervalo de datas
$sql = "SELECT * FROM producao_relatorios 
        WHERE data BETWEEN ? AND ? 
        ORDER BY data ASC, turno ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $inicio, $fim);
$stmt->execute();
$res = $stmt->get_result();

$items = [];

if ($res) {
    while($row = $res->fetch_assoc()){
        // 2. Decodifica o JSON das paradas para que o JS entenda como array
        if (!empty($row['lista_paradas'])) {
            $row['lista_paradas'] = json_decode($row['lista_paradas'], true);
        } else {
            $row['lista_paradas'] = [];
        }
        
        $items[] = $row;
    }
}

// 3. Retorna os dados limpos
echo json_encode(["ok" => true, "items" => $items]);

$stmt->close();
$conn->close();
?>
