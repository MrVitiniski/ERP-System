(function() {
    const tbody = document.querySelector("#tabelaEstoque tbody");

    async function carregarInventario() {
        if (!tbody) return;

        try {
            const res = await fetch('api/estoque_lista_geral.php');
            const out = await res.json();

            if (out.ok) {
                tbody.innerHTML = out.items.map(it => {
                    // 1. AJUSTE: Trocar 'quantidade' por 'estoque_atual'
                    const qtdAtual = Number(it.estoque_atual || 0);
                    const qtdMinima = Number(it.estoque_minimo || 0);
                    
                    const critico = qtdAtual <= qtdMinima;
                    const statusClass = critico ? 'background: #fee2e2; color: #991b1b;' : 'background: #dcfce7; color: #166534;';
                    const statusText = critico ? 'REPOR URGENTE' : 'ESTOQUE OK';

                    return `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px; font-family: monospace; color: #6366f1;">${it.cod_barras || '-'}</td>
                            <td style="padding: 12px;">
                                <!-- 2. AJUSTE: Trocar 'descricao' por 'nome' -->
                                <div style="font-weight: 600; color: #1e293b;">${it.nome || it.descricao || 'Sem Nome'}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">NF: ${it.nf || 'S/ NF'}</div>
                            </td>
                            <td style="padding: 12px;">
                                <div style="font-size: 0.85rem;">${it.segmento || '-'}</div>
                                <div style="font-size: 0.75rem; color: #94a3b8;">${it.setor_destino || '-'}</div>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="display: block; font-size: 0.85rem; font-weight: 500;">${it.localizacao || '-'}</span>
                                <span style="font-size: 0.7rem; color: #94a3b8;">${it.andar_nivel || '-'}</span>
                            </td>
                            <!-- 3. AJUSTE: Trocar 'it.quantidade' por 'it.estoque_atual' -->
                            <td style="padding: 12px; text-align: center; font-weight: 700;">${it.estoque_atual}</td>
                            <td style="padding: 12px; text-align: center; color: #94a3b8;">${it.estoque_minimo}</td>
                            <td style="padding: 12px; text-align: center;">
                                <span style="${statusClass} padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">${statusText}</span>
                            </td>
                        </tr>
                    `;
                }).join("");
            }
        } catch (err) {
            console.error("Erro ao carregar inventário:", err);
        }
    }

    carregarInventario();
})();
