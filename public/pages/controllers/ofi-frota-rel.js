(function() {
    let frotaCache = [];
    const tbody = document.getElementById('tbodyRelatorioFrota');
    const inputBusca = document.getElementById('campoBusca');

    async function buscarFrota() {
        try {
            const res = await fetch('api/oficina_get_frota.php');
            const out = await res.json();
            if (out.ok) {
                frotaCache = out.items;
                renderizarTabela(frotaCache);
            }
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">Erro ao conectar com o banco.</td></tr>';
        }
    }

    function renderizarTabela(lista) {
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">Nenhum veículo encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = lista.map(v => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="font-weight:bold; color:#1e40af; padding:12px;">${v.placa}</td>
                <td>${v.modelo}</td>
                <td>
                    <span style="font-size:10px; padding:2px 6px; border-radius:4px; font-weight:bold; text-transform:uppercase; 
                        background:${v.status === 'ativo' ? '#dcfce7' : '#fee2e2'}; color:${v.status === 'ativo' ? '#166534' : '#991b1b'};">
                        ${v.status}
                    </span>
                </td>
                <td>${v.ano || '---'}</td>
                <td style="text-align:right;">
                    <button type="button" class="clean-btn clean-btn--sm" onclick="verFichaRel('${v.placa}')" style="background:#3b82f6;">Ver Detalhes</button>
                </td>
            </tr>
        `).join('');
    }

    // Filtro em tempo real (Enquanto digita)
    inputBusca.addEventListener('input', () => {
        const termo = inputBusca.value.toLowerCase().trim();
        const filtrados = frotaCache.filter(v => 
            v.placa.toLowerCase().includes(termo) || 
            v.modelo.toLowerCase().includes(termo)
        );
        renderizarTabela(filtrados);
    });

    document.getElementById('btnAtualizar').onclick = buscarFrota;

    // Função global para o Modal
   window.verFichaRel = function(placa) {
    const v = frotaCache.find(item => item.placa === placa);
    if (!v) return;

    document.getElementById('rel_placa').textContent = v.placa;
    document.getElementById('rel_conteudo_ficha').innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 14px; line-height: 1.6; color: #1e293b;">
            
            <!-- CABEÇALHO DA FICHA -->
            <div style="grid-column: span 2; background: #f1f5f9; padding: 15px; border-radius: 6px; border: 1px solid #cbd5e0; color: #0f172a;">
                <strong style="color: #475569; font-size: 11px; text-transform: uppercase;">Equipamento / Modelo:</strong><br>
                <span style="font-size: 18px; font-weight: bold;">${v.modelo}</span>
                <div style="margin-top: 5px; border-top: 1px solid #cbd5e0; padding-top: 5px;">
                    <strong style="color: #475569; font-size: 11px;">CHASSI:</strong> ${v.chassi || 'NÃO INFORMADO'}
                </div>
            </div>
            
            <!-- COLUNA FILTROS -->
            <div style="border: 2px solid #fbbf24; padding: 15px; border-radius: 8px; background: #fffdfa;">
                <h4 style="color:#92400e; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #fde68a; padding-bottom: 5px;">
                   ⚙️ Filtros
                </h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div><b style="color: #475569;">Ar (Primário):</b><br> <span style="color: #000;">${v.filtro_ar_primario || '---'}</span></div>
                    <div><b style="color: #475569;">Ar (Secundário):</b><br> <span style="color: #000;">${v.filtro_ar_secundario || '---'}</span></div>
                    <div><b style="color: #475569;">Comb. (Racor):</b><br> <span style="color: #000;">${v.filtro_comb_racor || '---'}</span></div>
                    <div><b style="color: #475569;">Comb. (Secundário):</b><br> <span style="color: #000;">${v.filtro_comb_secundario || '---'}</span></div>
                    <div><b style="color: #475569;">Óleo Motor:</b><br> <span style="color: #000;">${v.filtro_oleo_motor || '---'}</span></div>
                </div>
            </div>

            <!-- COLUNA ÓLEOS -->
            <div style="border: 2px solid #3b82f6; padding: 15px; border-radius: 8px; background: #f0f7ff;">
                <h4 style="color:#1e40af; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #bfdbfe; padding-bottom: 5px;">
                   🛢️ Lubrificação
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <b style="color: #475569;">Óleo do Motor:</b><br> 
                        <span style="font-size: 16px; font-weight: bold; color: #000;">${v.qtd_oleo_motor}L</span> 
                        <br><small style="color: #1e40af; font-weight: bold;">Tipo: ${v.tipo_oleo_motor || '---'}</small>
                    </div>
                    <div style="border-top: 1px solid #bfdbfe; padding-top: 10px;">
                        <b style="color: #475569;">Óleo Hidráulico:</b><br> 
                        <span style="font-size: 16px; font-weight: bold; color: #000;">${v.qtd_oleo_hidraulico}L</span>
                        <br><small style="color: #1e40af; font-weight: bold;">Tipo: ${v.tipo_oleo_hidraulico || '---'}</small>
                    </div>
                </div>
            </div>

            <!-- OBSERVAÇÕES -->
            <div style="grid-column: span 2; background: #fff7ed; padding: 12px; border-left: 5px solid #f59e0b; border-radius: 4px; color: #78350f;">
                <strong>📝 OBSERVAÇÕES TÉCNICAS:</strong><br>
                <div style="margin-top: 5px; font-size: 13px;">${v.obs || 'Nenhuma observação cadastrada.'}</div>
            </div>
        </div>
    `;
    document.getElementById('modalFichaRel').style.display = 'flex';
}

    window.fecharFichaRel = () => document.getElementById('modalFichaRel').style.display = 'none';

    buscarFrota();
})();
