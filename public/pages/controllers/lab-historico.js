(function() {
    let cacheLab = [];
    let chartQuimico, chartDensidade; 
    const tbody = document.getElementById('tbodyHistLab'); 
    const inputBusca = document.getElementById('buscaLab');

    // 1. FUNÇÃO PARA RENDERIZAR OS GRÁFICOS
    function renderizarGraficos(dados) {
        if (!dados || dados.length === 0) return;

        const agrupado = {};
        // Ordena para o gráfico refletir a ordem cronológica correta
        const dadosOrdenados = [...dados].sort((a, b) => new Date(a.data_producao) - new Date(b.data_producao));

        dadosOrdenados.forEach(it => {
            const dataObj = new Date(it.data_producao);
            const mesAno = dataObj.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
            
            if (!agrupado[mesAno]) agrupado[mesAno] = { fe: [], dens: [] };
            agrupado[mesAno].fe.push(parseFloat(it.fe_pct) || 0);
            agrupado[mesAno].dens.push(parseFloat(it.densidade) || 0);
        });

        const labels = Object.keys(agrupado);
        const mediasFe = labels.map(m => (agrupado[m].fe.reduce((a, b) => a + b, 0) / agrupado[m].fe.length).toFixed(2));
        const mediasDens = labels.map(m => (agrupado[m].dens.reduce((a, b) => a + b, 0) / agrupado[m].dens.length).toFixed(3));

        const ctxQ = document.getElementById('chartQuimico');
        if(ctxQ) {
            if (chartQuimico) chartQuimico.destroy();
            chartQuimico = new Chart(ctxQ, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Média Fe %', data: mediasFe, borderColor: '#10b981', backgroundColor: '#10b98122', fill: true, tension: 0.4 }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        const ctxD = document.getElementById('chartDensidade');
        if(ctxD) {
            if (chartDensidade) chartDensidade.destroy();
            chartDensidade = new Chart(ctxD, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{ label: 'Média Densidade', data: mediasDens, backgroundColor: '#f59e0b' }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    }

    // 2. FUNÇÃO PARA CARREGAR RELATÓRIO FILTRADO (POR PERÍODO)
    async function carregarRelatorioMensal() {
        const inicio = document.getElementById('rel_data_inicio').value;
        const fim = document.getElementById('rel_data_fim').value;

        if (!inicio || !fim) return alert("Selecione o período inicial e final.");

        try {
            const res = await fetch(`api/lab_get_historico.php?inicio=${inicio}&fim=${fim}`);
            const out = await res.json();
            
            if (out.ok && out.items.length > 0) {
                document.getElementById('dashboardRelatorio').style.display = 'block';
                document.getElementById('msgVazioRelatorio').style.display = 'none';
                
                renderizarGraficos(out.items);
                renderizar(out.items);
            } else {
                alert("Nenhum dado encontrado para este período.");
            }
        } catch (e) { console.error("Erro ao gerar relatório:", e); }
    }

    // 3. FUNÇÃO VER LAUDO (COM HORA INCLUÍDA)
    window.verLaudo = function(id) {
        const it = cacheLab.find(x => x.id == id);
        if(!it) return;

        document.getElementById('laudo_scavare').textContent = it.scavare_numero || 'N/A';
        document.getElementById('conteudoLaudo').innerHTML = `
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; color:#1e293b; font-size:14px;">
                <div style="grid-column: span 2; background:#f8fafc; padding:15px; border-radius:6px; border:1px solid #e2e8f0; border-left: 5px solid #0f172a;">
                    <strong>📦 AMOSTRA:</strong> ${it.descricao}<br>
                    <strong>📅 DATA PRODUÇÃO:</strong> ${new Date(it.data_producao).toLocaleDateString('pt-BR')}<br>
                    <strong>⏰ REGISTRO NO SISTEMA:</strong> ${it.data_registro_formatada || 'N/A'}
                </div>
                <div style="background:#fffbeb; border:1px solid #fef3c7; padding:15px; border-radius:8px;">
                    <h4 style="margin:0 0 10px 0; color:#92400e;">⚖️ Física / Densidade</h4>
                    <b>Peso Seco:</b> ${it.peso_seco} g | <b>Volume:</b> ${it.volume} cm³<br>
                    <b>Densidade:</b> <span style="font-weight:bold;">${it.densidade}</span> g/cm³
                </div>
                <div style="background:#f0fdf4; border:1px solid #dcfce7; padding:15px; border-radius:8px;">
                    <h4 style="margin:0 0 10px 0; color:#166534;">🔬 Química (%)</h4>
                    <b>Fe:</b> ${it.fe_pct}% | <b>SiO2:</b> ${it.sio2_pct}%<br>
                    <b>Al2O3:</b> ${it.al2o3_pct}% | <b>Mn:</b> ${it.mn_pct}% | <b>P:</b> ${it.p_pct}%
                </div>
                <div style="grid-column: span 2; background:#fdf2f8; border:1px solid #fce7f3; padding:15px; border-radius:8px; border-left: 5px solid #db2777;">
                    <strong>📊 Granulometria / Observações:</strong><br>
                    <p style="margin-top:5px; font-style:italic;">${it.com_alumina || 'Nenhuma informação técnica registrada.'}</p>
                </div>
            </div>`;
        document.getElementById('modalLaudo').style.display = 'flex';
    };

    window.fecharLaudo = () => document.getElementById('modalLaudo').style.display = 'none';

    // 4. FUNÇÃO DE RENDERIZAR TABELA (ADICIONADA COLUNA DE HORA)
    function renderizar(lista) {
        if (!tbody) return;
        tbody.innerHTML = lista.map(it => `
            <tr>
                <td style="font-weight:bold; color: #2563eb;">#${it.id}</td>
                <td>${new Date(it.data_producao).toLocaleDateString('pt-BR')}</td>
                <td style="color: #64748b; font-size: 0.85em;">${it.data_registro_formatada || '-'}</td>
                <td>${it.descricao}</td>
                <td>${it.fe_pct}%</td>
                <td><button onclick="verLaudo(${it.id})" class="clean-btn">Ver Laudo</button></td>
            </tr>`).join('');
    }

    // 5. CARREGAR HISTÓRICO INICIAL
    async function carregarHistorico() {
        try {
            const res = await fetch('api/lab_get_historico.php');
            const out = await res.json();
            if (out.ok) {
                cacheLab = out.items;
                renderizar(cacheLab);
            }
        } catch (e) { console.error(e); }
    }

    // LISTENERS
    const btnGerar = document.getElementById('btnGerarRelatorio');
    if(btnGerar) btnGerar.onclick = carregarRelatorioMensal;

    const btnAtu = document.getElementById('btnAtualizarLab');
    if(btnAtu) btnAtu.onclick = carregarHistorico;

    carregarHistorico();
})();
