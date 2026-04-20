<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

$in = json_decode(file_get_contents("php://input"), true);
if (!$in) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Payload inválido"]); exit; }

$dia = (string)($in["dia"] ?? "");
$conta = trim((string)($in["conta"] ?? "principal"));
$saldo = $in["saldo_bancario"] ?? null;

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dia)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Dia inválido (YYYY-MM-DD)."]); exit;
}
if ($conta === "") $conta = "principal";
if (!is_numeric($saldo)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Saldo bancário inválido."]); exit;
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

try {
  // MySQL/MariaDB upsert
  $stmt = $pdo->prepare("
    INSERT INTO fin_caixa_saldos_dia (dia, conta, saldo_bancario, informado_por, informado_em)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
      saldo_bancario = VALUES(saldo_bancario),
      informado_por = VALUES(informado_por),
      informado_em = NOW()
  ");
  $stmt->execute([$dia, $conta, (float)$saldo, $userName]);

  echo json_encode(["ok"=>true]);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
}