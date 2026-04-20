<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();

$raw  = file_get_contents("php://input") ?: "";
$json = json_decode($raw, true);

$usuarioInput = trim((string)($json["username"] ?? $_POST["username"] ?? ""));
$senhaInput   = (string)($json["password"] ?? $_POST["password"] ?? "");

if ($usuarioInput === "" || $senhaInput === "") {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Informe usuário e senha."]);
    exit;
}

function login_ok(array $sessionUser): void {
    session_regenerate_id(true);
    $_SESSION["user"] = $sessionUser;
    echo json_encode(["ok" => true, "mensagem" => "Login OK"]);
    exit;
}

try {
    $pdo = db();

    // 1) NOVO: tenta autenticar pela tabela `usuarios` (seu cadastro usa ela)
    $sqlUsuarios = "
        SELECT id, nome, usuario, senha, role, status
        FROM usuarios
        WHERE LOWER(usuario) = LOWER(?) AND LOWER(status) = 'ativo'
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlUsuarios);
    $stmt->execute([$usuarioInput]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        $senhaDb = (string)($u["senha"] ?? "");
        $senhaOk = password_verify($senhaInput, $senhaDb) || ($senhaInput === $senhaDb);

        if ($senhaOk) {
            $roleRaw = (string)($u["role"] ?? "");
            $roles = array_values(array_filter(array_map("trim", explode(",", $roleRaw))));

            login_ok([
                "id"    => (int)$u["id"],
                "name"  => (string)($u["nome"] ?? ""),
                "role"  => $roles[0] ?? $roleRaw,
                "roles" => $roles ?: [$roleRaw],
            ]);
        }
    }

    // 2) LEGADO: tenta autenticar pela tabela `users` (se o admin antigo ainda está lá)
    $sqlUsers = "
        SELECT id, name AS nome, username AS usuario, password_hash AS senha, role, active AS status
        FROM users
        WHERE LOWER(username) = LOWER(?) AND active = 1
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlUsers);
    $stmt->execute([$usuarioInput]);
    $u2 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u2) {
        $senhaDb = (string)($u2["senha"] ?? "");
        $senhaOk = password_verify($senhaInput, $senhaDb) || ($senhaInput === $senhaDb);

        if ($senhaOk) {
            login_ok([
                "id"    => (int)$u2["id"],
                "name"  => (string)($u2["nome"] ?? ""),
                "role"  => (string)($u2["role"] ?? ""),
                "roles" => [(string)($u2["role"] ?? "")],
            ]);
        }
    }

    http_response_code(401);
    echo json_encode(["ok" => false, "error" => "Usuário ou senha incorretos."]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erro no servidor."]);
    exit;
}