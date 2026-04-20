<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";
header("Content-Type: application/json; charset=utf-8");

$pdo = db();

$dia = (string)($_GET["dia"] ?? "");
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dia)) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"Informe dia em YYYY-MM-DD."]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT
    id, tipo, pessoa, descricao, valor,
    data_quitacao, quitado_por
  FROM fin_lancamentos
  WHERE status='quitado'
    AND data_quitacao = ?
  ORDER BY id DESC
");
$stmt->execute([$dia]);

echo json_encode(["ok"=>true, "items"=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);