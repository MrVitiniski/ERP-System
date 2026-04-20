<?php
require_once __DIR__ . "/../includes/db.php";
$id = $_GET['id'] ?? null;
if (!$id) die("Ticket não encontrado.");

$pdo = db();
$stmt = $pdo->prepare("SELECT *, 
    DATE_FORMAT(data_entrada, '%d/%m/%Y %H:%i') as entrada_fmt,
    DATE_FORMAT(data_saida, '%d/%m/%Y %H:%i') as saida_fmt
    FROM balanca_pesagens WHERE id = ?");
$stmt->execute([$id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$t) die("Registro inexistente.");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?php echo $id; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 300px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; }
        .header { text-align: center; border-bottom: 2px dashed #000; margin-bottom: 10px; padding-bottom: 10px; }
        .item { margin-bottom: 5px; font-size: 14px; }
        .item b { text-transform: uppercase; }
        .footer { margin-top: 15px; border-top: 2px dashed #000; padding-top: 10px; font-size: 12px; text-align: center; }
        @media print { .no-print { display: none; } body { border: none; } }
    </style>
</head>
<body>
    <div class="header">
        <h3>SCAVARE BALANÇA</h3>
        <b>TICKET DE PESAGEM: #<?php echo $id; ?></b>
    </div>
    <div class="item"><b>Placa:</b> <?php echo $t['placa_cavalo']; ?></div>
    <div class="item"><b>Motorista:</b> <?php echo $t['motorista_nome']; ?></div>
    <div class="item"><b>Empresa:</b> <?php echo $t['transportadora']; ?></div>
    <div class="item"><b>Operação:</b> <?php echo $t['tipo_operacao']; ?></div>
    <div class="item"><b>Material:</b> <?php echo $t['material_tipo']; ?></div>
    <div class="item"><b>NF:</b> <?php echo $t['nf_numero']; ?></div>
    <hr>
    <div class="item"><b>Entrada:</b> <?php echo $t['entrada_fmt']; ?></div>
    <div class="item"><b>Peso Ent.:</b> <?php echo $t['peso_entrada']; ?> kg</div>
    <?php if($t['status'] == 'finalizado'): ?>
        <div class="item"><b>Saída:</b> <?php echo $t['saida_fmt']; ?></div>
        <div class="item"><b>Peso Saí.:</b> <?php echo $t['peso_saida']; ?> kg</div>
        <div class="item" style="font-size:18px; font-weight:bold;"><b>LÍQUIDO:</b> <?php echo abs($t['peso_entrada'] - $t['peso_saida']); ?> kg</div>
    <?php endif; ?>
    
    <div class="footer">
        <button class="no-print" onclick="window.print()">🖨️ IMPRIMIR</button>
        <p>Assinatura: _______________________</p>
    </div>
</body>
</html>
