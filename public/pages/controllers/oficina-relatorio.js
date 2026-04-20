// controllers/oficina-relatorio.js
console.log("Controlador de Relatorio da Oficina Carregado!");

document.addEventListener('click', async function(e) {
    
    // BOTAO GERAR DADOS
    if (e.target && e.target.id === 'btnGerarRelatorio') {
        const de = document.getElementById('rel_data_de').value;
        const ate = document.getElementById('rel_data_ate').value;
        const tbody = document.getElementById('tbodyRelatorio');

        if (!de || !ate) return alert("Por favor, selecione as datas.");

        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#1e40af; font-weight:bold;">🔍 Buscando dados da Oficina...</td></tr>';

        try {
            const res = await fetch('api/oficina_get_relatorio.php?inicio=' + de + '&fim=' + ate);
            if (!res.ok) throw new Error('Erro na rede');
            
            const dados = await res.json();

            if (dados.erro) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:red; padding:20px;">Erro: ' + dados.erro + '</td></tr>';
                return;
            }

            if (!Array.isArray(dados) || dados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:40px; color:#64748b;">Nenhum registro encontrado na Oficina para este período.</td></tr>';
                return;
            }

            tbody.innerHTML = dados.map(os => {
    const statusTexto = os.status ? os.status.toUpperCase() : 'N/A';
    const isAberto = statusTexto.includes('ABERT');
    
    return `
    <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
        <td style="font-weight:bold; color: #1e40af; padding: 12px;">#${os.id}</td>
        <td>${os.data_abertura ? new Date(os.data_abertura).toLocaleDateString('pt-BR') : '--'}</td>
        <td style="font-weight:bold;">${os.equipamento || '---'}<br><small style="color:#64748b; font-weight:normal;">Setor: ${os.setor || 'N/I'}</small></td>
        <td>${os.mecanico || '---'}</td>
        <td>
          <span style="font-size:10px; padding:2px 6px; border-radius:3px; font-weight:bold; 
            background:${isAberto ? '#fff7ed' : '#dcfce7'}; 
            color:${isAberto ? '#c2410c' : '#166534'}; border: 1px solid ${isAberto ? '#ffedd5' : '#bbf7d0'};">
            ${os.status}
          </span>
        </td>
        <td style="text-align:right; padding-right:12px;">${os.data_encerramento ? new Date(os.data_encerramento).toLocaleDateString('pt-BR') : '--'}</td>
    </tr>
    <tr>
        <td colspan="6" style="padding: 15px 25px; background: #fff; border-bottom: 2px solid #cbd5e0; font-size: 12px; line-height: 1.6;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <div>
                    <strong style="color: #e53e3e; font-size: 10px;">🚨 PROBLEMA RELATADO:</strong><br>
                    <div style="color: #334155; white-space: pre-wrap;">${os.descricao_problema || 'Não informado.'}</div>
                </div>
                <div>
                    <strong style="color: #059669; font-size: 10px;">🛠️ SERVIÇO EXECUTADO:</strong><br>
                    <div style="color: #1e293b; font-weight: 500; white-space: pre-wrap;">${os.servico_executado || 'Pendente.'}</div>
                </div>
                
                <!-- SEÇÃO DE OBSERVAÇÕES (ADICIONADA) -->
                <div style="grid-column: span 2; border-top: 1px dashed #cbd5e0; margin-top: 10px; padding-top: 10px;">
                    <div style="display: flex; gap: 20px; color: #64748b; font-size: 11px;">
                        <span><strong>👤 MOTORISTA:</strong> ${os.motorista_operador || '---'}</span>
                        <span><strong>🚩 PRIORIDADE:</strong> ${os.prioridade || 'Normal'}</span>
                    </div>
                    ${os.observacao ? `
                    <div style="margin-top: 8px; padding: 8px; background: #f1f5f9; border-left: 4px solid #94a3b8; border-radius: 4px;">
                        <strong style="font-size: 10px; color: #475569;">📝 OBSERVAÇÕES TÉCNICAS:</strong><br>
                        <span style="color: #334155;">${os.observacao}</span>
                    </div>` : ''}
                </div>
            </div>
        </td>
    </tr>`;
}).join('');

        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:red;">Falha ao carregar dados da Oficina.</td></tr>';
        }
    }

    // BOTAO PDF (CORRIGIDO PARA EXIBIR TUDO)
    if (e.target && e.target.id === 'btnPDF') {
        const tableBody = document.getElementById('tbodyRelatorio').innerHTML;
        const de = document.getElementById('rel_data_de').value;
        const ate = document.getElementById('rel_data_ate').value;

        const win = window.open('', '', 'height=800,width=1000');
        win.document.write(`
            <html>
            <head>
                <title>Relatório Oficina</title>
                <style>
                    body { font-family: sans-serif; padding: 20px; color: #333; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th { background: #f1f5f9; border: 1px solid #ddd; padding: 10px; font-size: 11px; text-align: left; }
                    td { border: 1px solid #ddd; padding: 10px; font-size: 11px; vertical-align: top; }
                    h2 { color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 5px; }
                    .details-box { background: #fff; padding: 10px; }
                </style>
            </head>
            <body>
                <h2>Relatório Gerencial de Manutenção</h2>
                <p style="font-size: 12px;">Período: <b>${de}</b> até <b>${ate}</b></p>
                <table>
                    <thead>
                        <tr>
                            <th>OS</th><th>Data</th><th>Equipamento</th><th>Mecânico</th><th>Status</th><th>Encerramento</th>
                        </tr>
                    </thead>
                    <tbody>${tableBody}</tbody>
                </table>
            </body>
            </html>
        `);
        win.document.close();
        setTimeout(() => { win.print(); win.close(); }, 700);
    }
});
