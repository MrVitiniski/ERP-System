<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

$in = json_decode(file_get_contents("php://input"), true);
if (!$in) { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Payload inválido"]); exit; }

$tipo = (string)($in["tipo"] ?? "pagar");
$pessoa = trim((string)($in["pessoa"] ?? ""));
$descricao = trim((string)($in["descricao"] ?? ""));
$parcelas = $in["parcelas"] ?? [];

if ($pessoa === "" || !is_array($parcelas) || count($parcelas) < 1) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Fornecedor e parcelas são obrigatórios."]);
  exit;
}

function uuidv4(): string {
  $data = random_bytes(16);
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

try{
  $pdo->beginTransaction();

  $total = count($parcelas);
  $grupoId = ($total > 1) ? uuidv4() : null;

  $ins = $pdo->prepare("
    INSERT INTO fin_lancamentos
      (grupo_id, parcela_num, parcela_total, tipo, status, pessoa, descricao, valor, data_prevista, criado_por)
    VALUES
      (?, ?, ?, ?, 'aberto', ?, ?, ?, ?, ?)
  ");

  $ids = [];
  foreach ($parcelas as $i => $p) {
    $v = (float)($p["valor"] ?? 0);
    $d = (string)($p["data_prevista"] ?? "");

    if ($v <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
      throw new Exception("Parcela inválida. Preencha data e valor.");
    }

    $num = $i + 1;
    $ins->execute([
      $grupoId,
      ($total > 1 ? $num : null),
      ($total > 1 ? $total : null),
      $tipo,
      $pessoa,
      ($descricao === "" ? null : $descricao),
      $v,
      $d,
      $userName
    ]);

    $ids[] = (int)$pdo->lastInsertId();
  }

  $pdo->commit();
  echo json_encode(["ok"=>true, "ids"=>$ids, "grupo_id"=>$grupoId]);
}catch(Throwable $e){
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>$e->getMessage()]);
}