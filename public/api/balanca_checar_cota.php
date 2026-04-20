<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "sistema");

$empresa = $_GET['empresa'] ?? '';
$hoje = date('Y-m-d');

$sql = "SELECT toneladas_total, toneladas_saldo FROM balanca_cotas 
        WHERE empresa = '$empresa' AND data_cota = '$hoje' LIMIT 1";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    echo json_encode(["tem_cota" => true, "dados" => $res->fetch_assoc()]);
} else {
    echo json_encode(["tem_cota" => false]);
}
