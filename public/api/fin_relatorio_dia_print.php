<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/db.php";

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$pdo = db();

$data = $_GET["data"] ?? "";
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data)) {
  http_response_code(400);
  echo "Parâmetro inválido. Use ?data=YYYY-MM-DD";
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
function money($v){ return number_format((float)$v, 2, ",", "."); }

// Conta fixa (SCAVARE MINERAÇÃO)
$conta = "SCAVARE MINERAÇÃO";

// Saldo inicial informado do dia (SCAVARE). Se não tiver, assume 0
$stmtSaldoIni = $pdo->prepare("
  SELECT saldo_bancario
  FROM fin_caixa_saldos_dia
  WHERE dia = ? AND conta = ?
  LIMIT 1
");
$stmtSaldoIni->execute([$data, $conta]);
$saldoInicial = (float)($stmtSaldoIni->fetchColumn() ?? 0);

// Quitados no dia
$stmtQuitados = $pdo->prepare("
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
  WHERE l.status='quitado' AND l.data_quitacao = ?
  ORDER BY l.tipo ASC, l.id DESC
");
$stmtQuitados->execute([$data]);
$quitados = $stmtQuitados->fetchAll(PDO::FETCH_ASSOC);

// Totais quitados no dia
$totalPago = 0.0;
$totalReceb = 0.0;
foreach ($quitados as $q) {
  if (($q["tipo"] ?? "") === "pagar") $totalPago += (float)$q["valor"];
  if (($q["tipo"] ?? "") === "receber") $totalReceb += (float)$q["valor"];
}

$resultadoDia = $totalReceb - $totalPago;
$saldoFinal = $saldoInicial + $resultadoDia;

// Abertos que vencem no dia
$stmtVencendoHoje = $pdo->prepare("
  SELECT id, tipo, pessoa, descricao, valor, data_prevista, criado_por, criado_em, parcela_num, parcela_total
  FROM fin_lancamentos
  WHERE status='aberto' AND data_prevista = ?
  ORDER BY tipo ASC, id DESC
");
$stmtVencendoHoje->execute([$data]);
$vencendoHoje = $stmtVencendoHoje->fetchAll(PDO::FETCH_ASSOC);

// Abertos vencidos até o dia (opcional útil)
$stmtVencidosAte = $pdo->prepare("
  SELECT id, tipo, pessoa, descricao, valor, data_prevista, criado_por, criado_em, parcela_num, parcela_total
  FROM fin_lancamentos
  WHERE status='aberto' AND data_prevista < ?
  ORDER BY data_prevista ASC, tipo ASC, id DESC
  LIMIT 300
");
$stmtVencidosAte->execute([$data]);
$vencidosAte = $stmtVencidosAte->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: text/html; charset=utf-8");
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8"/>
  <title>Relatório Diário (<?=h($data)?>)</title>
  <style>
    body{ font-family: Arial, sans-serif; margin: 24px; color:#111; }
    h1{ margin:0 0 6px 0; font-size:18px; }
    h2{ margin:18px 0 8px; font-size:14px; }
    .muted{ color:#555; font-size:12px; }

    .actions{ margin-top: 8px; }
    button{ padding:8px 10px; border:1px solid #ccc; background:#fff; border-radius:8px; cursor:pointer; }
    button:hover{ background:#f3f3f3; }

    .cards{ display:flex; gap:12px; margin: 12px 0 10px; flex-wrap:wrap; }
    .card{ border:1px solid #ddd; border-radius:10px; padding:10px 12px; min-width:220px; background:#fff; }
    .card .t{ font-size:12px; color:#555; }
    .card .v{ font-size:16px; font-weight:700; margin-top:4px; }

    table{ width:100%; border-collapse:collapse; margin-top:10px; }
    th,td{ border-bottom:1px solid #e5e5e5; padding:8px 6px; font-size:12px; text-align:left; vertical-align:top; }
    th{ background:#f6f6f6; }
    .right{ text-align:right; }
    .pill{
      display:inline-block; padding:2px 8px; border-radius:999px;
      border:1px solid #ddd; background:#fafafa; font-size:11px;
    }
    .bad{ color:#b91c1c; font-weight:700; }
    @media print{
      .actions{ display:none; }
      body{ margin: 10mm; }
    }
  </style>
</head>
<body>
  <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
    <div>
      <h1>Relatório Diário • <?=h($data)?></h1>
      <div class="muted">Conta/Empresa: <b><?=h($conta)?></b></div>
      <div class="muted">Gerado em: <?=h(date("Y-m-d H:i"))?></div>
    </div>
    <div class="actions">
      <button onclick="window.print()">Imprimir / Salvar em PDF</button>
    </div>
  </div>

  <div class="cards">
    <div class="card">
      <div class="t">Saldo inicial (informado)</div>
      <div class="v">R$ <?=h(money($saldoInicial))?></div>
    </div>
    <div class="card">
      <div class="t">Recebido (quitado no dia)</div>
      <div class="v">R$ <?=h(money($totalReceb))?></div>
    </div>
    <div class="card">
      <div class="t">Pago (quitado no dia)</div>
      <div class="v">R$ <?=h(money($totalPago))?></div>
    </div>
    <div class="card">
      <div class="t">Resultado do dia (recebido - pago)</div>
      <div class="v">R$ <?=h(money($resultadoDia))?></div>
    </div>
    <div class="card">
      <div class="t">Saldo final (calculado)</div>
      <div class="v">R$ <?=h(money($saldoFinal))?></div>
    </div>
  </div>

  <h2>Quitados no dia</h2>
  <table>
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Para</th>
        <th>Descrição</th>
        <th class="right">Valor</th>
        <th>Previsto</th>
        <th>Quitação</th>
        <th>Criado por</th>
        <th>Quitado por</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$quitados): ?>
        <tr><td colspan="8" class="muted">Nenhum lançamento quitado nesta data.</td></tr>
      <?php endif; ?>
      <?php foreach ($quitados as $it): ?>
        <tr>
          <td><span class="pill"><?=h(($it["tipo"] ?? "") === "pagar" ? "Pagar" : "Receber")?></span></td>
          <td><?=h($it["pessoa"])?></td>
          <td><?=h($it["descricao"] ?? "")?></td>
          <td class="right">R$ <?=h(money($it["valor"]))?></td>
          <td><?=h($it["data_prevista"])?></td>
          <td><?=h($it["data_quitacao"])?></td>
          <td><?=h($it["criado_por"] ?? "")?></td>
          <td><?=h($it["quitado_por"] ?? "")?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Abertos vencendo no dia</h2>
  <table>
    <thead>
      <tr>
        <th>Tipo</th>
        <th>Para</th>
        <th>Descrição</th>
        <th class="right">Valor</th>
        <th>Vencimento</th>
        <th>Parcela</th>
        <th>Criado por</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$vencendoHoje): ?>
        <tr><td colspan="7" class="muted">Nenhum lançamento em aberto vencendo nesta data.</td></tr>
      <?php endif; ?>
      <?php foreach ($vencendoHoje as $it):
        $parc = (($it["parcela_num"] ?? 0) && ($it["parcela_total"] ?? 0)) ? ($it["parcela_num"]."/".$it["parcela_total"]) : "—";
      ?>
        <tr>
          <td><span class="pill"><?=h(($it["tipo"] ?? "") === "pagar" ? "Pagar" : "Receber")?></span></td>
          <td><?=h($it["pessoa"])?></td>
          <td><?=h($it["descricao"] ?? "")?></td>
          <td class="right">R$ <?=h(money($it["valor"]))?></td>
          <td><?=h($it["data_prevista"])?></td>
          <td><?=h($parc)?></td>
          <td><?=h($it["criado_por"] ?? "")?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Abertos vencidos até <?=h($data)?></h2>
  <div class="muted">Lista limitada a 300 registros (para não ficar pesado na impressão).</div>
  <table>
    <thead>
      <tr>
        <th>Vencimento</th>
        <th>Tipo</th>
        <th>Para</th>
        <th>Descrição</th>
        <th class="right">Valor</th>
        <th>Parcela</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$vencidosAte): ?>
        <tr><td colspan="6" class="muted">Nenhum vencido até esta data.</td></tr>
      <?php endif; ?>
      <?php foreach ($vencidosAte as $it):
        $parc = (($it["parcela_num"] ?? 0) && ($it["parcela_total"] ?? 0)) ? ($it["parcela_num"]."/".$it["parcela_total"]) : "—";
      ?>
        <tr>
          <td class="bad"><?=h($it["data_prevista"])?></td>
          <td><span class="pill"><?=h(($it["tipo"] ?? "") === "pagar" ? "Pagar" : "Receber")?></span></td>
          <td><?=h($it["pessoa"])?></td>
          <td><?=h($it["descricao"] ?? "")?></td>
          <td class="right">R$ <?=h(money($it["valor"]))?></td>
          <td><?=h($parc)?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>