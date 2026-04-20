(function () {
    
    // --- NOVO: FUNÇÃO PARA ABRIR O PDF EM OUTRA ABA ---
    window.gerarRelatorioPDF = function() {
        const inicio = document.getElementById('data_inicio')?.value;
        const fim = document.getElementById('data_fim')?.value;

        if (!inicio || !fim) {
            return alert("⚠️ Selecione o período antes de gerar o PDF.");
        }

        // Abre o arquivo PHP de impressão (que criaremos a seguir) em nova aba
        const url = `api/producao_pdf.php?inicio=${inicio}&fim=${fim}`;
        window.open(url, '_blank');
    };

    // --- FUNÇÃO GLOBAL CHAMADA PELO BOTÃO 'ANALISAR' ---
    window.carregarEficiencia = async function() {
    const inicio = document.getElementById('data_inicio')?.value;
    const fim = document.getElementById('data_fim')?.value;
    const btnPDF = document.getElementById('btnGerarPDF'); // Pega a referência do botão

    if (!inicio || !fim) {
        return alert("⚠️ Selecione o período inicial e final.");
    }

    try {
        const res = await fetch(`api/producao_get_eficiencia.php?inicio=${inicio}&fim=${fim}`);
        const out = await res.json();

        const el = document.getElementById("painelEficiencia");
        if (el) el.innerHTML = "";

        if (out.ok && out.items && out.items.length > 0) {
            // ✅ MOSTRA O BOTÃO: Encontrou dados, então permite gerar PDF
            if (btnPDF) btnPDF.style.display = 'inline-block';
            
            renderizar(out.items);
        } else {
            // ❌ ESCONDE O BOTÃO: Se não houver dados, não faz sentido imprimir
            if (btnPDF) btnPDF.style.display = 'none';
            
            alert("Nenhum dado encontrado para o período selecionado.");
            if (el) el.innerHTML = '<p style="text-align:center; padding:20px; color:#94a3b8;">Nenhum registro encontrado.</p>';
        }
    } catch (e) {
        if (btnPDF) btnPDF.style.display = 'none';
        console.error("Erro:", e);
        alert("❌ Erro ao carregar os dados.");
    }
};


    function renderizar(lista) {
        const el = document.getElementById("painelEficiencia");
        if (!el) return;
        
        el.innerHTML = lista.map(r => {
            const dataBr = r.data.split('-').reverse().join('/');
            const ef = parseFloat(r.eficiencia) || 0;
            let cor = ef >= 85 ? "#16a34a" : (ef >= 70 ? "#f59e0b" : "#dc2626");

            const htmlParadas = (r.lista_paradas && r.lista_paradas.length > 0) 
                ? r.lista_paradas.map(p => `
                    <li style="border-bottom:1px solid #f1f5f9; padding:4px 0;">
                        <b style="color:#b91c1c;">${p.minutos} min:</b> ${p.motivo}
                    </li>`).join('')
                : '<li style="color:#16a34a;">✅ Nenhuma parada registrada no turno.</li>';

            return `
            <div class="card-eficiencia" style="background:white; padding:20px; border-radius:12px; margin-bottom:25px; border-left:10px solid ${cor}; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;">
                <div>
                  <span style="font-size:20px; font-weight:bold; color:#1e293b;">📅 ${dataBr}</span>
                  <span style="background:#f1f5f9; padding:2px 8px; border-radius:4px; margin-left:10px; font-weight:bold;">Turno ${r.turno}</span>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:28px; font-weight:900; color:${cor};">${ef.toFixed(1)}%</span>
                </div>
              </div>
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
                <div>
                    <p style="margin:0 0 10px 0; font-size:14px;">
                        <b>👤 Responsável:</b> ${r.responsavel}<br>
                        <b>🎮 Operador:</b> ${r.operador || 'Não informado'}
                    </p>
                    <hr style="border:0; border-top:1px dashed #cbd5e1; margin:10px 0;">
                    <b style="font-size:12px; color:#64748b; text-transform:uppercase;">Produção (t):</b>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; font-size:13px; margin-top:5px; color:#475569;">
                        <span>TC01: ${r.tc01}</span> <span>TC02: ${r.tc02}</span>
                        <span>TC03: ${r.tc03}</span> <span>TC04: ${r.tc04}</span>
                        <span>TC05: ${r.tc05}</span>
                    </div>
                    <div style="margin-top:10px; font-size:16px;">
                        <b>Total: <span style="color:#0f172a;">${parseFloat(r.total).toFixed(2)} t</span></b>
                    </div>
                </div>
                <div style="background:#f8fafc; padding:10px; border-radius:8px;">
                    <b style="font-size:12px; color:#991b1b; text-transform:uppercase;">🚫 Motivos das Paradas:</b>
                    <ul style="margin:8px 0; padding:0; font-size:13px; list-style:none;">
                        ${htmlParadas}
                    </ul>
                    <div style="margin-top:10px; border-top:1px solid #e2e8f0; padding-top:5px; display:flex; justify-content:space-between; align-items:center;">
                        <small>Total parado: <b>${r.total_paradas} min</b></small>
                        <small style="color:#2563eb;">Encerrado: <b>${r.hora_encerramento || '--:--'}</b></small>
                    </div>
                </div>
              </div>
            </div>`;
        }).join("");
    }
})();
