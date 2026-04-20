<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "almoxarifado"]); // ATIVE (senão qualquer logado entrega)

$raw = file_get_contents("php://input") ?: "";
$in = json_decode($raw, true);
if (!is_array($in)) $in = [];

$id = (int)($in["id"] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "ID inválido."]);
  exit;
}

try {
  $pdo = db();
  $entreguePor = $_SESSION["user"]["id"] ?? null;

  $pdo->beginTransaction();

  // trava o pedido para evitar dupla entrega
  $stmtP = $pdo->prepare("
    SELECT id, funcionario_id, epi, qtd, obs, status
    FROM epi_pedidos
    WHERE id = ?
    FOR UPDATE
  ");
  $stmtP->execute([$id]);
  $pedido = $stmtP->fetch(PDO::FETCH_ASSOC);

  if (!$pedido) {
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(["ok" => false, "error" => "Pedido não encontrado."]);
    exit;
  }

  if (($pedido["status"] ?? "") !== "PENDENTE_ALMOX") {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(["ok" => false, "error" => "Pedido não está pendente ou já foi entregue."]);
    exit;
  }

  $funcionarioId = (int)($pedido["funcionario_id"] ?? 0);
  if ($funcionarioId <= 0) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Pedido sem funcionario_id (não dá para gerar ficha)."]);
    exit;
  }

  // 1) marca pedido como entregue
  $stmtU = $pdo->prepare("
    UPDATE epi_pedidos
    SET status = 'ENTREGUE',
        entregue_por = ?,
        entregue_em = NOW()
    WHERE id = ? AND status = 'PENDENTE_ALMOX'
  ");
  $stmtU->execute([$entreguePor, $id]);

  if ($stmtU->rowCount() === 0) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(["ok" => false, "error" => "Pedido não está pendente ou já foi entregue."]);
    exit;
  }

  // 2) grava no histórico (ficha)
  $stmtI = $pdo->prepare("
    INSERT INTO epi_entregas (funcionario_id, pedido_id, epi, qtd, entregue_em, entregue_por, obs)
    VALUES (?, ?, ?, ?, NOW(), ?, ?)
  ");
  $stmtI->execute([
    $funcionarioId,
    (int)$pedido["id"],
    (string)$pedido["epi"],
    (int)$pedido["qtd"],
    $entreguePor,
    (string)($pedido["obs"] ?? "")
  ]);

  $pdo->commit();

  echo json_encode(["ok" => true]);
  exit;

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro no servidor."]);
  exit;
}