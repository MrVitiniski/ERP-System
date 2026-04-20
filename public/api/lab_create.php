<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$me = current_user();
$role = (string)($me["role"] ?? "");
if (!$me || ($role !== "admin" && $role !== "laboratorio")) {
  http_response_code(403);
  json_out(["ok" => false, "error" => "forbidden"]);
}

// Configura o fuso horário (ajuste para sua região se necessário)
date_default_timezone_set('America/Sao_Paulo');

$body = read_json();

$tipo = trim((string)($body["tipo_amostra"] ?? ""));
$dataProd = (string)($body["data_producao"] ?? "");

if ($tipo === "" || $dataProd === "") {
  http_response_code(400);
  json_out(["ok" => false, "error" => "Dados obrigatórios: tipo_amostra, data_producao"]);
}

$tag = trim((string)($body["tag"] ?? ""));
$ident = trim((string)($body["identificacao"] ?? ""));
$com = (int)($body["com_alumina"] ?? 0);

// GERAÇÃO AUTOMÁTICA DA HORA
$horaCriacao = date('H:i:s');
$body['hora_criacao'] = $horaCriacao; // Adiciona ao JSON de dados para ficar registrado

$pdo = db();

// Prepara o SQL (Certifique-se que seu banco tenha a coluna 'hora_criacao' ou ela será salva dentro da coluna 'dados')
$stmt = $pdo->prepare("INSERT INTO lab_reports (tipo_amostra, tag, data_producao, identificacao, com_alumina, dados)
                       VALUES (?, ?, ?, ?, ?, ?)");

$dadosJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$stmt->execute([$tipo, ($tag === "" ? null : $tag), $dataProd, ($ident === "" ? null : $ident), $com ? 1 : 0, $dadosJson]);

// CAPTURA O ID AUTOMÁTICO GERADO PELO BANCO
$idGerado = (int)$pdo->lastInsertId();

// RETORNA O ID E A HORA PARA O FRONTEND
json_out([
    "ok" => true, 
    "id" => $idGerado, 
    "hora" => $horaCriacao,
    "mensagem" => "Laudo N° $idGerado gerado com sucesso às $horaCriacao"
]);
