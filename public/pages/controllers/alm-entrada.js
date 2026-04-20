(function() {
    const form = document.getElementById('formEntrada');
    const inputCod = document.getElementById('cod_barras');

    if (form) {
        // Remove qualquer evento antigo para não duplicar
        form.onsubmit = null;

        form.onsubmit = async function(e) {
            e.preventDefault();
            console.log("Botão clicado, enviando dados..."); // Verificação no console

            const formData = new FormData(form);

            try {
                const res = await fetch('api/estoque_salvar.php', {
                    method: 'POST',
                    body: formData
                });
                
                const out = await res.json();

                if (out.ok) {
                    alert("✅ Item cadastrado com sucesso!");
                    form.reset();
                    if(inputCod) inputCod.focus();
                } else {
                    alert("❌ Erro ao salvar: " + out.error);
                }
            } catch (err) {
                console.error(err);
                alert("❌ Erro de conexão com a API.");
            }
        };
    }
})();
