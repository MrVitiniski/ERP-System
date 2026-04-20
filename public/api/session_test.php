<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
$_SESSION["ping"] = ($_SESSION["ping"] ?? 0) + 1;

header("Content-Type: application/json; charset=utf-8");
echo json_encode([
  "session_id" => session_id(),
  "cookie" => $_COOKIE["PHPSESSID"] ?? null,
  "session" => $_SESSION,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);