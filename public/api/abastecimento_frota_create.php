<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$u = current_user();
if (!$u) {
  http_response_code(401);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(["ok" => false, "error" => "Não autenticado"]);
  exit;
}

header("Content-Type: application/json; charset=utf-8");

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(["ok" => false, "error" => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$input = $_POST;
if (!$input) {
  $raw = file_get_contents("php://input");
  $json = json_decode($raw ?: "{}", true);
  if (is_array($json)) $input = $json;
}

$frentista = trim((string)($input["frentista"] ?? ""));
$data_hora = trim((string)($input["data_hora"] ?? ""));
$frota_id  = (int)($input["frota_id"] ?? 0);
$operador  = trim((string)($input["operador"] ?? ""));
$horimetro = trim((string)($input["horimetro"] ?? ""));
$litros    = trim((string)($input["litros"] ?? ""));

$frentistasValidos = ["Cesar","Marcos","Jonas","Thomaz","Alexandre"];

if ($frentista === "" || !in_array($frentista, $frentistasValidos, true)) bad("Frentista inválido.");
if ($data_hora === "") bad("Data é obrigatória.");
if ($frota_id <= 0) bad("Equipamento é obrigatório.");
if ($operador === "") bad("Motorista/Operador é obrigatório.");
if ($horimetro === "" || !is_numeric($horimetro)) bad("Horímetro inválido.");
if ($litros === "" || !is_numeric($litros)) bad("Litros inválido.");

$horimetroVal = (float)$horimetro;
$litrosVal    = (float)$litros;

if ($horimetroVal < 0) bad("Horímetro não pode ser negativo.");
if ($litrosVal <= 0) bad("Litros deve ser maior que zero.");

try {
  $pdo = db();

  // garante que a frota existe
  $st = $pdo->prepare("SELECT id FROM oficina_frota WHERE id = :id LIMIT 1");
  $st->execute([":id" => $frota_id]);
  if (!$st->fetchColumn()) bad("Equipamento não encontrado.");

  $sql = "INSERT INTO frota_abastecimentos
            (frentista, data_hora, frota_id, operador, horimetro, litros)
          VALUES
            (:frentista, :data_hora, :frota_id, :operador, :horimetro, :litros)";
  $st = $pdo->prepare($sql);
  $st->execute([
    ":frentista" => $frentista,
    ":data_hora" => $data_hora,
    ":frota_id"  => $frota_id,
    ":operador"  => $operador,
    ":horimetro" => $horimetroVal,
    ":litros"    => $litrosVal
  ]);

  echo json_encode(["ok" => true, "id" => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Erro ao salvar abastecimento"]);
}