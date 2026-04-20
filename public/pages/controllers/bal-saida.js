(function() {
    var formSaida = document.getElementById("fSaidaBalanca");
    var inputTkt = document.getElementById('id_pesagem_busca');
    var inputPlc = document.getElementById('placa_saida');
    var pesoSaidaInput = document.getElementById('peso_saida');
    var btnSalvar = document.getElementById('btnSalvarSaida');
    var pesoEntradaAtual = 0;

    async function buscar(parametro) {
        console.log("🔍 Tentando buscar: " + parametro);
        try {
            const res = await fetch('api/balanca_get_pendente.php?' + parametro);
            const out = await res.json();
            
            console.log("📦 Resposta da API:", out);

            if (out.ok && out.dados) {
                document.getElementById('id_pesagem').value = out.dados.id;
                document.getElementById('txt_motorista').innerText = out.dados.motorista_nome;
                document.getElementById('txt_transp').innerText = out.dados.transportadora;
                document.getElementById('txt_peso_ent').innerText = out.dados.peso_entrada;
                
                pesoEntradaAtual = parseFloat(out.dados.peso_entrada);
                
                var nfMat = (out.dados.nf_numero || 'S/NF') + " | " + (out.dados.material_tipo || 'S/MAT');
                document.getElementById('txt_material').innerText = nfMat.toUpperCase();

                document.getElementById('dados_veiculo').style.display = 'block';
                btnSalvar.disabled = false;
                btnSalvar.style.opacity = "1";
                btnSalvar.style.cursor = "pointer";
            } else {
                alert("Nenhuma pesagem em aberto localizada para " + parametro);
                document.getElementById('dados_veiculo').style.display = 'none';
                btnSalvar.disabled = true;
            }
        } catch (err) { console.error("❌ Erro no Fetch:", err); }
    }

    if (inputTkt) inputTkt.onchange = function() { if(this.value) buscar('id=' + this.value); };
    if (inputPlc) inputPlc.onchange = function() { if(this.value) buscar('placa=' + this.value.toUpperCase()); };

    if (pesoSaidaInput) {
        pesoSaidaInput.oninput = function() {
            var saida = parseFloat(this.value) || 0;
            var liquido = Math.abs(pesoEntradaAtual - saida);
            document.getElementById('txt_peso_liquido').innerText = liquido + " KG";
        };
    }

    if (formSaida) {
        formSaida.onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch('api/balanca_finalizar_saida.php', {
                method: 'POST',
                body: new FormData(formSaida)
            });
            const out = await res.json();
            if (out.ok) { alert(out.mensagem); window.location.reload(); }
        };
    }
})();
