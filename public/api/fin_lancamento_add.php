<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

$in = json_decode(file_get_contents("php://input"), true);

$tipo = (string)($in["tipo"] ?? "pagar"); // pagar/receber
$pessoa = trim((string)($in["pessoa"] ?? ""));
$descricao = trim((string)($in["descricao"] ?? ""));
$valor = (float)($in["valor"] ?? 0);

$dataInformada = (string)($in["data_prevista"] ?? ""); // usado se base_data=vencimento
$dias = $in["dias"] ?? []; // ex: [0] ou [30,60,90,120]

$valorModo = (string)($in["valor_modo"] ?? "por_parcela"); // por_parcela | total_dividir
$baseData = (string)($in["base_data"] ?? "vencimento"); // vencimento | hoje

if (!in_array($tipo, ["pagar","receber"], true)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Tipo inválido"]); exit;
}
if ($pessoa === "" || $valor <= 0) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Preencha fornecedor e valor"]); exit;
}
if (!in_array($valorModo, ["por_parcela","total_dividir"], true)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Modo de valor inválido"]); exit;
}
if (!in_array($baseData, ["vencimento","hoje"], true)) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Base de data inválida"]); exit;
}

if (!is_array($dias) || count($dias) === 0) $dias = [0];
$dias = array_values(array_unique(array_map(fn($d)=> (int)$d, $dias)));
sort($dias);
if ($dias[0] < 0) {
  http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Dias inválidos"]); exit;
}

if ($baseData === "vencimento") {
  if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dataInformada)) {
    http_response_code(400); echo json_encode(["ok"=>false,"error"=>"Informe a data do 1º vencimento"]); exit;
  }
  $dataBase = $dataInformada;
} else {
  $dataBase = date("Y-m-d"); // base = hoje
}

$userName = $_SESSION["user"]["name"] ?? ($_SESSION["name"] ?? "desconhecido");

function uuidv4(): string {
  $data = random_bytes(16);
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
  $pdo->beginTransaction();

  $totalParcelas = count($dias);
  $grupoId = ($totalParcelas > 1) ? uuidv4() : null;

  $valorParcela = $valor;
  if ($valorModo === "total_dividir" && $totalParcelas > 0) {
    $valorParcela = round($valor / $totalParcelas, 2);
  }

  $stmt = $pdo->prepare("
    INSERT INTO fin_lancamentos
      (grupo_id, parcela_num, parcela_total, tipo, status, pessoa, descricao, valor, data_prevista, criado_por)
    VALUES
      (?, ?, ?, ?, 'aberto', ?, ?, ?, ?, ?)
  ");

  $aud = $pdo->prepare("
    INSERT INTO fin_auditoria (lancamento_id, acao, usuario, antes_json, depois_json)
    VALUES (?, 'criar', ?, NULL, ?)
  ");

  $ids = [];

  foreach ($dias as $i => $offset) {
    $num = $i + 1;
    $parcelaNum = ($totalParcelas > 1) ? $num : null;
    $parcelaTotal = ($totalParcelas > 1) ? $totalParcelas : null;

    $dt = new DateTime($dataBase);
    if ($offset !== 0) $dt->modify("+{$offset} days");
    $venc = $dt->format("Y-m-d");

    $stmt->execute([
      $grupoId,
      $parcelaNum,
      $parcelaTotal,
      $tipo,
      $pessoa,
      ($descricao === "" ? null : $descricao),
      $valorParcela,
      $venc,
      $userName
    ]);

    $id = (int)$pdo->lastInsertId();
    $ids[] = $id;

    $after = [
      "id"=>$id,
      "grupo_id"=>$grupoId,
      "parcela_num"=>$parcelaNum,
      "parcela_total"=>$parcelaTotal,
      "tipo"=>$tipo,
      "status"=>"aberto",
      "pessoa"=>$pessoa,
      "descricao"=>($descricao===""?null:$descricao),
      "valor"=>$valorParcela,
      "data_prevista"=>$venc,
      "valor_modo"=>$valorModo,
      "base_data"=>$baseData,
      "dias"=>$dias
    ];
    $aud->execute([$id, $userName, json_encode($after, JSON_UNESCAPED_UNICODE)]);
  }

  $pdo->commit();
  echo json_encode([
    "ok"=>true,
    "ids"=>$ids,
    "grupo_id"=>$grupoId,
    "valor_parcela"=>$valorParcela
  ]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(["ok"=>false, "error"=>$e->getMessage()]);
}