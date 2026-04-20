<?php
error_reporting(0); 
header('Content-Type: application/json');
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . "/../includes/db.php"; 

try {
    $pdo = db(); 
    $agora = date('Y-m-d H:i:s'); 

    $sql = "INSERT INTO lab_analises (
        data_producao, descricao, peso_seco, volume, densidade,
        fe_pct, sio2_pct, al2o3_pct, fet_pct, fe2o3_pct, 
        mn_pct, p_pct, ppc_pct, com_alumina, granulometria, data_registro
    ) VALUES (
        :data_p, :desc, :p_seco, :vol, :dens,
        :fe, :sio2, :al2o3, :fet, :fe2o3, 
        :mn, :p_p, :ppc, :com_alum, :granulometria, :data_reg
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':data_p'        => $_POST['data_producao'] ?? null,
        ':desc'          => $_POST['descricao'] ?? '',
        ':p_seco'        => $_POST['peso_seco'] ?? 0,
        ':vol'           => $_POST['volume'] ?? 0,
        ':dens'          => $_POST['densidade'] ?? 0,
        ':fe'            => $_POST['fe_pct'] ?? 0,
        ':sio2'          => $_POST['sio2_pct'] ?? 0,
        ':al2o3'         => $_POST['al2o3_pct'] ?? 0,
        ':fet'           => $_POST['fet_pct'] ?? 0,
        ':fe2o3'         => $_POST['fe2o3_pct'] ?? 0,
        ':mn'            => $_POST['mn_pct'] ?? 0,
        ':p_p'           => $_POST['p_pct'] ?? 0,
        ':ppc'           => $_POST['ppc_pct'] ?? 0,
        ':com_alum'      => $_POST['com_alumina'] ?? '',
        ':granulometria' => $_POST['granulometria'] ?? '',
        ':data_reg'      => $agora
    ]);

    echo json_encode(["ok" => true, "id" => $pdo->lastInsertId(), "mensagem" => "✅ Salvo às " . date('H:i')]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
