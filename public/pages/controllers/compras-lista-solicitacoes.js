(() => {
  const BASE = "/sistema/public";
  const msg = document.getElementById("msg");
  const tbody = document.querySelector("#tbl tbody");
  const q = document.getElementById("q");
  const btnReload = document.getElementById("btnReload");

  async function render(items) {
    const term = (q?.value || "").trim().toLowerCase();
    const filtered = !term ? items : items.filter(it => {
      const hay = [it.numero_interno, it.nome_requerente, it.solicitante_nome, it.item_solicitado, it.fornecedor].join(" ").toLowerCase();
      return hay.includes(term);
    });

    if (!tbody) return;
    tbody.innerHTML = filtered.map(it => `
      <tr>
        <td><b>${it.numero_interno || it.id}</b></td>
        <td>${it.data_pedido || ""}</td>
        <td>${it.nome_requerente || ""}</td>
        <td>${it.solicitante_nome || ""}</td>
        <td>${it.item_solicitado || ""}</td>
        <td>${it.fornecedor || ""}</td>
        <td style="text-align:center;">
          <button class="clean-btn clean-btn--sm" onclick="window.open('${BASE}/api/compras_solicitacao_print.php?id=${it.id}', '_blank')">Imprimir</button>
        </td>
      </tr>
    `).join("");
    
    if (msg) {
        msg.style.display = "block";
        msg.textContent = `Total: ${filtered.length}`;
    }
  }

  async function load() {
    if (msg) { msg.style.display = "block"; msg.textContent = "Carregando..."; }
    try {
        const res = await fetch(`${BASE}/api/compras_list.php`);
        const out = await res.json().catch(() => ({}));
        if (out.ok) {
          window.__COMPRAS_ITEMS__ = out.items || [];
          render(window.__COMPRAS_ITEMS__);
        }
    } catch (e) {
        if (msg) msg.textContent = "Erro ao carregar lista.";
    }
  }

  btnReload?.addEventListener("click", load);
  q?.addEventListener("input", () => render(window.__COMPRAS_ITEMS__ || []));
  
  load();
})();
