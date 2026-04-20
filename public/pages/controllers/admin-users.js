// Local: sistema/public/pages/controllers/admin-users.js
console.log("🚀 Controller de Usuários (com Excluir) Ativo!");

// Usamos var para evitar o erro de "already declared"
var formUser = null;
var tbodyUsers = null;

setTimeout(function() {
    formUser = document.getElementById("fUsuario");
    tbodyUsers = document.getElementById('tbodyUsuarios');

    // 1. FUNÇÃO PARA CARREGAR A LISTA
    async function carregarUsuarios() {
        if (!tbodyUsers) return;
        try {
            // Ajuste o caminho se necessário (../../api/)
            const res = await fetch('/sistema/public/api/usuarios_lista.php');
            const out = await res.json();
            
            if (out.ok) {
                tbodyUsers.innerHTML = out.items.map(u => `
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 15px; color:#1e293b; font-weight: 600;">${u.nome}</td>
                        <td style="padding: 15px;"><strong>${u.usuario}</strong></td>
                        <td style="padding: 15px;"><span style="color:#2563eb; font-weight:bold; font-size: 11px;">${u.role}</span></td>
                        <td style="padding: 15px; text-align: center;">
                             <span style="background:${u.status === 'ativo' ? '#dcfce7' : '#fee2e2'}; color:${u.status === 'ativo' ? '#166534' : '#991b1b'}; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:900;">
                                ${u.status.toUpperCase()}
                             </span>
                        </td>
                        <!-- COLUNA DE AÇÕES COM O BOTÃO DE EXCLUIR -->
                        <td style="padding: 15px; text-align: center;">
                            <button onclick="window.excluirUsuario(${u.id}, '${u.usuario}')" 
                                    style="background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 11px;">
                                🗑️ EXCLUIR
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        } catch (err) { console.error("Erro ao listar:", err); }
    }

    // 2. FUNÇÃO PARA EXCLUIR (Global para o botão funcionar)
    window.excluirUsuario = async function(id, login) {
        if (confirm(`⚠️ Deseja realmente excluir o usuário "${login}"?`)) {
            try {
                const res = await fetch('/sistema/public/api/usuarios_excluir.php?id=' + id);
                const out = await res.json();
                if (out.ok) {
                    alert(out.mensagem);
                    carregarUsuarios(); // Atualiza a lista
                } else {
                    alert("Erro: " + out.error);
                }
            } catch (err) { console.error("Erro ao excluir:", err); }
        }
    };

   // 3. EVENTO DE SALVAR (Ajustado para múltiplos setores)
if (formUser) {
    formUser.onsubmit = async function(e) {
        e.preventDefault();
        
        // Criamos o objeto de dados do formulário
        const formData = new FormData(this);
        
        // Verificamos se pelo menos um setor foi marcado antes de enviar
        const setoresMarcados = formData.getAll('roles[]');
        if (setoresMarcados.length === 0) {
            alert("⚠️ Selecione pelo menos um setor/nível de acesso!");
            return;
        }

        console.log("💾 Salvando usuário com setores:", setoresMarcados);

        try {
            // Usamos o caminho absoluto que funcionou para você
            const res = await fetch('/sistema/public/api/usuarios_salvar.php', {
                method: 'POST',
                body: formData
            });

            const out = await res.json();

            if (out.ok) {
                alert(out.mensagem || "✅ Usuário cadastrado com sucesso!");
                this.reset();
                
                // Se a função carregarUsuarios existir no arquivo, ela atualiza a lista
                if (typeof carregarUsuarios === "function") {
                    carregarUsuarios();
                }
            } else {
                alert("Erro ao salvar: " + out.error);
            }
        } catch (err) {
            console.error("❌ Erro na requisição:", err);
            alert("Erro de comunicação com o servidor.");
        }
    };
}


    // Inicializa a lista
    carregarUsuarios();

}, 400);
