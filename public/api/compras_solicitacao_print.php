<?php
require_once __DIR__ . "/../includes/db.php";

$pdo = db();

$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) {
  http_response_code(400);
  echo "ID inválido.";
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT * FROM compras_ordens WHERE id = ?");
  $stmt->execute([(int)$id]);
  $s = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$s) {
    http_response_code(404);
    echo "Ordem não encontrada.";
    exit;
  }

  function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
} catch (Throwable $e) {
  http_response_code(400);
  echo "Erro: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
  exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h($s["numero_interno"] ?? ("OC #" . $s["id"])) ?></title>
  <style>
    body{ font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; margin:24px; color:#0f172a; }
    .no-print{ display:flex; gap:8px; margin-bottom:12px; }
    .btn{ border:1px solid #e5e7eb; background:#fff; padding:10px 12px; border-radius:10px; font-weight:800; cursor:pointer; }
    .box{ border:1px solid #e5e7eb; border-radius:14px; padding:16px; }
    h1{ margin:0 0 12px; font-size:18px; }
    .grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px 16px; }
    .field{ padding:10px 12px; border:1px solid #e5e7eb; border-radius:12px; background:#fff; }
    .label{ font-size:12px; font-weight:800; color:#64748b; }
    .value{ margin-top:6px; font-weight:700; white-space:pre-wrap; }
    .full{ grid-column:1 / -1; }
    @media print { .no-print{ display:none; } body{ margin:0; } }
    @media (max-width: 860px){ .grid{ grid-template-columns:1fr; } }
  </style>
</head>
<body>
  <div class="no-print">
    <button class="btn" onclick="window.print()">Imprimir</button>
    <button class="btn" onclick="window.close()">Fechar</button>
  </div>

  <div class="box">
    <h1>Ordem de Compra: <?= h($s["numero_interno"] ?? ("#" . $s["id"])) ?></h1>

    <div class="grid">
      <div class="field">
        <div class="label">Setor/Requerente</div>
        <div class="value"><?= h($s["nome_requerente"] ?? "") ?></div>
      </div>
      <div class="field">
        <div class="label">Data da Solicitação</div>
        <div class="value"><?= h($s["data_pedido"] ?? "") ?></div>
      </div>

      <div class="field full">
        <div class="label">Nome do Solicitante</div>
        <div class="value"><?= h($s["solicitante_nome"] ?? "") ?></div>
      </div>

      <div class="field">
        <div class="label">Item Solicitado</div>
        <div class="value"><?= h($s["item_solicitado"] ?? "") ?></div>
      </div>
      <div class="field">
        <div class="label">Descrição</div>
        <div class="value"><?= h($s["descricao_item"] ?? "") ?></div>
      </div>

      <div class="field full">
        <div class="label">Justificativa da Compra</div>
        <div class="value"><?= h($s["justificativa_compra"] ?? "") ?></div>
      </div>

      <div class="field">
        <div class="label">Valor/Condições de pagamento</div>
        <div class="value"><?= h($s["condicoes_pagamento"] ?? "") ?></div>
      </div>
      <div class="field">
        <div class="label">Prazo de Entrega</div>
        <div class="value"><?= h($s["prazo_entrega"] ?? "") ?></div>
      </div>

      <div class="field full">
        <div class="label">Fornecedor</div>
        <div class="value"><?= h($s["fornecedor"] ?? "") ?></div>
      </div>

      <div class="field full">
        <div class="label">Criado em</div>
        <div class="value"><?= h($s["criado_em"] ?? "") ?></div>
      </div>
    </div>
  </div>

  <script>
    setTimeout(() => window.print(), 250);
  </script>
</body>
</html>