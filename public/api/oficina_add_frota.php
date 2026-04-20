<?php
header('Content-Type: application/json');
include_once "../includes/db.php"; 

try {
    $pdo = db();

    // Captura todos os campos do formulário
    $dados = [
        ':placa'      => $_POST['placa'] ?? '',
        ':modelo'     => $_POST['modelo'] ?? '',
        ':chassi'     => $_POST['chassi'] ?? '',
        ':ano'        => $_POST['ano'] ?? null,
        ':status'     => $_POST['status'] ?? 'ativo',
        ':f_ar_p'     => $_POST['filtro_ar_primario'] ?? '',
        ':f_ar_s'     => $_POST['filtro_ar_secundario'] ?? '',
        ':f_c_r'      => $_POST['filtro_comb_racor'] ?? '',
        ':f_c_s'      => $_POST['filtro_comb_secundario'] ?? '',
        ':f_o_m'      => $_POST['filtro_oleo_motor'] ?? '',
        ':q_o_m'      => $_POST['qtd_oleo_motor'] ?? 0,
        ':t_o_m'      => $_POST['tipo_oleo_motor'] ?? '',
        ':q_o_h'      => $_POST['qtd_oleo_hidraulico'] ?? 0,
        ':t_o_h'      => $_POST['tipo_oleo_hidraulico'] ?? '',
        ':obs'        => $_POST['obs'] ?? ''
    ];

    $sql = "INSERT INTO oficina_frota (
        placa, modelo, chassi, ano, status, 
        filtro_ar_primario, filtro_ar_secundario, filtro_comb_racor, 
        filtro_comb_secundario, filtro_oleo_motor, qtd_oleo_motor, 
        tipo_oleo_motor, qtd_oleo_hidraulico, tipo_oleo_hidraulico, obs
    ) VALUES (
        :placa, :modelo, :chassi, :ano, :status, 
        :f_ar_p, :f_ar_s, :f_c_r, :f_c_s, :f_o_m, 
        :q_o_m, :t_o_m, :q_o_h, :t_o_h, :obs
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
