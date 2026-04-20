<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php"; // Para usar json_out se existir

header("Content-Type: application/json; charset=utf-8");
$pdo = db();

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "JSON inválido."]);
  exit;
}

// Alinhando os nomes com o seu formulário HTML
$id = trim((string)($input["id"] ?? "")); // No HTML estava name="id"
$nome_requerente = trim((string)($input["nome_requerente"] ?? ""));
$data_pedido     = trim((string)($input["data_pedido"] ?? ""));

// Aceita tanto solicitante_nome quanto Nome (para evitar erros)
$solicitanteNome = trim((string)($input["solicitante_nome"] ?? $input["Nome"] ?? ""));

$item_solicitado      = trim((string)($input["item_solicitado"] ?? $input["Iten_solicitado"] ?? ""));
$descricao_item       = trim((string)($input["descricao_item"] ?? $input["descricao_iten"] ?? ""));
$justificativa_compra = trim((string)($input["justificativa_compra"] ?? ""));
$condicoes_pagamento  = trim((string)($input["condicoes_pagamento"] ?? ""));
$prazo_entrega        = trim((string)($input["prazo_entrega"] ?? ""));
$fornecedor           = trim((string)($input["fornecedor"] ?? ""));

// Validação idêntica à sua, mas com os nomes corrigidos
if ($nome_requerente === "" || $solicitanteNome === "" || $data_pedido === "") {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Preencha os campos obrigatórios (setor, data e solicitante)."]);
  exit;
}

try {
  if ($id === "") {
    // INSERT
    $stmt = $pdo->prepare("
      INSERT INTO compras_ordens
        (nome_requerente, data_pedido, solicitante_nome,
         item_solicitado, descricao_item, justificativa_compra, condicoes_pagamento,
         prazo_entrega, fornecedor)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
      $nome_requerente, $data_pedido, $solicitanteNome,
      $item_solicitado ?: null, $descricao_item ?: null, $justificativa_compra ?: null,
      $condicoes_pagamento ?: null, $prazo_entrega ?: null, $fornecedor ?: null
    ]);

    $newId = (int)$pdo->lastInsertId();
    $ano = substr($data_pedido, 0, 4);
    $numeroInterno = sprintf("OC-%s-%06d", $ano, $newId);

    // Atualiza com o número gerado
    $pdo->prepare("UPDATE compras_ordens SET numero_interno = ? WHERE id = ?")
        ->execute([$numeroInterno, $newId]);

    echo json_encode(["ok" => true, "id" => $newId, "numero_interno" => $numeroInterno]);
  } else {
    // UPDATE
    $stmt = $pdo->prepare("
      UPDATE compras_ordens
      SET nome_requerente = ?, data_pedido = ?, solicitante_nome = ?,
          item_solicitado = ?, descricao_item = ?, justificativa_compra = ?,
          condicoes_pagamento = ?, prazo_entrega = ?, fornecedor = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $nome_requerente, $data_pedido, $solicitanteNome,
      $item_solicitado ?: null, $descricao_item ?: null, $justificativa_compra ?: null,
      $condicoes_pagamento ?: null, $prazo_entrega ?: null, $fornecedor ?: null,
      (int)$id
    ]);

    echo json_encode(["ok" => true, "id" => (int)$id]);
  }
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
