<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/../includes/auth.php";

start_session();

$u = current_user();
if ($u) {
  audit_log($u["id"], "logout", "user", (string)$u["id"], null, null);
}

// Limpa a sessão em memória
$_SESSION = [];

// Remove o cookie da sessão (IMPORTANTE)
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    (bool)$params['secure'],
    (bool)$params['httponly']
  );
}

// Destroi a sessão no servidor
session_destroy();

json_out(["ok" => true]);