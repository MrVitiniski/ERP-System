<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../includes/db.php';
$pdo = db();
set_time_limit(0);

while (true) {
    if (connection_aborted()) break;
    
    $hoje = date('Y-m-d');
    $primeiroDiaMes = date('Y-m-01');

    $data_output = [
        'total_hoje' => 0,
        'total_mes_geral' => 0,
        'detalhe_dia' => [],
        'acumulado_mes' => [],
        'limites' => [],
        'producao_resumo' => ['conchadas' => 0, 'ton_total' => 0, 'paradas' => 0],
        'producao_lista' => [],
        'equipe' => ['responsavel' => 'Aguardando...', 'operador' => 'Aguardando...'], // Novo campo
        'periodo' => "01/" . date('m/Y') . " até " . date('d/m/Y')
    ];

    try {
        // --- 1. DADOS DE HOJE (BALANÇA EXTERNA) ---
        $stmtHoje = $pdo->prepare("SELECT * FROM balanca_pesagens WHERE DATE(data_saida) = ? AND status = 'finalizado'");
        $stmtHoje->execute([$hoje]);
        foreach ($stmtHoje->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $entStr = preg_replace('/\D/', '', $row['peso_entrada']);
            $saiStr = preg_replace('/\D/', '', $row['peso_saida']);
            $ent = (float) substr($entStr, 0, -2);
            $sai = (float) substr($saiStr, 0, -2);
            if ($ent < 100) $ent = (float) $entStr;
            if ($sai < 100) $sai = (float) $saiStr;
            $p = abs($sai - $ent);
            $data_output['total_hoje'] += $p;
            $empresa = strtoupper($row['cliente_nome'] ?? 'GERAL');
            if(!isset($data_output['detalhe_dia'][$empresa])) $data_output['detalhe_dia'][$empresa] = ['carretas' => 0, 'kg' => 0]; 
            $data_output['detalhe_dia'][$empresa]['carretas']++;
            $data_output['detalhe_dia'][$empresa]['kg'] += $p;
        }

        // --- 2. DADOS DE PRODUÇÃO (ORDEM CRONOLÓGICA + EQUIPE) ---
        try {
            // AJUSTE NA ORDEM: Prioriza 22:00-23:59 para o topo, depois segue 00:00+
            $stmtProd = $pdo->prepare("SELECT * FROM producao_lancamentos_temp 
                                       WHERE data = ? 
                                       ORDER BY hora >= '22:00' DESC, hora ASC");
            $stmtProd->execute([$hoje]);
            $logs = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            foreach($logs as $l) {
                // Captura os nomes da equipe do primeiro registro válido que encontrar
                if ($data_output['equipe']['responsavel'] == 'Aguardando...' && !empty($l['responsavel_turno'])) {
                    $data_output['equipe']['responsavel'] = $l['responsavel_turno'];
                }
                if ($data_output['equipe']['operador'] == 'Aguardando...' && !empty($l['operador_sala'])) {
                    $data_output['equipe']['operador'] = $l['operador_sala'];
                }

                $tc01 = (float)($l['tc01'] ?? 0);
                $tc02 = (float)($l['tc02'] ?? 0);
                $tc03 = (float)($l['tc03'] ?? 0);
                $tc04 = (float)($l['tc04'] ?? 0);
                $tc05 = (float)($l['tc05'] ?? 0);
                $somaLinha = $tc01 + $tc02 + $tc03 + $tc04 + $tc05;
                
                $data_output['producao_lista'][] = [
                    'hora'      => substr($l['hora'], 0, 5),
                    'maquina'   => $l['maquina'] ?? 'WA320',
                    'conchadas' => (int)($l['conchadas'] ?? 0),
                    'tc01'      => $tc01,
                    'tc02'      => $tc02,
                    'tc03'      => $tc03,
                    'tc04'      => $tc04,
                    'tc05'      => $tc05,
                    'toneladas' => $somaLinha, 
                    'parada'    => (int)($l['minutos_parada'] ?? 0),
                    'motivo'    => $l['motivo_parada'] ?? '-'
                ];

                $data_output['producao_resumo']['ton_total'] += $somaLinha;
                $data_output['producao_resumo']['paradas']   += (int)($l['minutos_parada'] ?? 0);
                $data_output['producao_resumo']['conchadas'] += (int)($l['conchadas'] ?? 0);
            }
        } catch (Exception $e_prod) { }

        // --- 3. DADOS DO MÊS E COTAS ---
        $stmtMes = $pdo->prepare("SELECT peso_entrada, peso_saida, cliente_nome FROM balanca_pesagens WHERE DATE(data_saida) >= ? AND status = 'finalizado'");
        $stmtMes->execute([$primeiroDiaMes]);
        foreach ($stmtMes->fetchAll(PDO::FETCH_ASSOC) as $rowM) {
            $eM = (float) substr(preg_replace('/\D/', '', $rowM['peso_entrada']), 0, -2);
            $sM = (float) substr(preg_replace('/\D/', '', $rowM['peso_saida']), 0, -2);
            if ($eM < 100) $eM = (float) preg_replace('/\D/', '', $rowM['peso_entrada']);
            if ($sM < 100) $sM = (float) preg_replace('/\D/', '', $rowM['peso_saida']);
            $pM = abs($sM - $eM);
            $data_output['total_mes_geral'] += $pM;
            $nCli = strtoupper($rowM['cliente_nome'] ?? 'GERAL');
            $data_output['acumulado_mes'][$nCli] = ($data_output['acumulado_mes'][$nCli] ?? 0) + $pM;
        }

        $stmtC = $pdo->prepare("SELECT cliente_nome, limite_ton FROM balanca_cotas_config WHERE ? BETWEEN data_inicio AND data_fim");
        $stmtC->execute([$hoje]);
        $data_output['limites'] = $stmtC->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        echo "data: " . json_encode($data_output) . "\n\n";

    } catch (Exception $e) {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }
    
    if (ob_get_level() > 0) ob_end_flush();
    flush();
    sleep(3);
}