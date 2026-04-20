<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

$id = $_GET['id'] ?? null;

try {
    if (!$id) throw new Exception("ID não informado.");

    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["ok" => true, "mensagem" => "Usuário removido com sucesso!"]);
} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
