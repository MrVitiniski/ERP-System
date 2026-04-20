<?php
declare(strict_types=1);

require_once __DIR__ . "/db.php";

/**
 * Inicia a sessão com parâmetros seguros e globais
 */
function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            "path" => "/",
            "httponly" => true,
            "samesite" => "Lax",
            // "secure" => false, // só ative com HTTPS em produção
        ]);
        session_start();
    }
}

/**
 * Retorna array do usuário logado ou null
 */
function current_user(): ?array {
    start_session();
    return $_SESSION['user'] ?? null;
}

/**
 * Exige login, ou encerra
 */
function require_login(): array {
    $u = current_user();
    if (!$u) {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["error" => "not_authenticated"]);
        exit;
    }
    return $u;
}

/**
 * Normaliza role (admin, RH, Balança -> admin, rh, balanca)
 */
function _norm_role(string $s): string {
    $s = trim(mb_strtolower($s, "UTF-8"));
    // remove acentos
    $s = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $s) ?: $s;
    $s = preg_replace('/[^a-z0-9_ -]/', '', $s) ?: $s;
    $s = str_replace(' ', '', $s);
    return $s;
}

/**
 * Exige uma role (ou lista de roles) ou encerra.
 * Suporta:
 * - $u["role"] (string)
 * - $u["roles"] (array)
 */
function require_role(array $roles): array {
    $u = require_login();

    $allowed = array_map(fn($r) => _norm_role((string)$r), $roles);

    $userRoles = [];

    if (isset($u["roles"]) && is_array($u["roles"])) {
        foreach ($u["roles"] as $r) {
            $rr = _norm_role((string)$r);
            if ($rr !== "") $userRoles[] = $rr;
        }
    }

    if (isset($u["role"]) && is_string($u["role"]) && trim($u["role"]) !== "") {
        $userRoles[] = _norm_role($u["role"]);
    }

    $userRoles = array_values(array_unique(array_filter($userRoles)));

    // admin sempre passa
    if (in_array("admin", $userRoles, true)) return $u;

    // checa interseção
    foreach ($allowed as $need) {
        if ($need !== "" && in_array($need, $userRoles, true)) return $u;
    }

    http_response_code(403);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "forbidden"]);
    exit;
}