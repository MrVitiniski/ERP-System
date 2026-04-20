<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";

$pdo = db();

$de = $_GET["de"] ?? "";
$ate = $_GET["ate"] ?? "";

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $de) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $ate)) {
  http_response_code(400);
  echo "Parâmetros inválidos. Use ?de=YYYY-MM-DD&ate=YYYY-MM-DD";
  exit;
}

$hoje = date("Y-m-d");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
function money($v){ return number_format((float)$v, 2, ",", "."); }

// Totais no período (quitados por data_quitacao)
$stmtPago = $pdo->prepare("
  SELECT COALESCE(SUM(valor),0) AS total
  FROM fin_lancamentos
  WHERE tipo='pagar' AND status='quitado' AND data_quitacao BETWEEN ? AND ?
");
$stmtPago->execute([$de, $ate]);
$totalPago = (float)($stmtPago->fetch()["total"] ?? 0);

$stmtRec = $pdo->prepare("
  SELECT COALESCE(SUM(valor),0) AS total
  FROM fin_lancamentos
  WHERE tipo='receber' AND status='quitado' AND data_quitacao BETWEEN ? AND ?
");
$stmtRec->execute([$de, $ate]);
$totalRec = (float)($stmtRec->fetch()["total"] ?? 0);

// Quitados (no período) + quem quitou (auditoria)
$stmtQuit = $pdo->prepare("
  SELECT
    l.id, l.tipo, l.pessoa, l.descricao, l.valor,
    l.data_prevista, l.data_quitacao,
    l.criado_por,
    (
      SELECT a.usuario
      FROM fin_auditoria a
      WHERE a.lancamento_id = l.id AND a.acao = 'quitar'
      ORDER BY a.id DESC
      LIMIT 1
    ) AS quitado_por
  FROM fin_lancamentos l
  WHERE l.status='quitado' AND l.data_quitacao BETWEEN ? AND ?
  ORDER BY l.data_quitacao ASC, l.id DESC
");
$stmtQuit->execute([$de, $ate]);
$quitados = $stmtQuit->fetchAll();

// Abertos/vencidos (por data_prevista) + quem criou
$stmtAbertos = $pdo->prepare("
  SELECT id, tipo, pessoa, descricao, valor, data_prevista, criado_por, criado_em
  FROM fin_lancamentos
  WHERE status='aberto' AND data_prevista BETWEEN ? AND ?
  ORDER BY data_prevista ASC, id DESC
");
$stmtAbertos->execute([$de, $ate]);
$abertos = $stmtAbertos->fetchAll();

header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8"/>
  <title>Relatório Financeiro (<?=h($de)?> a <?=h($ate)?>)</title>
  <style>
    body{ font-family: Arial, sans-serif; margin: 24px; color:#111; }
    .top{ display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    h1{ margin:0 0 6px 0; font-size:18px; }
    .muted{ color:#555; font-size:12px; }
    .cards{ display:flex; gap:12px; margin: 12px 0 16px; flex-wrap:wrap; }
    .card{ border:1px solid #ddd; border-radius:10px; padding:10px 12px; min-width:220px; }
    .card .t{ font-size:12px; color:#555; }
    .card .v{ font-size:16px; font-weight:700; margin-top:4px; }

    table{ width:100%; border-collapse:collapse; margin-top:10px; }
    th,td{ border-bottom:1px solid #e5e5e5; padding:8px 6px; font-size:12px; text-align:left; vertical-align:top; }
    th{ background:#f6f6f6; position:sticky; top:0; }
    .vencido{ color:#b91c1c; font-weight:700; }
    .right{ text-align:right; }

    .actions{ margin-top: 8px; }
    button{ padding:8px 10px; border:1px solid #ccc; background:#fff; border-radius:8px; cursor:pointer; }
    button:hover{ background:#f3f3f3; }

    @media print{
      .actions{ display:none; }
      body{ margin: 10mm; }
      th{ position:static; }
    }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1>Relatório Financeiro (Auditoria)</h1>
      <div class="muted">Período: <?=h($de)?> até <?=h($ate)?> • Gerado em: <?=h(date("Y-m-d H:i"))?></div>
    </div>
    <div class="actions">
      <button onclick="window.print()">Imprimir / Salvar em PDF</button>
    </div>
  </div>

  <div class="cards">
    <div class="card">
      <div class="t">Total recebido (quitado no período)</div>
      <div class="v">R$ <?=h(money($totalRec))?></div>
    </div>
    <div class="card">
      <div class="t">Total pago (quitado no período)</div>
      <div class="v">R$ <?=h(money($totalPago))?></div>
    </div>
    <div class="card">
      <div class="t">Resultado (recebido - pago)</div>
      <div class="v">R$ <?=h(money($totalRec - $totalPago))?></div>
    </div>
  </div>

  <h2 style="font-size:14px;margin:14px 0 6px;">Lançamentos quitados</h2>
  <table>
    <thead>
      <tr>
        <th>Quitação</th>
        <th>Tipo</th>
        <th>Pessoa</th>
        <th>Descrição</th>
        <th class="right">Valor</th>
        <th>Previsto</th>
        <th>Criado por</th>
        <th>Quitado por</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quitados as $it): ?>
        <tr>
          <td><?=h($it["data_quitacao"])?></td>
          <td><?=h($it["tipo"] === "pagar" ? "Pagar" : "Receber")?></td>
          <td><?=h($it["pessoa"])?></td>
          <td><?=h($it["descricao"] ?? "")?></td>
          <td class="right">R$ <?=h(money($it["valor"]))?></td>
          <td><?=h($it["data_prevista"])?></td>
          <td><?=h($it["criado_por"] ?? "")?></td>
          <td><?=h($it["quitado_por"] ?? "")?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$quitados): ?>
        <tr><td colspan="8" class="muted">Sem lançamentos quitados no período.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h2 style="font-size:14px;margin:16px 0 6px;">Lançamentos em aberto (no período)</h2>
  <table>
    <thead>
      <tr>
        <th>Previsto</th>
        <th>Tipo</th>
        <th>Pessoa</th>
        <th>Descrição</th>
        <th class="right">Valor</th>
        <th>Status</th>
        <th>Criado por</th>
        <th>Criado em</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($abertos as $it):
        $vencido = ($it["data_prevista"] < $hoje);
      ?>
        <tr>
          <td class="<?= $vencido ? "vencido" : "" ?>"><?=h($it["data_prevista"])?></td>
          <td><?=h($it["tipo"] === "pagar" ? "Pagar" : "Receber")?></td>
          <td><?=h($it["pessoa"])?></td>
          <td><?=h($it["descricao"] ?? "")?></td>
          <td class="right">R$ <?=h(money($it["valor"]))?></td>
          <td class="<?= $vencido ? "vencido" : "" ?>"><?= $vencido ? "Vencido" : "Aberto" ?></td>
          <td><?=h($it["criado_por"] ?? "")?></td>
          <td><?=h($it["criado_em"] ?? "")?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$abertos): ?>
        <tr><td colspan="8" class="muted">Sem lançamentos em aberto no período.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>