<?php
declare(strict_types=1);

// Exibe erros para nos ajudar caso algo falhe
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Importa a conexão com o banco e a autenticação (Padrão RH)
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php"; 

// Somente admin ou compras podem ver a lista
require_role(["admin", "compras"]); 

$pdo = db();

try {
    // Busca os fornecedores em ordem alfabética (A-Z)
    $stmt = $pdo->query("
        SELECT 
            id, 
            razao, 
            cnpj, 
            contato, 
            telefone, 
            email, 
            cidade_uf 
        FROM fornecedores 
        ORDER BY razao ASC 
        LIMIT 500
    ");

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // O SEGREDO: O JavaScript que você criou espera a chave "items"
    json_out([
        "ok" => true, 
        "items" => $items
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    json_out([
        "ok" => false, 
        "error" => $e->getMessage()
    ]);
}
