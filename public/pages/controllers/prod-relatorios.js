(function () {
    let chart;

    // Função para buscar relatórios por período
    window.buscarRelatorios = async function() {
        const inicio = document.getElementById('data_inicio')?.value;
        const fim = document.getElementById('data_fim')?.value;

        if (!inicio || !fim) return alert("Selecione o período inicial e final.");

        try {
            const res = await fetch(`api/producao_get_relatorios.php?inicio=${inicio}&fim=${fim}`);
            const out = await res.json();

            if (out.ok && out.items && out.items.length > 0) {
                renderTabela(out.items); 
                gerarDashboard(out.items);
            } else {
                alert("Nenhum dado encontrado para este período.");
                const tbody = document.getElementById("tbodyRelatorios");
                if (tbody) tbody.innerHTML = `<tr><td colspan="15">Nenhum registro encontrado</td></tr>`;
            }
        } catch (e) { 
            console.error("Erro na busca:", e); 
        }
    };

    function gerarDashboard(lista) {
        let total = 0;
        let porTurno = {};
        
        lista.forEach(r => {
            const val = parseFloat(r.total) || 0;
            total += val;
            if (!porTurno[r.turno]) porTurno[r.turno] = 0;
            porTurno[r.turno] += val;
        });

        const melhor = Object.entries(porTurno).sort((a, b) => b[1] - a[1])[0];
        const media = lista.length ? total / lista.length : 0;

        const elTotal = document.getElementById("res_producao_total") || document.getElementById("totalGeral");
        const elMelhor = document.getElementById("res_melhor_turno") || document.getElementById("melhorTurno");
        const elMedia = document.getElementById("res_media_turno") || document.getElementById("mediaTurno");

        if (elTotal) elTotal.innerText = total.toFixed(2) + " t";
        if (elMelhor) elMelhor.innerText = melhor ? "Turno " + melhor[0] : "-";
        if (elMedia) elMedia.innerText = media.toFixed(2) + " t";

        renderGrafico(porTurno);
    }

    function renderGrafico(dados) {
        const ctx = document.getElementById("graficoTurnos");
        if (!ctx) return;

        if (chart) chart.destroy();

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(dados).map(t => "Turno " + t),
                datasets: [{
                    label: 'Produção (t)',
                    data: Object.values(dados),
                    backgroundColor: '#0f172a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    function renderTabela(lista) {
        const tbody = document.getElementById("tbodyRelatorios");
        if (!tbody) return;

        tbody.innerHTML = lista.map(r => {
            // 1. TRATA A DATA PARA NÃO ATRASAR UM DIA
            const dataFormatada = r.data.split('-').reverse().join('/');

            // 2. TRATA OS MOTIVOS DAS PARADAS
            let paradas = r.lista_paradas || [];
            let textoParadas = paradas.length > 0 
                ? paradas.map(p => `${p.minutos}min - ${p.motivo}`).join("<br>") 
                : "Sem parada";

            return `
                <tr>
                    <td>${dataFormatada}</td>
                    <td style="text-align:center;">${r.turno}</td>
                    <td>${r.responsavel}</td>
                    <td>${r.operador || '-'}</td>
                    <td>${r.material || '-'}</td>
                    <td>${parseFloat(r.tc01).toFixed(2)}</td>
                    <td>${parseFloat(r.tc02).toFixed(2)}</td>
                    <td>${parseFloat(r.tc03).toFixed(2)}</td>
                    <td>${parseFloat(r.tc04).toFixed(2)}</td>
                    <td>${parseFloat(r.tc05).toFixed(2)}</td>
                    <td><b>${parseFloat(r.total).toFixed(2)} t</b></td>
                    <td style="color:#ef4444; font-weight:bold;">${r.total_paradas || 0} min</td>
                    
                    <!-- MOTIVOS DIGITADOS -->
                    <td style="font-size:0.85em; color:#444;">${textoParadas}</td>
                    
                    <!-- HORA DE ENCERRAMENTO -->
                    <td style="font-weight:bold; color:#2563eb; text-align:center;">
                        ${r.hora_encerramento || '--:--'}
                    </td>
                </tr>
            `;
        }).join("");
    }

    // Inicialização ao carregar a página
    document.addEventListener("DOMContentLoaded", () => {
        // Define datas padrão (hoje) para os inputs se estiverem vazios
        const hj = new Date().toISOString().split('T')[0];
        if(document.getElementById('data_inicio')) document.getElementById('data_inicio').value = hj;
        if(document.getElementById('data_fim')) document.getElementById('data_fim').value = hj;
        
        window.buscarRelatorios(); // Carrega os dados do dia ao abrir
    });

})();
