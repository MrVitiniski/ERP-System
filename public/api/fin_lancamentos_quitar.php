<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

$in = json_decode(file_get_contents("php://input"), true);
if (!$in) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Payload inválido"]); exit; }

$id = (int)($in["id"] ?? 0);
$data = trim((string)($in["data_quitacao"] ?? "")); // YYYY-MM-DD opcional

if ($id <= 0) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"ID inválido"]); exit; }
if ($data !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Data inválida (YYYY-MM-DD)."]); exit;
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

try {
  $pdo->beginTransaction();

  $st = $pdo->prepare("SELECT id, status FROM fin_lancamentos WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new Exception("Lançamento não encontrado.");
  if (($row["status"] ?? "") !== "aberto") throw new Exception("Só é possível quitar lançamentos em aberto.");

  if ($data === "") {
    $up = $pdo->prepare("
      UPDATE fin_lancamentos
      SET status='quitado',
          data_quitacao = DATE(NOW()),
          quitado_por = ?
      WHERE id = ?
    ");
    $up->execute([$userName, $id]);
  } else {
    $up = $pdo->prepare("
      UPDATE fin_lancamentos
      SET status='quitado',
          data_quitacao = ?,
          quitado_por = ?
      WHERE id = ?
    ");
    $up->execute([$data, $userName, $id]);
  }

  $pdo->commit();
  echo json_encode(["ok"=>true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
}