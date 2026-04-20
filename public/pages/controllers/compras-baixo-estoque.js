(function() {
    // Seleciona os elementos da sua tabela de compras
    const tbody = document.querySelector("#lista_compras_urgente");
    const contador = document.getElementById("total_critico");

    async function carregarListaNegra() {
        if (!tbody) return;

        try {
            // Chama a API que criamos anteriormente
            // O caminho depende de onde o index.php principal está rodando
            const res = await fetch('api/estoque_baixo.php');
            const out = await res.json();

            if (out.ok) {
                // Atualiza o contador de itens críticos no topo (ex: 02)
                if(contador) contador.textContent = out.items.length.toString().padStart(2, '0');

                // Se não houver itens, mostra uma mensagem amigável
                if (out.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748b;">✅ Tudo em dia! Nenhum item abaixo do estoque mínimo.</td></tr>';
                    return;
                }

                // Preenche a tabela com os itens que o PHP retornou
                tbody.innerHTML = out.items.map(it => `
                    <tr style="border-bottom: 1px solid #fee2e2;">
    <td style="padding: 12px;">
        <!-- Troquei it.descricao por it.nome -->
        <div style="font-weight: 600; color: #1e293b;">${it.nome || it.descricao}</div>
        <div style="font-size: 0.75rem; color: #64748b;">Ref: ${it.cod_barras || 'S/ cod'}</div>
    </td>
    <td style="padding: 12px; font-size: 0.85rem; color: #475569;">${it.segmento || '-'}</td>
    <!-- Troquei it.quantidade por it.estoque_atual -->
    <td style="padding: 12px; text-align: center; font-weight: 700; color: #e11d48;">${it.estoque_atual} un</td>
    <td style="padding: 12px; text-align: center; color: #64748b;">${it.estoque_minimo} un</td>
    <td style="padding: 12px; text-align: center;">
        <!-- Ajustei o cálculo da sugestão de compra -->
        <input type="number" class="clean-input" value="${Number(it.estoque_minimo) - Number(it.estoque_atual)}" style="width: 60px; text-align: center; padding: 4px; border: 1px solid #fecdd3;">
    </td>
    <td style="padding: 12px; text-align: center;">
        <button class="clean-btn clean-btn--sm clean-btn--primary">Comprar</button>
    </td>
</tr>
                `).join("");
            }
        } catch (err) {
            console.error("Erro ao carregar lista de compras:", err);
            if(tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Erro ao conectar com o servidor.</td></tr>';
        }
    }

    // Executa a carga dos dados assim que o controller é chamado
    carregarListaNegra();

})();
