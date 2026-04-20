<?php
declare(strict_types=1);

header("Content-Type: text/plain; charset=utf-8");
ini_set("display_errors", "1");
error_reporting(E_ALL);

echo "diag: begin\n";

echo "diag: before auth.php\n";
require_once __DIR__ . "/../includes/auth.php";
echo "diag: after auth.php\n";

echo "diag: before db.php\n";
require_once __DIR__ . "/../includes/db.php";
echo "diag: after db.php\n";

echo "diag: functions existence\n";
echo " - json_input: " . (function_exists("json_input") ? "yes" : "NO") . "\n";
echo " - json_out: " . (function_exists("json_out") ? "yes" : "NO") . "\n";
echo " - db: " . (function_exists("db") ? "yes" : "NO") . "\n";
echo " - require_role: " . (function_exists("require_role") ? "yes" : "NO") . "\n";
echo " - start_session: " . (function_exists("start_session") ? "yes" : "NO") . "\n";
echo " - current_user: " . (function_exists("current_user") ? "yes" : "NO") . "\n";
echo " - audit_log: " . (function_exists("audit_log") ? "yes" : "NO") . "\n";

echo "diag: ok\n";