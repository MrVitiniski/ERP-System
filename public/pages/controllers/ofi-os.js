(function() {
    const form = document.getElementById("fNovaOS");
    const tbody = document.getElementById("tbodyOS");
    const tbodyHist = document.querySelector("#tabelaHistoricoOS tbody");
    const selectEquip = document.getElementById('select_equipamento');

    // 1. Preencher data de abertura com a hora atual ao carregar
    const inputAbertura = document.getElementById('data_abertura_nova');
    if(inputAbertura) {
        const agora = new Date();
        inputAbertura.value = new Date(agora.getTime() - (agora.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
    }

    // 2. BUSCAR VEÍCULOS DA FROTA PARA O SELECT
    async function popularFrota() {
        if (!selectEquip) return;
        try {
            const res = await fetch('api/oficina_get_frota.php');
            const out = await res.json();
            if (out.ok) {
                selectEquip.innerHTML = '<option value="">Selecione o Veículo...</option>' + 
                    out.items.map(v => `<option value="${v.placa}">${v.placa} - ${v.modelo}</option>`).join('');
            }
        } catch (e) {
            selectEquip.innerHTML = '<option value="">Erro ao carregar frota</option>';
        }
    }

    // 3. CARREGAR TABELAS (ATIVAS E HISTÓRICO)
    async function carregarDados() {
        try {
            // CARREGAR ABERTAS
            const resA = await fetch('api/oficina_get_lista_os.php');
            const outA = await resA.json();
            if (outA.ok && tbody) {
                tbody.innerHTML = outA.items.map(it => `
                    <tr>
                        <td><b>#${it.id}</b></td>
                        <td style="font-size:0.8rem;">${it.data_abertura}</td>
                        <td><b>${it.equipamento}</b><br><small>Setor: ${it.setor || '---'}</small></td>
                        <td>${it.motorista_operador || it.solicitante || '---'}</td>
                        <td style="font-style:italic; font-size:0.85rem;">"${it.descricao_problema}"</td>
                        <td style="text-align:center;">
                            <button type="button" class="clean-btn clean-btn--sm" onclick="encerrarOS(${it.id})">Encerrar</button>
                        </td>
                    </tr>
                `).join("");
            }

            // CARREGAR HISTÓRICO
            const resH = await fetch('api/oficina_get_historico.php');
            const outH = await resH.json();
            if (outH.ok && tbodyHist) {
                tbodyHist.innerHTML = outH.items.map(it => `
                    <tr>
                        <td><b>#${it.id}</b></td>
                        <td style="font-size:0.75rem;">Abt: ${it.data_abertura}<br><b>Enc: ${it.data_encerramento}</b></td>
                        <td><b>${it.equipamento}</b><br><small>Setor: ${it.setor || '---'}</small></td>
                        <td>
                            <div style="font-size:0.8rem; color:#94a3b8;">Prob: "${it.descricao_problema}"</div>
                            <div style="font-weight:700;">Serviço: ${it.servico_executado}</div>
                        </td>
                        <td style="font-size:0.8rem;">Mecânico: ${it.mecanico}<br>Motorista: ${it.motorista_operador || '---'}</td>
                    </tr>
                `).join("");
            }
        } catch (e) { console.error("Erro ao carregar dados:", e); }
    }

    // MODAL ENCERRAMENTO
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

    // SUBMIT NOVA OS
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/oficina_abrir_os.php', { method: 'POST', body: new FormData(form) });
            const out = await res.json();
            if (out.ok) { 
                alert("OS da Oficina Aberta!"); 
                form.reset(); 
                // Reinicia a data após o reset
                inputAbertura.value = new Date(new Date().getTime() - (new Date().getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                carregarDados(); 
            }
            else { alert("Erro: " + out.error); }
        };
    }

    // SUBMIT ENCERRAMENTO
    const formEnc = document.getElementById('formEncerrarOS');
    if (formEnc) {
        formEnc.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/oficina_encerrar_os.php', { method: 'POST', body: new FormData(formEnc) });
            const out = await res.json();
            if (out.ok) { fecharModal(); alert("OS da Oficina Encerrada!"); carregarDados(); }
            else { alert("Erro: " + out.error); }
        };
    }

    // INICIALIZAÇÃO
    popularFrota();
    carregarDados();
})();
