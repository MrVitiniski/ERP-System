// controllers/mec-relatorio.js
console.log("Controlador de Relatorio Carregado!");

document.addEventListener('click', async function(e) {
    
    // BOTAO GERAR DADOS
    if (e.target && e.target.id === 'btnGerarRelatorio') {
        const de = document.getElementById('rel_data_de').value;
        const ate = document.getElementById('rel_data_ate').value;
        const tbody = document.getElementById('tbodyRelatorio');

        if (!de || !ate) return alert("Por favor, selecione as datas.");

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#1e40af;">Buscando dados...</td></tr>';

        try {
            const res = await fetch('api/mecanica_get_relatorio.php?inicio=' + de + '&fim=' + ate);
            if (!res.ok) throw new Error('Erro na rede');
            
            const dados = await res.json();

            if (dados.erro) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red;">Erro: ' + dados.erro + '</td></tr>';
                return;
            }

            if (!Array.isArray(dados) || dados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px;">Nenhum registro encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = dados.map(os => {
                const statusTexto = os.status ? os.status.toUpperCase() : 'N/A';
                const isAberto = statusTexto.includes('ABERT');
                
                return `
    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
        <td style="font-weight:bold; color: #1e40af;">#${os.id}</td>
        <td>${os.data_abertura ? new Date(os.data_abertura).toLocaleDateString('pt-BR') : '--'}</td>
        <td style="font-weight:bold;">${os.equipamento || '---'}</td>
        <td>${os.mecanico || '---'}</td>
        <td>
          <span style="font-size:10px; padding:2px 6px; border-radius:3px; font-weight:bold; 
            background:${os.status === 'ABERTA' ? '#fff7ed' : '#dcfce7'}; 
            color:${os.status === 'ABERTA' ? '#c2410c' : '#166534'};">
            ${os.status}
          </span>
        </td>
        <td style="text-align:right;">${os.data_encerramento ? new Date(os.data_encerramento).toLocaleDateString('pt-BR') : '--'}</td>
    </tr>
    <tr>
        <td colspan="6" style="padding: 15px; background: #fff; border-bottom: 2px solid #e2e8f0; font-size: 11px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>🚨 PROBLEMA RELATADO:</strong><br>
                    ${os.descricao_problema || 'Não informado.'}
                </div>
                <div>
                    <strong>🛠️ SERVIÇO EXECUTADO:</strong><br>
                    ${os.servico_executado || 'Pendente.'}
                </div>
                <div style="grid-column: span 2; border-top: 1px dashed #eee; pt: 5px; color: #64748b;">
                    <strong>📝 OBSERVAÇÕES:</strong> ${os.observacao || 'Nenhuma.'} | 
                    <strong>👤 SOLICITANTE:</strong> ${os.solicitante || '---'} |
                    <strong>🚩 PRIORIDADE:</strong> ${os.prioridade || 'Normal'}
                </div>
            </div>
        </td>
    </tr>
`;
            }).join('');

        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:red;">Falha ao carregar dados.</td></tr>';
        }
    }

    // BOTAO PDF
    if (e.target && e.target.id === 'btnPDF') {
        const table = document.getElementById('tabelaRelatorioFinal').innerHTML;
        const win = window.open('', '', 'height=700,width=900');
        win.document.write('<html><head><title>Relatorio</title><style>table{width:100%;border-collapse:collapse;font-family:sans-serif;} td,th{border:1px solid #eee;padding:8px;font-size:11px;}</style></head><body><h2>Relatorio de Manutencao</h2><table>' + table + '</table></body></html>');
        win.document.close();
        setTimeout(() => { win.print(); }, 500);
    }
});
