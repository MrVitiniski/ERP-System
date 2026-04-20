(() => {

  const BASE = "/sistema/public";
  
  const f = document.getElementById("frmCompra"); 
  const msg = document.getElementById("msg");
  const tbody = document.getElementById("tbody");
  const q = document.getElementById("q");
  const btnReload = document.getElementById("btnReload");

  function render(items) {
    if (!tbody) return;

    const term = (q?.value || "").trim().toLowerCase();

    const filtered = !term ? items : items.filter(it => {
      const hay = [
        it.razao,
        it.cnpj,
        it.contato,
        it.cidade_uf
      ].join(" ").toLowerCase();

      return hay.includes(term);
    });

    tbody.innerHTML = filtered.map(it => `
      <tr>
        <td><b>${it.razao || ""}</b></td>
        <td>${it.cnpj || ""}</td>
        <td>${it.contato || ""}</td>
        <td>${it.telefone || ""}</td>
        <td>${it.cidade_uf || ""}</td>
        <td style="text-align:center;">
          <button class="clean-btn clean-btn--sm">Editar</button>
        </td>
      </tr>
    `).join("");

    if (msg) msg.textContent = `Total: ${filtered.length} fornecedores`;
  }

  async function load() {
    if (msg) msg.textContent = "Carregando...";

    try {

      const res = await fetch(`${BASE}/api/fornecedores_list.php`);
      const out = await res.json().catch(() => ({}));

      if (out.ok) {

        window.__FORN_ITEMS__ = out.items || [];
        render(window.__FORN_ITEMS__);

      } else {

        if (msg) msg.textContent = "Erro: " + (out.error || "falha na API");

      }

    } catch (e) {

      if (msg) msg.textContent = "Erro de rede ao carregar.";

    }
  }

  q?.addEventListener("input", () => {
    render(window.__FORN_ITEMS__ || []);
  });

  btnReload?.addEventListener("click", load);

  // ⭐ SALVAR FORNECEDOR
  f?.addEventListener("submit", async (e) => {

    e.preventDefault();

    const formData = new FormData(f);

    const data = {
      razao: formData.get("razao"),
      cnpj: formData.get("cnpj"),
      contato: formData.get("contato"),
      telefone: formData.get("telefone"),
      email: formData.get("email"),
      cidade_uf: formData.get("cidade_uf")
    };

    if (msg) msg.textContent = "Salvando...";

    try {

      const res = await fetch(`${BASE}/api/fornecedor_salvar.php`, {

        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(data)

      });

      const out = await res.json();

      if (out.ok) {

        if (msg) msg.textContent = "Fornecedor salvo com sucesso.";

        f.reset();

        load();

      } else {

        if (msg) msg.textContent = "Erro: " + out.error;

      }

    } catch (e) {

      if (msg) msg.textContent = "Erro de conexão.";

    }

  });

  load();

})();