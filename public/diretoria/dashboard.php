<?php require_once __DIR__ . '/../includes/db.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SCAVARE - Dashboard Diretoria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: system-ui, sans-serif; }
        .card { background: #1e2937; border-radius: 24px; border: 1px solid #334155; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .progress-bar { transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
        .custom-scroll::-webkit-scrollbar { width: 8px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 10px; }
        tr { height: 50px; } 
        .stat-card { border-left: 4px solid; }
    </style>
</head>
<body class="p-8">

    <div class="max-w-[1800px] mx-auto">
        <div class="flex justify-between items-center mb-8 bg-slate-800/50 p-8 rounded-[30px] border border-slate-700">
            <div>
                <h1 class="text-4xl font-black text-emerald-400 tracking-tighter uppercase italic leading-none">Scavare Dashboard</h1>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.3em] mt-2">Diretoria & Logística</p>
            </div>
            <div class="bg-emerald-500/10 text-emerald-500 px-6 py-3 rounded-2xl border border-emerald-500/20 text-sm font-black animate-pulse">🟢 AO VIVO</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-6 stat-card border-emerald-500 flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Alimentação WA320</p>
                    <h4 id="prod-conchadas" class="text-3xl font-black text-white">0</h4>
                    <p class="text-emerald-500 text-[10px] font-bold uppercase">Conchadas Hoje</p>
                </div>
                <i class="fas fa-tractor text-slate-700 text-3xl"></i>
            </div>

            <div class="card p-6 stat-card border-blue-500 flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Produção Planta</p>
                    <h4 id="prod-planta" class="text-3xl font-black text-white">0.00</h4>
                    <p class="text-blue-500 text-[10px] font-bold uppercase">Toneladas (TCs)</p>
                </div>
                <i class="fas fa-industry text-slate-700 text-3xl"></i>
            </div>

            <div class="card p-6 stat-card border-slate-500 flex items-center justify-between bg-slate-800/40">
                <div class="w-full">
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-2">Equipe em Operação</p>
                    <div class="space-y-1">
                        <div class="flex flex-col">
                            <span class="text-emerald-400 text-[9px] font-bold uppercase">Responsável</span>
                            <span id="equipe-responsavel" class="text-base font-black text-white truncate">Aguardando...</span>
                        </div>
                        <div class="flex flex-col mt-1">
                            <span class="text-blue-400 text-[9px] font-bold uppercase">Operador</span>
                            <span id="equipe-operador" class="text-base font-black text-white truncate">Aguardando...</span>
                        </div>
                    </div>
                </div>
                <i class="fas fa-users-cog text-slate-700 text-3xl ml-2"></i>
            </div>

            <div id="card-parada" class="card p-6 stat-card border-slate-600 flex items-center justify-between transition-colors">
                <div>
                    <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Eficiência</p>
                    <h4 id="prod-paradas" class="text-3xl font-black text-white">0</h4>
                    <p id="label-parada" class="text-slate-500 text-[10px] font-bold uppercase">Minutos de Parada</p>
                </div>
                <i class="fas fa-clock text-slate-700 text-3xl"></i>
            </div>
        </div>

        <div class="card p-8 mb-8">
            <h2 class="text-2xl font-black text-white mb-6 uppercase italic flex items-center gap-4">
                <i class="fas fa-history text-blue-500 text-3xl"></i> Monitoramento Operacional (Produção por Hora)
            </h2>
            <div class="overflow-x-auto rounded-2xl border border-slate-700">
                <table class="w-full text-left">
                    <thead class="bg-slate-800 text-slate-400 text-[10px] uppercase font-bold tracking-widest">
                        <tr>
                            <th class="px-4 py-4">Hora</th>
                            <th class="px-4 py-4">Máquina</th>
                            <th class="px-4 py-4 text-center">Conch.</th>
                            <th class="px-4 py-4 text-center text-blue-400">TC01</th>
                            <th class="px-4 py-4 text-center">TC02</th>
                            <th class="px-4 py-4 text-center text-emerald-400 font-black">TC03</th>
                            <th class="px-4 py-4 text-center">TC04</th>
                            <th class="px-4 py-4 text-center">TC05</th>
                            <th class="px-4 py-4 text-center">Parada</th>
                            <th class="px-4 py-4 text-left">Observação</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-producao-horaria" class="divide-y divide-slate-700/50 bg-slate-800/20 text-sm">
                        <tr><td colspan="10" class="text-center py-10 text-slate-500 uppercase font-bold text-xs tracking-widest">Sincronizando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-4">
                <div class="card p-8 h-full">
                    <h2 class="text-2xl font-black text-white mb-8 uppercase italic flex items-center gap-4">
                        <i class="fas fa-truck text-emerald-500 text-3xl"></i> Parcial do Dia
                    </h2>
                    <div class="overflow-hidden rounded-2xl border border-slate-700">
                        <table class="w-full text-left text-lg">
                            <thead class="bg-slate-800 text-slate-400 text-xs uppercase font-bold">
                                <tr>
                                    <th class="px-6 py-5">Empresa</th>
                                    <th class="px-6 py-5 text-center">Carretas</th>
                                    <th class="px-6 py-5 text-right">Toneladas</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-parcial" class="divide-y divide-slate-700 bg-slate-800/20"></tbody>
                            <tfoot class="bg-slate-800 font-black text-emerald-400 text-xl">
                                <tr>
                                    <td class="px-6 py-6 uppercase text-sm">Total Geral</td>
                                    <td id="total-viagens" class="px-6 py-6 text-center text-xl text-blue-400">-</td>
                                    <td id="total-ton-hoje" class="px-6 py-6 text-right text-xl">-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="card p-8">
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-black text-white uppercase italic flex items-center gap-4">
                            <i class="fas fa-calendar-alt text-blue-500 text-3xl"></i> Status Mensal
                        </h2>
                        <span id="label-periodo" class="text-xs font-bold bg-slate-900 px-4 py-2 rounded-lg border border-slate-700 text-slate-400 uppercase tracking-widest"></span>
                    </div>
                    <div id="container-cotas" class="space-y-6 max-h-[750px] overflow-y-auto pr-4 custom-scroll"></div>
                </div>
            </div>

            <div class="lg:col-span-3 space-y-8">
                <div class="card p-10 text-center bg-gradient-to-br from-slate-800 to-slate-900 border-t-8 border-emerald-500">
                    <h3 class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-6">Total Vendido Mês</h3>
                    <div id="producao-mes-total" class="text-6xl font-black text-white mb-2 tracking-tighter">0</div>
                    <p class="text-emerald-500 text-sm font-bold uppercase italic">Quilos Registrados (KG)</p>
                </div>
                <div class="card p-8 bg-blue-500/5 border-blue-500/20 text-center">
                    <h4 class="text-blue-400 font-bold text-xs uppercase mb-3">Informativo</h4>
                    <p class="text-slate-400 text-[11px] leading-relaxed">Dados de conchadas e esteiras atualizados em tempo real pela sala de controle.</p>
                </div>
            </div>
        </div>
    </div>

