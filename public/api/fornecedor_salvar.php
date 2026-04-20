<?php
require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$pdo = db();
$input = json_decode(file_get_contents("php://input"), true);

$razao     = trim((string)($input["razao"] ?? ""));
$cnpj      = trim((string)($input["cnpj"] ?? ""));
$contato   = trim((string)($input["contato"] ?? ""));
$telefone  = trim((string)($input["telefone"] ?? ""));
$email     = trim((string)($input["email"] ?? ""));
$cidade_uf = trim((string)($input["cidade_uf"] ?? ""));

if ($razao === "" || $cnpj === "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Razão Social e CNPJ são obrigatórios."]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO fornecedores (razao, cnpj, contato, telefone, email, cidade_uf) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$razao, $cnpj, $contato, $telefone, $email, $cidade_uf]);

    echo json_encode(["ok" => true, "id" => $pdo->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
