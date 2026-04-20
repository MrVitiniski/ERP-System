(function() {
    const tbody = document.querySelector("#tabelaRelatorio tbody");

    async function loadRelatorio() {
        if (!tbody) return;

        try {
            const res = await fetch('api/relatorio_diario.php');
            const out = await res.json();

            if (out.ok) {
                // Atualiza os cards de resumo
                document.getElementById("resumo_entradas").textContent = out.resumo.entradas;
                document.getElementById("resumo_saidas").textContent = out.resumo.saidas;
                document.getElementById("resumo_total").textContent = out.resumo.total;

                // Preenche a tabela
                tbody.innerHTML = out.items.map(it => `
                    <tr>
                        <td style="color:#64748b">${it.data_hora}</td>
                        <td>
                            <span style="padding:4px 8px; border-radius:15px; font-size:10px; font-weight:bold; background:${it.tipo=='ENTRADA'?'#dcfce7':'#fff1f2'}; color:${it.tipo=='ENTRADA'?'#166534':'#9f1239'}">
                                ${it.tipo}
                            </span>
                        </td>
                        <td><b>${it.descricao}</b></td>
                        <td style="text-align:center"><b>${it.quantidade}</b></td>
                        <td>${it.colaborador || '-'} <br><small>${it.setor || '-'}</small></td>
                    </tr>
                `).join("");
            }
        } catch (err) {
            console.error("Erro ao carregar relatório:", err);
        }
    }

    loadRelatorio();
})();
