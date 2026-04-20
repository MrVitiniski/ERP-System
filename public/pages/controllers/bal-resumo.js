(function() {
  var btn = document.getElementById('btnFiltrarResumo');

  async function carregarResumos() {
    var inicio = document.getElementById('res_data_inicio')?.value || "";
    var fim = document.getElementById('res_data_fim')?.value || "";
    var url = 'api/balanca_get_resumo.php';

    if (inicio && fim) url += "?inicio=" + encodeURIComponent(inicio) + "&fim=" + encodeURIComponent(fim);

    try {
      const res = await fetch(url, { cache: "no-store" });
      const out = await res.json();

      if (out.ok) {
        renderTabela('tbodyResumoCliente', 'cardsResumoCliente', out.por_cliente || [], 'cliente_nome', '#1e40af');
        renderTabela('tbodyResumoMaterial', 'cardsResumoMaterial', out.por_material || [], 'material_tipo', '#9a3412');
        await carregarEProcessarCotas(out.por_cliente || []);
      }
    } catch (e) {
      console.error("Erro ao carregar dados:", e);
    }
  }

  async function carregarEProcessarCotas(dadosCarregados) {
    const containerCotas = document.getElementById('lista-cotas-clientes');
    if (!containerCotas) return;

    try {
        const resCotas = await fetch('api/balanca_get_cotas_config.php', { cache: "no-store" });
        const configCotas = await resCotas.json();

        containerCotas.innerHTML = ''; 

        if (!configCotas || configCotas.length === 0) {
            containerCotas.innerHTML = '<p style="font-size:12px; color:#64748b; padding:10px;">Nenhuma cota ativa configurada para hoje.</p>';
            return;
        }

        configCotas.forEach(function(cota) {
            const registro = dadosCarregados.find(function(d) {
                return String(d.cliente_nome || "").toUpperCase() === String(cota.cliente_nome || "").toUpperCase();
            });
            
            // PADRONIZAÇÃO KG: Lemos o limite e o carregado como números inteiros
            const limite = Number(cota.limite_ton || 0);
            const carregado = registro ? Number(registro.ton || 0) : 0;
            
            // Cálculo do percentual sem divisões por 1000
            const percentual = Math.min((carregado / limite) * 100, 100);
            const restante = Math.max(limite - carregado, 0);

            let corBarra = "#10b981"; 
            if (percentual >= 100) corBarra = "#ef4444";
            else if (percentual >= 85) corBarra = "#f59e0b";

            // EXIBIÇÃO EM KG: Sem casas decimais (minimumFractionDigits: 0)
            containerCotas.innerHTML += `
                <div style="margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <div style="display:flex; justify-content:space-between; font-weight:800; font-size:13px; color:#1e293b;">
                        <span>🏢 ${cota.cliente_nome} <small style="font-weight:400; color:#64748b;">(${cota.data_inicio} a ${cota.data_fim})</small></span>
                        <span>${carregado.toLocaleString('pt-BR')} / ${limite.toLocaleString('pt-BR')} kg</span>
                    </div>
                    <div style="background:#e2e8f0; height:12px; border-radius:10px; overflow:hidden; margin:5px 0;">
                        <div style="width:${percentual}%; height:100%; background:${corBarra}; transition:width 1s ease-in-out;"></div>
                    </div>
                    <div style="text-align:right; font-size:11px; font-weight:bold; color:${restante <= 0 ? 'red' : '#64748b'};">
                        ${restante <= 0 ? '🚫 COTA ESGOTADA' : 'SALDO DISPONÍVEL: ' + restante.toLocaleString('pt-BR') + ' kg'}
                    </div>
                </div>
            `;
        });
    } catch (err) {
        containerCotas.innerHTML = '<p style="color:red; font-size:12px;">Erro ao carregar configurações de cotas.</p>';
    }
}

  // Função ajustada para KG (sem decimais)
  function fmtKg(v) {
    return Number(v || 0).toLocaleString('pt-BR') + " kg";
  }

  function renderTabela(tbodyId, cardsId, dados, campoNome, cor) {
    var tbody = document.getElementById(tbodyId);
    if (tbody) {
      tbody.innerHTML = dados.map(it => {
        const viagensFmt = Number(it?.viagens || 0).toLocaleString('pt-BR');
        return `
          <tr>
            <td style="padding:12px; font-weight:800; text-transform:uppercase;">${escapeHtml(it?.[campoNome])}</td>
            <td style="text-align:center; font-weight:bold;">${viagensFmt}</td>
            <td style="text-align:right; font-weight:900; color:${cor};">${fmtKg(it?.ton)}</td>
          </tr>
        `;
      }).join('') || '<tr><td colspan="3" style="text-align:center; padding:20px;">Nenhum registro.</td></tr>';
    }
  }

  function escapeHtml(s) {
    return String(s ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  if (btn) btn.onclick = carregarResumos;

  var ini = document.getElementById('res_data_inicio');
  var fim = document.getElementById('res_data_fim');
  if (ini && fim) {
    var d = new Date();
    fim.value = d.toISOString().split('T')[0];
    d.setDate(1);
    ini.value = d.toISOString().split('T')[0];
  }

  carregarResumos();
})();