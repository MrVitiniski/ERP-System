// 1. Limpamos lixo de memória para evitar erros de redeclaração
var formEntrada = null;
var inputPlaca = null;
var spanTicket = null;

// 2. Iniciamos a lógica
console.log("🚀 Iniciando lógica de Entrada com Ticket Automático...");

formEntrada = document.getElementById("fEntradaBalanca");
inputPlaca = document.getElementById('placa_cavalo');
spanTicket = document.getElementById('num_ticket_gerado');

/**
 * FUNÇÃO PARA BUSCAR O PRÓXIMO NÚMERO DE TICKET DISPONÍVEL
 */
async function atualizarProximoTicket() {
    if (!spanTicket) return;
    try {
        const res = await fetch('api/balanca_get_proximo_id.php');
        const out = await res.json();
        if (out.ok) {
            spanTicket.innerText = out.proximo;
            console.log("🎫 Próximo ticket disponível:", out.proximo);
        }
    } catch (err) {
        console.error("❌ Erro ao buscar próximo ID:", err);
    }
}

// Chama a função assim que carregar o script
atualizarProximoTicket();

/**
 * BUSCA DE DADOS POR PLACA
 */
if (inputPlaca) {
    inputPlaca.onchange = async function() {
        const placa = this.value.trim().toUpperCase();
        if (placa.length < 7) return;

        console.log("🔍 Buscando dados para a placa:", placa);
        try {
            const res = await fetch('api/balanca_get_veiculo.php?placa=' + placa);
            const out = await res.json();

            if (out.ok && out.dados) {
                document.getElementById('motorista_nome').value = out.dados.motorista_nome || '';
                document.getElementById('motorista_doc').value = out.dados.motorista_doc || '';
                document.getElementById('transportadora').value = out.dados.transportadora || '';
                document.getElementById('placa_carreta').value = out.dados.placa_carreta || '';
                console.log("✅ Dados recuperados com sucesso.");
            }
        } catch (err) { 
            console.error("❌ Erro na busca:", err); 
        }
    };
}

/**
 * SALVAMENTO DA ENTRADA
 */
if (formEntrada) {
    formEntrada.onsubmit = async function(e) {
        e.preventDefault();
        console.log("💾 Salvando entrada...");

        try {
            const formData = new FormData(this);
            const res = await fetch('api/balanca_salvar_entrada.php', {
                method: 'POST',
                body: formData
            });
            const out = await res.json();

            // --- LOCAL DA ALTERAÇÃO AQUI ---
            if (out.ok) {
                // Pergunta se quer imprimir ANTES de resetar o formulário
                if (confirm("✅ Entrada registrada! Deseja imprimir o ticket agora?")) {
                    window.open('api/balanca_print.php?id=' + out.id, '_blank');
                }
                
                this.reset();
                // Após salvar, busca o novo número disponível para o próximo caminhão
                atualizarProximoTicket();
            } else {
                alert("Erro ao salvar: " + out.error);
            }
            // -------------------------------

        } catch (err) { 
            alert("Erro de comunicação com o servidor."); 
        }
    };
}