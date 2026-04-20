<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "sst"]); // SST ou Admin

$raw = file_get_contents("php://input") ?: "";
$in = json_decode($raw, true);
if (!is_array($in)) $in = [];

// NOVO: funcionario_id vem do SST (select/autocomplete)
$funcionarioId = (int)($in["funcionario_id"] ?? 0);

$colaborador = trim((string)($in["colaborador"] ?? "")); // opcional: nome pra histórico
$setor       = trim((string)($in["setor"] ?? ""));
$epi         = trim((string)($in["epi"] ?? ""));
$qtd         = (int)($in["qtd"] ?? 0);
$dataPedido  = trim((string)($in["data"] ?? ""));
$obs         = trim((string)($in["obs"] ?? ""));

if ($funcionarioId <= 0 || $epi === "" || $qtd < 1 || $dataPedido === "") {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Selecione o funcionário e preencha EPI, quantidade e data."]);
  exit;
}

try {
  $pdo = db();

  // opcional: valida se funcionario existe e já pega nome/setor oficial
  $stmtF = $pdo->prepare("SELECT id, nome_completo, setor FROM funcionarios WHERE id = ? LIMIT 1");
  $stmtF->execute([$funcionarioId]);
  $func = $stmtF->fetch(PDO::FETCH_ASSOC);

  if (!$func) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Funcionário não encontrado."]);
    exit;
  }

  // se não vier colaborador/setor do front, usa o do cadastro
  if ($colaborador === "") $colaborador = (string)$func["nome_completo"];
  if ($setor === "") $setor = (string)($func["setor"] ?? "");

  $criadoPor = $_SESSION["user"]["id"] ?? null;

  $sql = "
    INSERT INTO epi_pedidos
      (funcionario_id, colaborador, setor, epi, qtd, data_pedido, obs, status, criado_por, created_at)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, 'PENDENTE_ALMOX', ?, NOW())
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$funcionarioId, $colaborador, $setor, $epi, $qtd, $dataPedido, $obs, $criadoPor]);

  echo json_encode(["ok" => true, "pedido_id" => (int)$pdo->lastInsertId()]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro no servidor."]);
  exit;
}