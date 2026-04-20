(function() {
    const form = document.getElementById("fNovaOS");
    const tbody = document.getElementById("tbodyOS");
    const tbodyHist = document.querySelector("#tabelaHistoricoOS tbody");

    // Preencher data de abertura com a hora atual ao carregar
    const inputAbertura = document.getElementById('data_abertura_nova');
    if(inputAbertura) {
        const agora = new Date();
        inputAbertura.value = new Date(agora.getTime() - (agora.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    }

    async function carregarDados() {
        try {
            // 1. CARREGAR ABERTAS
            const resA = await fetch('api/mecanica_lista_os.php');
            const outA = await resA.json();
            if (outA.ok && tbody) {
                tbody.innerHTML = outA.items.map(it => `
                    <tr>
                        <td><b>#${it.id}</b></td>
                        <td style="font-size:0.8rem;">${it.data_abertura}</td>
                        <td><b>${it.equipamento}</b><br><small>${it.setor}</small></td>
                        <td>${it.solicitante}</td>
                        <td style="font-style:italic; font-size:0.85rem;">"${it.descricao_problema}"</td>
                        <td style="text-align:center;">
                            <button class="clean-btn clean-btn--sm" onclick="encerrarOS(${it.id})">Encerrar</button>
                        </td>
                    </tr>
                `).join("");
            }

            // 2. CARREGAR HISTÓRICO
            const resH = await fetch('api/mecanica_lista_historico.php');
            const outH = await resH.json();
            if (outH.ok && tbodyHist) {
                tbodyHist.innerHTML = outH.items.map(it => `
                    <tr>
                        <td><b>#${it.id}</b></td>
                        <td style="font-size:0.75rem;">Abt: ${it.data_abertura}<br><b>Enc: ${it.data_encerramento}</b></td>
                        <td><b>${it.equipamento}</b><br><small>${it.setor}</small></td>
                        <td>
                            <div style="font-size:0.8rem; color:#94a3b8;">Ref: "${it.descricao_problema}"</div>
                            <div style="font-weight:700;">Feito: ${it.servico_executado}</div>
                        </td>
                        <td style="font-size:0.8rem;">Mec: ${it.mecanico}<br>Solic: ${it.solicitante}</td>
                    </tr>
                `).join("");
            }
        } catch (e) { console.error("Erro ao carregar dados:", e); }
    }

    // ABRIR MODAL COM DATA PREENCHIDA
    window.encerrarOS = function(id) {
        document.getElementById('input_os_id').value = id;
        document.getElementById('modal_id_os').textContent = id;
        
        const agora = new Date();
        document.getElementById('input_data_fim').value = new Date(agora.getTime() - (agora.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
        
        document.getElementById('modalEncerrar').style.display = 'flex';
    };

    window.fecharModal = function() {
        document.getElementById('modalEncerrar').style.display = 'none';
    };

    // EVENTOS
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/mecanica_abrir_os.php', { method: 'POST', body: new FormData(form) });
            const out = await res.json();
            if (out.ok) { alert("OS Aberta!"); form.reset(); carregarDados(); }
        };
    }

    const formEnc = document.getElementById('formEncerrarOS');
    if (formEnc) {
        formEnc.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/mecanica_encerrar_os.php', { method: 'POST', body: new FormData(formEnc) });
            const out = await res.json();
            if (out.ok) { fecharModal(); alert("OS Encerrada!"); carregarDados(); }
        };
    }

    carregarDados();
})();
