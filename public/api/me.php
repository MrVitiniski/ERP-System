<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . "/../includes/auth.php";

json_out([
  "session" => $_SESSION ?? null,
  "user" => current_user(),
]);