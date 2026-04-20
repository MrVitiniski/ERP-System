<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

ini_set("display_errors", "0");
error_reporting(E_ALL);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
require_role(["admin", "rh"]);

function br_to_iso_date(string $s): ?string {
  $s = trim($s);
  if ($s === "") return null;
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
  if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) return "{$m[3]}-{$m[2]}-{$m[1]}";
  return null;
}

try {
  $raw = file_get_contents("php://input") ?: "";
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = [];

  $nome = trim((string)($data["nome_completo"] ?? ""));
  $cpf  = trim((string)($data["cpf"] ?? ""));

  $nasc = br_to_iso_date((string)($data["data_nascimento"] ?? ""));
  $adm  = br_to_iso_date((string)($data["data_admissao"] ?? ""));

  if ($nome === "" || $cpf === "" || !$nasc || !$adm) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "missing_required_fields"]);
    exit;
  }

  // CPF: somente dígitos e 11 chars
  $cpfDigits = preg_replace('/\D+/', '', $cpf) ?? "";
  if (strlen($cpfDigits) !== 11) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "invalid_cpf"]);
    exit;
  }

  $status = mb_strtolower(trim((string)($data["status"] ?? "ativo")), "UTF-8");
  if (!in_array($status, ["ativo", "inativo"], true)) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "invalid_status"]);
    exit;
  }

  $salario = $data["salario"] ?? null;
  if ($salario === "" || $salario === false) $salario = null;
  if ($salario !== null && !is_numeric($salario)) {
    http_response_code(422);
    echo json_encode(["ok" => false, "error" => "invalid_salario"]);
    exit;
  }

  $payload = [
    "nome_completo" => $nome,
    "cpf" => $cpfDigits,
    "data_nascimento" => $nasc,
    "telefone" => trim((string)($data["telefone"] ?? "")) ?: null,
    "email" => trim((string)($data["email"] ?? "")) ?: null,

    "endereco_linha" => trim((string)($data["endereco_linha"] ?? "")) ?: null,
    "endereco_cidade" => trim((string)($data["endereco_cidade"] ?? "")) ?: null,
    "endereco_uf" => strtoupper(trim((string)($data["endereco_uf"] ?? ""))) ?: null,
    "endereco_cep" => trim((string)($data["endereco_cep"] ?? "")) ?: null,

    "cargo" => trim((string)($data["cargo"] ?? "")) ?: null,
    "setor" => trim((string)($data["setor"] ?? "")) ?: null,

    "data_admissao" => $adm,
    "salario" => $salario !== null ? (float)$salario : null,

    "banco_nome" => trim((string)($data["banco_nome"] ?? "")) ?: null,
    "pix_chave" => trim((string)($data["pix_chave"] ?? "")) ?: null,
    "banco_agencia" => trim((string)($data["banco_agencia"] ?? "")) ?: null,
    "banco_conta" => trim((string)($data["banco_conta"] ?? "")) ?: null,

    "emprestimo_folha" => !empty($data["emprestimo_folha"]) ? 1 : 0,
    "pensao_folha" => !empty($data["pensao_folha"]) ? 1 : 0,

    "status" => $status,
  ];

  $pdo = db();

  // evitar duplicar CPF
  $stmt = $pdo->prepare("SELECT id FROM funcionarios WHERE cpf = ? LIMIT 1");
  $stmt->execute([$payload["cpf"]]);
  if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(["ok" => false, "error" => "cpf_already_exists"]);
    exit;
  }

  $stmt = $pdo->prepare("
    INSERT INTO funcionarios (
      nome_completo, cpf, data_nascimento, telefone, email,
      endereco_linha, endereco_cidade, endereco_uf, endereco_cep,
      cargo, setor, data_admissao, salario,
      banco_nome, pix_chave, banco_agencia, banco_conta,
      emprestimo_folha, pensao_folha, status
    ) VALUES (
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?, ?,
      ?, ?, ?
    )
  ");

  $stmt->execute([
    $payload["nome_completo"], $payload["cpf"], $payload["data_nascimento"], $payload["telefone"], $payload["email"],
    $payload["endereco_linha"], $payload["endereco_cidade"], $payload["endereco_uf"], $payload["endereco_cep"],
    $payload["cargo"], $payload["setor"], $payload["data_admissao"], $payload["salario"],
    $payload["banco_nome"], $payload["pix_chave"], $payload["banco_agencia"], $payload["banco_conta"],
    $payload["emprestimo_folha"], $payload["pensao_folha"], $payload["status"],
  ]);

  $id = (int)$pdo->lastInsertId();

  echo json_encode(["ok" => true, "id" => $id]);
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  // DEV: mostre a mensagem real pra corrigir schema/SQL se precisar
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
  exit;
}