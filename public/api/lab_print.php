<?php
declare(strict_types=1);

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

start_session();
$me = current_user();
$role = (string)($me["role"] ?? "");
if (!$me || ($role !== "admin" && $role !== "laboratorio")) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM lab_reports WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  echo "Não encontrado";
  exit;
}

$dados = json_decode((string)$row["dados"], true) ?: [];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fnum($v, $dec=2){
  if ($v === null || $v === "") return "";
  if (!is_numeric($v)) return h($v);
  return number_format((float)$v, $dec, ",", ".");
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Relatório Lab #<?= h($row["id"]) ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; }
    .top { display:flex; justify-content:space-between; gap:12px; }
    h1 { margin: 0 0 8px; font-size: 18px; }
    .muted { color:#555; font-size: 12px; }
    table { width:100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
    th { background:#f5f5f5; text-align:left; }
    .actions { margin-top: 14px; }
    @media print { .actions { display:none; } }
  </style>
</head>
<body>
  <div class="top">
    <div>
      <h1>Relatório de Laboratório</h1>
      <div class="muted">ID: <?= h($row["id"]) ?> · Criado em: <?= h($row["created_at"]) ?></div>
    </div>
    <div class="actions">
      <button onclick="window.print()">Imprimir / Salvar como PDF</button>
    </div>
  </div>

  <table>
    <tr><th>Tipo de amostra</th><td><?= h($row["tipo_amostra"]) ?></td></tr>
    <tr><th>Tag</th><td><?= h($row["tag"] ?? "") ?></td></tr>
    <tr><th>Data da produção</th><td><?= h($row["data_producao"]) ?></td></tr>
    <tr><th>Identificação</th><td><?= h($row["identificacao"] ?? "") ?></td></tr>
    <tr><th>Com-Alumina</th><td><?= ((int)$row["com_alumina"] === 1) ? "Sim" : "Não" ?></td></tr>
  </table>

  <h2 style="margin:16px 0 6px; font-size:14px;">Densidade / Umidade</h2>
  <table>
    <thead>
      <tr>
        <th>Peso úmido (g)</th>
        <th>Peso seco (g)</th>
        <th>Volume (cm³)</th>
        <th>Densidade (g/cm³)</th>
        <th>% Sólido</th>
        <th>% Umidade</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= fnum($dados["peso_umido_g"] ?? null, 2) ?></td>
        <td><?= fnum($dados["peso_seco_g"] ?? null, 2) ?></td>
        <td><?= fnum($dados["volume_cm3"] ?? null, 2) ?></td>
        <td><?= fnum($dados["densidade_g_cm3"] ?? null, 9) ?></td>
        <td><?= fnum($dados["percent_solido"] ?? null, 2) ?></td>
        <td><?= fnum($dados["percent_umidade"] ?? null, 2) ?></td>
      </tr>
    </tbody>
  </table>

  <h2 style="margin:16px 0 6px; font-size:14px;">Análise / Granulometria</h2>
  <table>
    <thead>
      <tr>
        <th>Granulometria</th>
        <th>Fe %</th>
        <th>SiO2 %</th>
        <th>FeT %</th>
        <th>Fe2O3 %</th>
        <th>Al2O3 %</th>
        <th>Mn</th>
        <th>P</th>
        <th>PPC</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?= h($dados["granulometria"] ?? "") ?></td>
        <td><?= fnum($dados["fe_percent"] ?? null, 4) ?></td>
        <td><?= fnum($dados["sio2_percent"] ?? null, 4) ?></td>
        <td><?= fnum($dados["fet_percent"] ?? null, 4) ?></td>
        <td><?= fnum($dados["fe2o3_percent"] ?? null, 4) ?></td>
        <td><?= fnum($dados["al2o3_percent"] ?? null, 4) ?></td>
        <td><?= h($dados["mn_text"] ?? "") ?></td>
        <td><?= h($dados["p_text"] ?? "") ?></td>
        <td><?= h($dados["ppc_text"] ?? "") ?></td>
      </tr>
    </tbody>
  </table>

  <h2 style="margin:16px 0 6px; font-size:14px;">Observações</h2>
  <div style="border:1px solid #ddd; padding:10px; font-size:12px; min-height:40px;">
    <?= h($dados["obs"] ?? "") ?>
  </div>
</body>
</html>