(function() {
    const tbody = document.getElementById('tbodyHistoricoBalanca');
    const btnAtu = document.getElementById('btnAtualizarBalanca');

    async function carregarHistoricoBalanca() {
        if (!tbody) return;

        try {
            const res = await fetch('api/balanca_get_historico.php');
            const out = await res.json();

            if (out.ok && out.items) {
                if (out.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="padding:30px; text-align:center;">Nenhuma pesagem registrada.</td></tr>';
                    return;
                }

                tbody.innerHTML = out.items.map(it => {
                    // Cálculo do Líquido
                    const liqValue = it.peso_saida > 0 ? Math.abs(it.peso_entrada - it.peso_saida) : 0;
                    
                    // --- FORMATAÇÃO DOS PESOS COM PONTO DE MILHAR ---
                    // Transformamos os valores em números e aplicamos o padrão brasileiro
                    const entradaFormatada = Number(it.peso_entrada).toLocaleString('pt-BR');
                    const saidaFormatada   = it.peso_saida > 0 ? Number(it.peso_saida).toLocaleString('pt-BR') : '-';
                    const liquidoFormatado = liqValue.toLocaleString('pt-BR');

                    const statusStyle = it.status === 'aberto' 
                        ? 'background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;' 
                        : 'background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;';
                    
                    return `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 15px;">ID: ${it.id}<br><strong>${it.placa_cavalo}</strong></td>
                        <td style="padding: 15px;">NF: ${it.nf_numero || '-'}<br><small>${it.material_tipo || '-'}</small></td>
                        <td style="padding: 15px;">${it.motorista_nome}<br><small>${it.transportadora}</small></td>
                        <td style="padding: 15px;">${entradaFormatada} kg</td>
                        <td style="padding: 15px;">${it.peso_saida > 0 ? saidaFormatada + ' kg' : '-'}</td>
                        <td style="padding: 15px; font-weight:bold; color: #1e40af;">${it.peso_saida > 0 ? liquidoFormatado + ' kg' : '-'}</td>
                        <td style="padding: 15px; text-align: center;">
                            <span style="${statusStyle}">${it.status.toUpperCase()}</span>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <button onclick="window.open('api/balanca_print.php?id=${it.id}', '_blank')" 
                                    style="background: #1e293b; color: #fff; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                🖨️ Ticket
                            </button>
                        </td>
                    </tr>`;
                }).join('');
            } else {
                throw new Error(out.error || "Falha ao carregar dados.");
            }
        } catch (e) { 
            console.error("Erro no Histórico Balança:", e);
            tbody.innerHTML = `<tr><td colspan="6" style="padding:30px; text-align:center; color:red;">Erro: ${e.message}</td></tr>`;
        }
    }

    if (btnAtu) btnAtu.onclick = carregarHistoricoBalanca;
    carregarHistoricoBalanca();
})();