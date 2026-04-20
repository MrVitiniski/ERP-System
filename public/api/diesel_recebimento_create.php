<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$u = current_user();
if (!$u) {
  http_response_code(401);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(["ok" => false, "error" => "Não autenticado"], JSON_UNESCAPED_UNICODE);
  exit;
}

header("Content-Type: application/json; charset=utf-8");

try {
  $raw = file_get_contents("php://input");
  $in = json_decode($raw ?: "{}", true);
  if (!is_array($in)) $in = [];

  $data_hora = trim((string)($in["data_hora"] ?? ""));
  $litros = (float)($in["litros"] ?? 0);

  // campos novos
  $fornecedor = trim((string)($in["fornecedor"] ?? ""));
  $nf = trim((string)($in["nf"] ?? ""));
  $motorista = trim((string)($in["motorista"] ?? ""));
  $placa_caminhao = trim((string)($in["placa_caminhao"] ?? ""));
  $obs = trim((string)($in["obs"] ?? ""));

  if ($data_hora === "") throw new Exception("Informe a data.");
  if ($litros <= 0) throw new Exception("Informe os litros recebidos.");

  // garante "YYYY-MM-DD HH:MM:SS"
  if (str_contains($data_hora, "T")) $data_hora = str_replace("T", " ", $data_hora);
  if (strlen($data_hora) === 16) $data_hora .= ":00";

  // tenta pegar o usuário logado (se existir)
  $usuario = null;
  if (is_array($u)) {
    $usuario = $u["nome"] ?? $u["user"] ?? $u["username"] ?? $u["login"] ?? null;
  } elseif (is_string($u)) {
    $usuario = $u;
  }

  $pdo = db();

  // mantemos empresa por compatibilidade (preenche com fornecedor)
  $empresa = ($fornecedor !== "") ? $fornecedor : null;

  $sql = "INSERT INTO diesel_recebimentos
            (empresa, fornecedor, nf, motorista, placa_caminhao, obs, litros, data_hora, usuario)
          VALUES
            (:empresa, :fornecedor, :nf, :motorista, :placa_caminhao, :obs, :litros, :data_hora, :usuario)";
  $st = $pdo->prepare($sql);
  $st->execute([
    ":empresa" => $empresa,
    ":fornecedor" => $fornecedor !== "" ? $fornecedor : null,
    ":nf" => $nf !== "" ? $nf : null,
    ":motorista" => $motorista !== "" ? $motorista : null,
    ":placa_caminhao" => $placa_caminhao !== "" ? $placa_caminhao : null,
    ":obs" => $obs !== "" ? $obs : null,
    ":litros" => $litros,
    ":data_hora" => $data_hora,
    ":usuario" => $usuario,
  ]);

  echo json_encode(["ok" => true, "id" => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Erro ao salvar recebimento", "debug" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}