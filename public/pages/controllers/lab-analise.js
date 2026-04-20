(function() {
    const form = document.getElementById("fLabAnalise");
    const msg = document.getElementById("msgLab");

    // 1. PREENCHER DATA AUTOMATICAMENTE AO CARREGAR
    // Isso garante que o campo de data já venha preenchido com o dia de hoje
    const dataProducaoInput = document.getElementById('data_producao');
    if (dataProducaoInput && !dataProducaoInput.value) {
        dataProducaoInput.value = new Date().toISOString().split('T')[0];
    }

    // 2. CÁLCULO AUTOMÁTICO DE DENSIDADE
    window.calcularDensidade = function() {
        const peso = parseFloat(document.getElementById('peso_seco').value) || 0;
        const vol = parseFloat(document.getElementById('volume').value) || 0;
        const res = document.getElementById('densidade_res');
        
        if (peso > 0 && vol > 0) {
            res.value = (peso / vol).toFixed(9); 
        } else {
            res.value = "";
        }
    };

    // 3. SALVAR ANÁLISE
    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();

            msg.style.color = "#475569";
            msg.textContent = "⌛ Processando análise química...";

            try {
                // Enviamos o formulário. O PHP e o Banco de Dados 
                // cuidarão de registrar o horário exato no campo 'data_registro'.
                const res = await fetch('api/lab_salvar_analise.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const out = await res.json();

                if (out.ok) {
                    msg.style.color = "#059669";
                    msg.textContent = out.mensagem || "✅ Análise salva com sucesso!";

                    // Limpa campos específicos após salvar, se necessário, 
                    // ou mantém para a próxima análise.
                    
                } else {
                    throw new Error(out.error || "Erro desconhecido");
                }
            } catch (err) {
                msg.style.color = "#dc2626";
                msg.textContent = "❌ Erro ao salvar: " + err.message;
            }
        };
    }
})();
