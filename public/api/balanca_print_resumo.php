<?php
require_once __DIR__ . "/../includes/db.php";

// AJUSTE: Agora o padrão é HOJE, para não acumular o mês inteiro na prévia
$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fim    = $_GET['fim']    ?? date('Y-m-d');

$pdo = db();

// Filtro de segurança para o dia completo
$where = " WHERE status = 'finalizado' AND data_saida BETWEEN ? AND ? ";
$params = [$inicio . " 00:00:00", $fim . " 23:59:59"];

/** * SQL PADRONIZADO PARA KG:
 * Somamos o peso líquido puro. O banco já armazena em KG sem pontos decimais.
 */
$sqlC = "SELECT cliente_nome, COUNT(*) as v, 
         SUM(ABS(peso_entrada - peso_saida)) as peso_total 
         FROM balanca_pesagens $where GROUP BY cliente_nome ORDER BY peso_total DESC";

$sqlM = "SELECT material_tipo, COUNT(*) as v, 
         SUM(ABS(peso_entrada - peso_saida)) as peso_total 
         FROM balanca_pesagens $where GROUP BY material_tipo ORDER BY peso_total DESC";

$clientes = $pdo->prepare($sqlC); 
$clientes->execute($params);

$materiais = $pdo->prepare($sqlM); 
$materiais->execute($params);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SCAVARE - Resumo de Movimentação</title>
    <style>
        body { font-family: sans-serif; padding: 30px; color: #334155; background: #f1f5f9; }
        .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: white; }
        th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; }
        th { background: #f8fafc; text-transform: uppercase; font-size: 12px; color: #64748b; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #0f172a; padding-bottom: 20px; }
        .badge-hoje { background: #0ea5e9; color: white; padding: 4px 12px; border-radius: 9999px; font-size: 14px; }
        .t-center { text-align: center; }
        .t-right { text-align: right; }
        @media print { .no-print { display: none; } body { padding: 0; background: white; } .container { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../assets/img/logo.png" alt="SCAVARE" style="height: 50px; margin-bottom: 10px;"> 
            <h1>RESUMO DE MOVIMENTAÇÃO (KG)</h1>
            <p>
                Período: <strong><?php echo date('d/m/Y', strtotime($inicio)); ?></strong> 
                até <strong><?php echo date('d/m/Y', strtotime($fim)); ?></strong>
                <?php if($inicio == date('Y-m-d')) echo '<span class="badge-hoje">HOJE</span>'; ?>
            </p>
        </div>

        <h3><i class="fas fa-users"></i> TOTAL POR CLIENTE</h3>
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="t-center">Viagens</th>
                    <th class="t-right">Total (KG)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($clientes as $c): 
                    $pesoKg = (float)$c['peso_total'];
                ?>
                <tr>
                    <td><?php echo $c['cliente_nome']; ?></td>
                    <td class="t-center"><?php echo $c['v']; ?></td>
                    <td class="t-right"><strong><?php echo number_format($pesoKg, 0, ',', '.'); ?> kg</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3><i class="fas fa-boxes"></i> TOTAL POR MATERIAL</h3>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th class="t-center">Viagens</th>
                    <th class="t-right">Total (KG)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($materiais as $m): 
                    $pesoKgM = (float)$m['peso_total'];
                ?>
                <tr>
                    <td><?php echo $m['material_tipo']; ?></td>
                    <td class="t-center"><?php echo $m['v']; ?></td>
                    <td class="t-right"><strong><?php echo number_format($pesoKgM, 0, ',', '.'); ?> kg</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="display: flex; gap: 10px;" class="no-print">
            <button onclick="window.print()" style="flex: 1; padding: 15px; background: #0f172a; color: #fff; cursor: pointer; border: none; border-radius: 5px; font-weight: bold;">
                🖨️ IMPRIMIR RELATÓRIO
            </button>
            <button onclick="window.history.back()" style="flex: 1; padding: 15px; background: #64748b; color: #fff; cursor: pointer; border: none; border-radius: 5px; font-weight: bold;">
                VOLTAR
            </button>
        </div>
    </div>
</body>
</html>