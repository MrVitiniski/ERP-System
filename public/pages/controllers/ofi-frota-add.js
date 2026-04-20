const f = document.getElementById("fAddFrota");
const msg = document.getElementById("msg");
let listaFrotaGlobal = [];

// 1. SALVAR NOVO VEÍCULO
f.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    msg.style.color = "#475569";
    msg.textContent = "⌛ Salvando no banco de dados...";

    try {
        const res = await fetch('api/oficina_add_frota.php', {
            method: 'POST',
            body: new FormData(f)
        });
        const out = await res.json();

        if (out.ok) {
            msg.style.color = "#059669";
            msg.textContent = "✅ Veículo cadastrado com sucesso!";
            f.reset();
            // Atualiza a tabela automaticamente após salvar
            carregarFrota(); 
            // Limpa a mensagem após 4 segundos
            setTimeout(() => { msg.textContent = ""; }, 4000);
        } else {
            throw new Error(out.error);
        }
    } catch (err) {
        msg.style.color = "#dc2626";
        msg.textContent = "❌ Erro: " + err.message;
    }
});

// 2. CARREGAR TABELA DE FROTA
async function carregarFrota() {
    const tbody = document.getElementById('tbodyFrota');
    if(!tbody) return;

    try {
        const res = await fetch('api/oficina_get_frota.php');
        const out = await res.json();
        
        if (out.ok) {
            listaFrotaGlobal = out.items;
            if (listaFrotaGlobal.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">Nenhum veículo cadastrado na frota.</td></tr>';
                return;
            }

            tbody.innerHTML = listaFrotaGlobal.map(v => `
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                    <td style="font-weight:bold; color:#1e40af; padding:12px;">${v.placa}</td>
                    <td style="padding:12px;">${v.modelo}</td>
                    <td style="padding:12px;">${v.ano || '---'}</td>
                    <td style="padding:12px;">
                        <span style="font-size:10px; padding:3px 8px; border-radius:4px; font-weight:bold; text-transform:uppercase; 
                            background:${v.status === 'ativo' ? '#dcfce7' : '#fee2e2'}; 
                            color:${v.status === 'ativo' ? '#166534' : '#991b1b'}; border: 1px solid ${v.status === 'ativo' ? '#bbf7d0' : '#fecaca'};">
                            ${v.status}
                        </span>
                    </td>
                    <td style="text-align:right; padding:12px;">
                        <button type="button" class="clean-btn clean-btn--sm" onclick="verFicha('${v.placa}')" style="background:#3b82f6; padding: 5px 12px;">Ficha Técnica</button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) { 
        console.error("Erro ao buscar frota:", e);
        if(tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Falha ao carregar frota.</td></tr>';
    }
}

// 3. MOSTRAR MODAL COM DETALHES TÉCNICOS
window.verFicha = function(placa) {
    const v = listaFrotaGlobal.find(item => item.placa === placa);
    if (!v) return;

    document.getElementById('ficha_placa').textContent = v.placa;
    document.getElementById('conteudoFicha').innerHTML = `
        <div style="font-size:13px; line-height:1.6; color:#334155;">
            <div style="background:#f1f5f9; padding:10px; border-radius:6px; margin-bottom:15px;">
                <strong>🚙 Modelo:</strong> ${v.modelo} <br>
                <strong>📅 Ano:</strong> ${v.ano || '---'} | <strong>🔑 Chassi:</strong> ${v.chassi || '---'}
            </div>
            
            <h4 style="color:#b45309; margin: 15px 0 8px 0; border-bottom:1px solid #fed7aa; padding-bottom:4px;">⚙️ Especificação de Filtros</h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div><strong>Ar (Primário):</strong><br> ${v.filtro_ar_primario || '---'}</div>
                <div><strong>Ar (Secundário):</strong><br> ${v.filtro_ar_secundario || '---'}</div>
                <div><strong>Comb. (Racor):</strong><br> ${v.filtro_comb_racor || '---'}</div>
                <div><strong>Comb. (Secundário):</strong><br> ${v.filtro_comb_secundario || '---'}</div>
                <div style="grid-column: span 2;"><strong>Óleo Motor:</strong> ${v.filtro_oleo_motor || '---'}</div>
            </div>

            <h4 style="color:#1d4ed8; margin: 20px 0 8px 0; border-bottom:1px solid #bfdbfe; padding-bottom:4px;">🛢️ Lubrificação e Capacidades</h4>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div><strong>Óleo Motor:</strong><br> ${v.qtd_oleo_motor}L (${v.tipo_oleo_motor || '---'})</div>
                <div><strong>Óleo Hidráulico:</strong><br> ${v.qtd_oleo_hidraulico}L (${v.tipo_oleo_hidraulico || '---'})</div>
            </div>

            ${v.obs ? `
            <div style="background:#fff7ed; padding:10px; margin-top:20px; border-radius:4px; border-left:4px solid #f59e0b;">
                <strong>📝 Observações:</strong><br> ${v.obs}
            </div>` : ''}
        </div>
    `;
    document.getElementById('modalFicha').style.display = 'flex';
}

window.fecharFicha = function() { 
    document.getElementById('modalFicha').style.display = 'none'; 
}

// Inicialização
carregarFrota();
