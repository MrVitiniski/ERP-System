(function() {
    console.log("Controller de Cotas carregado com sucesso.");

    // Lógica para Listar as cotas existentes
    async function listarCotas() {
        const tbody = document.getElementById('listaCotasCadastradas');
        if (!tbody) return;

        try {
            const res = await fetch('api/balanca_get_cotas_config.php', { cache: "no-store" });
            const dados = await res.json();
            
            if(!dados || dados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">Nenhuma cota cadastrada.</td></tr>';
                return;
            }

            tbody.innerHTML = dados.map(c => {
                // Tratamos o limite vindo do banco (ex: 5000)
                const limiteKg = parseFloat(c.limite_ton) || 0;
                const limiteTon = limiteKg / 1000;

                return `
                  <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:12px; font-weight:800; text-transform:uppercase; color:#0f172a;">${c.cliente_nome}</td>
                    <td style="padding:12px; font-weight:700; color:#1e40af;">
                        ${limiteKg.toLocaleString('pt-BR')} kg (${limiteTon.toLocaleString('pt-BR', {minimumFractionDigits:1})} t)
                    </td>
                    <td style="padding:12px; font-size: 13px; color:#475569;">${fmtDataBr(c.data_inicio)} até ${fmtDataBr(c.data_fim)}</td>
                    <td style="padding:12px; text-align:center;">
                        <button class="btn-excluir-cota" data-id="${c.id}" style="color:#ef4444; border:1px solid #fee2e2; background:#fef2f2; padding:5px 10px; border-radius:5px; cursor:pointer; font-weight:bold;">🗑️ Excluir</button>
                    </td>
                  </tr>
                `;
            }).join('');

            // Adiciona evento aos botões de excluir
            document.querySelectorAll('.btn-excluir-cota').forEach(btn => {
                btn.onclick = () => excluirCota(btn.getAttribute('data-id'));
            });

        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:20px; color:red;">Erro ao conectar com a API.</td></tr>';
        }
    }

    // Lógica para Salvar
    const form = document.getElementById('formCota');
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            // Criamos o FormData
            const fd = new FormData(e.target);
            
            // AJUSTE CRÍTICO: Remove o ponto de milhar (ex: 5.000 vira 5000) antes de enviar
            const limiteRaw = fd.get('limite_ton') || "0";
            const limiteLimpo = limiteRaw.replace(/\./g, ""); 
            fd.set('limite_ton', limiteLimpo);

            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = '⏳ SALVANDO...';

            try {
                const res = await fetch('api/balanca_cota_salvar.php', { method: 'POST', body: fd });
                const out = await res.json();
                if(out.ok) { 
                    alert('Cota salva com sucesso!');
                    e.target.reset();
                    listarCotas();
                } else {
                    alert('Erro ao salvar: ' + (out.error || out.erro || 'Erro desconhecido'));
                }
            } catch(err) {
                alert('Erro ao salvar cota.');
            } finally {
                btn.disabled = false;
                btn.innerText = '➕ SALVAR COTA';
            }
        };
    }

    async function excluirCota(id) {
        if(confirm('Deseja realmente remover esta cota?')) {
            await fetch('api/balanca_cota_excluir.php?id=' + id);
            listarCotas();
        }
    }

    function fmtDataBr(data) {
        if(!data) return "";
        const p = data.split("-");
        return p[2] + "/" + p[1] + "/" + p[0];
    }

    // Inicializa a lista
    listarCotas();

})();