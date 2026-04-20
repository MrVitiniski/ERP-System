<?php
$conn = new mysqli("localhost", "root", "", "sistema");
$inicio = $_GET['inicio'] ?? '';
$fim = $_GET['fim'] ?? '';

$sql = "SELECT * FROM producao_relatorios 
        WHERE data BETWEEN '$inicio' AND '$fim' 
        ORDER BY data ASC, turno ASC";
$res = $conn->query($sql);

$contador = 0; // Contador para controlar as quebras de página
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Produção - <?php echo date('d/m/Y', strtotime($inicio)); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 20px; color: #1e293b; margin: 0; }
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
        /* Estilo do Card idêntico ao sistema */
        .card-eficiencia {
            background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; 
            border-left: 8px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-size: 12px; /* Reduzido levemente para caber 10 por folha */
        }
        
        .quebra-pagina { page-break-after: always; } /* Força a troca de folha */

        .header-card { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; }
        .grid-card { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; }
        .col-paradas { background: #f8fafc; padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0; }
        
        ul { margin: 5px 0; padding: 0; list-style: none; }
        li { border-bottom: 1px solid #f1f5f9; padding: 2px 0; font-size: 11px; }
        
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; }
            .card-eficiencia { box-shadow: none; border: 1px solid #e2e8f0; border-left: 8px solid #ccc; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ IMPRIMIR RELATÓRIO (10 por folha)</button>
    </div>

    <?php while($r = $res->fetch_assoc()): 
        $contador++;
        $ef = (float)$r['eficiencia'];
        $cor = $ef >= 85 ? "#16a34a" : ($ef >= 70 ? "#f59e0b" : "#dc2626");
        $paradas = json_decode($r['lista_paradas'], true) ?: [];
    ?>
    
    <div class="card-eficiencia" style="border-left-color: <?php echo $cor; ?>;">
        <div class="header-card">
            <div>
                <b style="font-size:14px;">📅 <?php echo date('d/m/Y', strtotime($r['data'])); ?></b>
                <span style="background:#f1f5f9; padding:2px 5px; border-radius:4px; margin-left:8px;">Turno <?php echo $r['turno']; ?></span>
            </div>
            <div style="text-align:right;">
                <b style="font-size:16px; color:<?php echo $cor; ?>;"><?php echo number_format($ef, 1); ?>% EFICIÊNCIA</b>
            </div>
        </div>

        <div class="grid-card">
            <div>
                <b>👤 Resp:</b> <?php echo $r['responsavel']; ?> | <b>🎮 Op:</b> <?php echo $r['operador'] ?: 'N/A'; ?><br>
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); margin-top:5px; color:#475569; font-size:11px;">
                    <span>TC01: <?php echo $r['tc01']; ?>t</span> <span>TC02: <?php echo $r['tc02']; ?>t</span> <span>TC03: <?php echo $r['tc03']; ?>t</span>
                    <span>TC04: <?php echo $r['tc04']; ?>t</span> <span>TC05: <?php echo $r['tc05']; ?>t</span> <span><b>Total: <?php echo $r['total']; ?>t</b></span>
                </div>
            </div>

            <div class="col-paradas">
                <b style="font-size:10px; color:#991b1b; text-transform:uppercase;">🚫 Paradas:</b>
                <ul>
                    <?php if(!empty($paradas)): foreach(array_slice($paradas, 0, 2) as $p): ?>
                        <li><b><?php echo $p['minutos']; ?>m:</b> <?php echo $p['motivo']; ?></li>
                    <?php endforeach; else: ?>
                        <li style="color:#16a34a;">✅ Sem paradas.</li>
                    <?php endif; ?>
                </ul>
                <div style="display:flex; justify-content:space-between; font-size:10px; margin-top:3px;">
                    <span>Parado: <b><?php echo $r['total_paradas']; ?> min</b></span>
                    <span>Encerrado: <b><?php echo $r['hora_encerramento']; ?></b></span>
                </div>
            </div>
        </div>
    </div>

    <?php 
    // Se atingiu 10 registros, fecha a "folha" e inicia uma nova
    if ($contador % 10 == 0 && $contador < $res->num_rows): ?>
        <div class="quebra-pagina"></div>
    <?php endif; ?>

    <?php endwhile; ?>

</body>
</html>