<script>
    const eventSource = new EventSource('../api/balanca_sse.php');
    
    eventSource.onmessage = function(e) {
        try {
            const data = JSON.parse(e.data);
            if (data.error) throw new Error(data.error);

            // 1. CARDS SUPERIORES E EQUIPE
            if (data.producao_resumo) {
                const res = data.producao_resumo;
                document.getElementById('prod-conchadas').textContent = res.conchadas.toLocaleString('pt-BR');
                document.getElementById('prod-planta').textContent = res.ton_total.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ' t';
                document.getElementById('prod-paradas').textContent = res.paradas;
            }

            if (data.equipe) {
                document.getElementById('equipe-responsavel').textContent = data.equipe.responsavel;
                document.getElementById('equipe-operador').textContent = data.equipe.operador;
            }

            // 2. TABELA OPERACIONAL (PLANTA)
            let htmlProd = '';
            if (data.producao_lista && data.producao_lista.length > 0) {
                data.producao_lista.forEach(item => {
                    const corP = item.parada > 0 ? 'text-red-500 font-black' : 'text-slate-500';
                    htmlProd += `
                        <tr class="hover:bg-slate-800/50 transition-colors border-b border-slate-700/30">
                            <td class="px-4 py-3 font-black text-emerald-400 italic text-lg">${item.hora}</td>
                            <td class="px-4 py-3 text-slate-400 font-bold uppercase text-[10px]">${item.maquina}</td>
                            <td class="px-4 py-3 text-center font-bold text-white text-lg">${item.conchadas}</td>
                            <td class="px-4 py-3 text-center text-blue-400 font-bold">${item.tc01.toFixed(1)}</td>
                            <td class="px-4 py-3 text-center text-slate-300">${item.tc02.toFixed(1)}</td>
                            <td class="px-4 py-3 text-center text-emerald-400 font-black bg-emerald-500/5 text-lg">${item.tc03.toFixed(1)}</td>
                            <td class="px-4 py-3 text-center text-slate-300">${item.tc04.toFixed(1)}</td>
                            <td class="px-4 py-3 text-center text-slate-300">${item.tc05.toFixed(1)}</td>
                            <td class="px-4 py-3 text-center ${corP}">${item.parada} min</td>
                            <td class="px-4 py-3 text-slate-500 text-[10px] italic">${item.motivo || '-'}</td>
                        </tr>`;
                });
            } else {
                htmlProd = '<tr><td colspan="10" class="text-center py-10 text-slate-600 uppercase font-bold text-xs tracking-widest">Aguardando lançamentos...</td></tr>';
            }
            document.getElementById('tabela-producao-horaria').innerHTML = htmlProd;

            // 3. LOGÍSTICA (PARCIAL DO DIA)
            let htmlTab = '';
            let vTotal = 0;
            if (data.detalhe_dia) {
                Object.entries(data.detalhe_dia).forEach(([empresa, info]) => {
                    vTotal += info.carretas;
                    htmlTab += `
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-200 uppercase text-sm">${empresa}</td>
                            <td class="px-6 py-4 text-center font-black text-blue-400 text-xl">${info.carretas}</td>
                            <td class="px-6 py-4 text-right font-black text-white text-xl">${(info.kg / 1000).toLocaleString('pt-BR', {minimumFractionDigits: 2})} t</td>
                        </tr>`;
                });
            }
            document.getElementById('tabela-parcial').innerHTML = htmlTab;
            document.getElementById('total-viagens').textContent = vTotal;
            document.getElementById('total-ton-hoje').textContent = ((data.total_hoje || 0) / 1000).toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " t";

            // 4. MENSAL E COTAS (COM CÁLCULO DE BARRA)
            document.getElementById('label-periodo').textContent = data.periodo || "";
            document.getElementById('producao-mes-total').textContent = (data.total_mes_geral || 0).toLocaleString('pt-BR');

            let htmlCotas = '';
            if (data.acumulado_mes && Object.keys(data.acumulado_mes).length > 0) {
                Object.entries(data.acumulado_mes).forEach(([empresa, pesoMes]) => {
                    // Cota vem em Toneladas, convertemos para KG
                    const limiteKg = (data.limites && data.limites[empresa]) ? parseFloat(data.limites[empresa]) * 1000 : 0; 
                    
                    let perc = 0;
                    let infoCota = "Cota não definida";

                    if (limiteKg > 0) {
                        perc = Math.min((pesoMes / limiteKg) * 100, 100);
                        infoCota = `Cota: ${limiteKg.toLocaleString('pt-BR')} kg`;
                    }

                    const corBarra = perc >= 100 ? 'bg-red-500' : 'bg-blue-500';

                    htmlCotas += `
                        <div class="bg-slate-800/40 p-6 rounded-[20px] border border-slate-700 shadow-inner mb-4">
                            <div class="flex justify-between items-end mb-4">
                                <div>
                                    <span class="text-sm font-black uppercase text-white tracking-wider">${empresa}</span>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase italic">${infoCota}</p>
                                </div>
                                <span class="text-lg font-black text-blue-400">${pesoMes.toLocaleString('pt-BR')} kg</span>
                            </div>
                            <div class="bg-slate-900 h-3 rounded-full overflow-hidden mb-2 border border-slate-800">
                                <div class="h-full ${corBarra} progress-bar" style="width: ${perc}%"></div>
                            </div>
                            <div class="flex justify-between text-[9px] font-bold text-slate-600 uppercase">
                                <span>0 kg</span>
                                <span>${perc.toFixed(1)}%</span>
                                <span>${limiteKg > 0 ? (limiteKg / 1000).toFixed(0) + ' t' : '-'}</span>
                            </div>
                        </div>`;
                });
                document.getElementById('container-cotas').innerHTML = htmlCotas;
            }

        } catch (err) {
            console.error("Erro no Dashboard:", err);
        }
    };
</script>
</body>
</html>