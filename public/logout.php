<?php
require_once __DIR__ . "/includes/auth.php";
start_session();
unset($_SESSION['user']);
session_destroy();
header("Location: /sistema/public/login.php");
exit;