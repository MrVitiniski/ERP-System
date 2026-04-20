(function() {
    const selectItem = document.getElementById('saida_item');

    // Função para buscar os nomes dos itens cadastrados
    async function carregarItensParaSaida() {
        try {
            // Reutilizamos a API que lista tudo (criada para o inventário)
            const res = await fetch('api/estoque_lista_geral.php');
            const out = await res.json();

            if (out.ok && selectItem) {
                selectItem.innerHTML = '<option value="">Selecione o item...</option>';
                
                // Preenche o select com Nome (e mostra o saldo atual entre parênteses)
                out.items.forEach(it => {
                    const option = document.createElement('option');
                    option.value = it.nome; // O valor que o PHP vai receber
                    option.textContent = `${it.nome} (Saldo: ${it.estoque_atual})`;
                    selectItem.appendChild(option);
                });
            }
        } catch (err) {
            console.error("Erro ao carregar lista de itens:", err);
        }
    }

    // Chama a função ao carregar o controller
    carregarItensParaSaida();

    
})();


(function() {
    const form = document.getElementById('formSaida');
    const inputCod = document.getElementById('saida_cod_barras');
    const msgSaida = document.getElementById('msgSaida');

    if (form) {
        form.onsubmit = async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);

            try {
                const res = await fetch('api/estoque_baixa.php', {
                    method: 'POST',
                    body: formData
                });
                
                const out = await res.json();

                if (out.ok) {
                    // Alerta visual de sucesso
                    if(msgSaida) {
                        msgSaida.textContent = `📤 Baixa confirmada! Novo saldo: ${out.novo_saldo}`;
                        msgSaida.style.display = 'block';
                    }
                    
                    alert("✅ Saída registrada com sucesso!");
                    form.reset();
                    if(inputCod) inputCod.focus();
                    
                    setTimeout(() => { if(msgSaida) msgSaida.style.display = 'none'; }, 4000);
                } else {
                    alert("❌ Erro: " + out.error);
                }
            } catch (err) {
                console.error(err);
                alert("❌ Erro de conexão com a API de saída.");
            }
        };
    }
})();
