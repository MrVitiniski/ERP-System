<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

try {
    $pdo = db();

    $nome    = $_POST['nome']    ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $senha   = $_POST['senha']   ?? '';
    
    // Captura o array de checkboxes (roles[]) e transforma em texto separado por vírgula
    $rolesArray = $_POST['roles'] ?? []; 
    $role = !empty($rolesArray) ? implode(', ', $rolesArray) : 'BALANÇA';

    if (empty($nome) || empty($usuario) || empty($senha)) {
        throw new Exception("Preencha todos os campos obrigatórios (Nome, Login e Senha).");
    }

    if (empty($rolesArray)) {
        throw new Exception("Selecione pelo menos um Nível de Acesso/Setor.");
    }

    // Criptografia da senha (Segurança padrão)
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // SQL para inserir o novo usuário com a lista de permissões
    $sql = "INSERT INTO usuarios (nome, usuario, senha, role, status) 
            VALUES (?, ?, ?, ?, 'ativo')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nome, 
        $usuario, 
        $senhaHash, 
        $role // Aqui será salvo ex: "RH, FINANCEIRO, BALANÇA"
    ]);

    echo json_encode([
        "ok" => true, 
        "mensagem" => "✅ Usuário $usuario criado com sucesso com acesso a: $role"
    ]);

} catch (Exception $e) {
    // Tratamento para logins duplicados
    $msg = strpos($e->getMessage(), 'Duplicate entry') !== false 
           ? "Erro: O login '$usuario' já está em uso por outro colaborador." 
           : "Erro no Banco: " . $e->getMessage();
           
    echo json_encode(["ok" => false, "error" => $msg]);
}
