<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$pdo = db();

$in = json_decode(file_get_contents("php://input"), true);
if (!$in || !isset($in["tipo"]) || !isset($in["itens"]) || !is_array($in["itens"])) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Payload inválido."]);
  exit;
}

$tipo = (string)$in["tipo"];
if (!in_array($tipo, ["pagar", "receber"], true)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Tipo inválido."]);
  exit;
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    INSERT INTO fin_lancamentos (tipo, status, pessoa, descricao, valor, data_prevista, criado_por)
    VALUES (?, 'aberto', ?, ?, ?, ?, ?)
  ");

  $audit = $pdo->prepare("
    INSERT INTO fin_auditoria (lancamento_id, acao, usuario, antes_json, depois_json)
    VALUES (?, 'criar', ?, NULL, ?)
  ");

  $ids = [];

  foreach ($in["itens"] as $it) {
    $pessoa = trim((string)($it["pessoa"] ?? ""));
    $descricao = trim((string)($it["descricao"] ?? ""));
    $valor = (float)($it["valor"] ?? 0);
    $dataPrev = (string)($it["data_prevista"] ?? "");

    if ($pessoa === "" || $valor <= 0 || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $dataPrev)) {
      throw new Exception("Item inválido: preencha Pessoa/Valor/Data prevista.");
    }

    $stmt->execute([$tipo, $pessoa, ($descricao === "" ? null : $descricao), $valor, $dataPrev, $userName]);
    $id = (int)$pdo->lastInsertId();
    $ids[] = $id;

    $after = [
      "id" => $id,
      "tipo" => $tipo,
      "status" => "aberto",
      "pessoa" => $pessoa,
      "descricao" => ($descricao === "" ? null : $descricao),
      "valor" => $valor,
      "data_prevista" => $dataPrev
    ];

    $audit->execute([$id, $userName, json_encode($after, JSON_UNESCAPED_UNICODE)]);
  }

  $pdo->commit();
  echo json_encode(["ok" => true, "ids" => $ids]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}