<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/db.php';
$pdo = db();

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception("Nenhum dado recebido.");
    }

    $sql = "INSERT INTO producao_lancamentos_temp 
            (data, hora, maquina, conchadas, tc01, tc02, tc03, tc04, tc05, minutos_parada, motivo_parada) 
            VALUES (:data, :hora, :maquina, :conchadas, :tc01, :tc02, :tc03, :tc04, :tc05, :minutos_parada, :motivo_parada)
            ON DUPLICATE KEY UPDATE 
            maquina = VALUES(maquina), 
            conchadas = VALUES(conchadas), 
            tc01 = VALUES(tc01), 
            tc02 = VALUES(tc02), 
            tc03 = VALUES(tc03), 
            tc04 = VALUES(tc04), 
            tc05 = VALUES(tc05), 
            minutos_parada = VALUES(minutos_parada), 
            motivo_parada = VALUES(motivo_parada)";

    $stmt = $pdo->prepare($sql);
    
    // Usamos (int) e (float) para garantir que valores vazios virem 0 e não quebrem o SQL
    $stmt->execute([
        ':data'           => $data['data'] ?? date('Y-m-d'),
        ':hora'           => $data['hora'],
        ':maquina'        => $data['maquina'] ?? 'WA320',
        ':conchadas'      => (int)($data['conchadas'] ?? 0),
        ':tc01'           => (float)($data['tc01'] ?? 0),
        ':tc02'           => (float)($data['tc02'] ?? 0),
        ':tc03'           => (float)($data['tc03'] ?? 0),
        ':tc04'           => (float)($data['tc04'] ?? 0),
        ':tc05'           => (float)($data['tc05'] ?? 0),
        ':minutos_parada' => (int)($data['minutos_parada'] ?? 0),
        ':motivo_parada'  => $data['motivo_parada'] ?? ''
    ]);

    echo json_encode(["ok" => true]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
}