<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../includes/db.php';
$pdo = db();

try {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) throw new Exception("Formato de dados inválido.");

    $pdo->beginTransaction();

    $sql = "INSERT INTO producao_lancamentos_temp 
            (data, hora, maquina, conchadas, tc01, tc02, tc03, tc04, tc05, minutos_parada, motivo_parada) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            maquina = VALUES(maquina), conchadas = VALUES(conchadas), 
            tc01 = VALUES(tc01), tc02 = VALUES(tc02), tc03 = VALUES(tc03), 
            tc04 = VALUES(tc04), tc05 = VALUES(tc05), 
            minutos_parada = VALUES(minutos_parada), motivo_parada = VALUES(motivo_parada)";

    $stmt = $pdo->prepare($sql);

    foreach ($data as $row) {
        $stmt->execute([
            $row['data'], $row['hora'], $row['maquina'],
            (int)$row['conchadas'], (float)$row['tc01'], (float)$row['tc02'],
            (float)$row['tc03'], (float)$row['tc04'], (float)$row['tc05'],
            (int)$row['minutos_parada'], $row['motivo_parada']
        ]);
    }

    $pdo->commit();
    echo json_encode(["ok" => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
}