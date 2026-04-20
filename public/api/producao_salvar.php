<?php
session_start(); // Inicia a sessão para podermos destruí-la depois
header("Content-Type: application/json");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("localhost", "root", "", "sistema");
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!$data) {
        throw new Exception("Dados JSON não recebidos.");
    }

    // Corrigido: Removido o duplo $$ e garantido o JSON das paradas
    $lista_paradas_json = json_encode($data["lista_paradas"] ?? []);

    $stmt = $conn->prepare("
        INSERT INTO producao_relatorios 
        (data, turno, responsavel, operador, material, tc01, tc02, tc03, tc04, tc05, total, eficiencia, total_paradas, lista_paradas, hora_encerramento) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssdddddddiss",
        $data["data"],
        $data["turno"],
        $data["responsavel"],
        $data["operador"],
        $data["material"],
        $data["tc01"],
        $data["tc02"],
        $data["tc03"],
        $data["tc04"],
        $data["tc05"],
        $data["total"],
        $data["eficiencia"],
        $data["total_paradas"],
        $lista_paradas_json,
        $data["hora_encerramento"]
    );

    if ($stmt->execute()) {
        //  IMPORTANTE: Limpa a sessão para o próximo usuário
        session_unset();
        session_destroy();
        
        echo json_encode(["ok" => true, "mensagem" => "Relatório salvo e sessão encerrada."]);
    } else {
        throw new Exception($stmt->error);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "erro" => $e->getMessage()]);
}
?>
