// public/pages/controllers/alm-pedidos-epi.js
(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const msg = document.getElementById("msg");
  const list = document.getElementById("list");
  const count = document.getElementById("count");
  const btnReload = document.getElementById("btnReload");

  if (!msg || !list || !count || !btnReload) {
    console.warn("[alm-pedidos-epi] elementos não encontrados (view ainda não montou?)");
    return;
  }

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  async function api(path, body) {
    const url = `${BASE}${path}`;
    console.log("[alm] fetch:", url);

    const res = await fetch(url, {
      method: body ? "POST" : "GET",
      headers: body ? { "Content-Type": "application/json; charset=utf-8" } : {},
      credentials: "same-origin",
      body: body ? JSON.stringify(body) : undefined,
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) throw new Error(data.error || data.message || `HTTP ${res.status}`);
    if (data.ok !== true) throw new Error(data.error || data.message || "Erro");

    return data;
  }

  function render(rows) {
    count.textContent = rows.length ? `${rows.length} pendente(s)` : "0 pendentes";

    if (!rows.length) {
      list.innerHTML = `<div class="empty">Nenhum pedido pendente no momento.</div>`;
      return;
    }

    list.innerHTML = `
      <table class="tbl">
        <thead>
          <tr>
            <th>#</th>
            <th class="col-wide">Colaborador</th>
            <th>Setor</th>
            <th class="col-wide">EPI</th>
            <th>Qtd</th>
            <th>Data</th>
            <th class="col-wide">Obs</th>
            <th>Status</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map(r => `
            <tr>
              <td>${r.id}</td>
              <td class="col-wide">${escapeHtml(r.colaborador)}</td>
              <td>${escapeHtml(r.setor || "")}</td>
              <td class="col-wide">${escapeHtml(r.epi)}</td>
              <td>${r.qtd}</td>
              <td>${escapeHtml(r.data_pedido)}</td>
              <td class="col-wide">${escapeHtml(r.obs || "")}</td>
              <td><span class="pill">${escapeHtml(r.status)}</span></td>
              <td><button class="btn-mini" data-id="${r.id}">Entregar</button></td>
            </tr>
          `).join("")}
        </tbody>
      </table>
    `;

    list.querySelectorAll("button[data-id]").forEach((b) => {
      b.addEventListener("click", async () => {
        const id = Number(b.dataset.id);
        if (!id) return;

        const ok = confirm(`Confirmar ENTREGA do pedido #${id}?`);
        if (!ok) return;

        b.disabled = true;
        msg.textContent = `Entregando pedido #${id}...`;

        try {
          await api("/api/epi_pedidos_entregar.php", { id });
          msg.textContent = `Pedido #${id} marcado como ENTREGUE.`;
          await load();
        } catch (e) {
          msg.textContent = e.message || "Falha ao entregar.";
        } finally {
          b.disabled = false;
        }
      });
    });
  }

  async function load() {
    msg.textContent = "Carregando pedidos...";
    btnReload.disabled = true;

    try {
      const data = await api("/api/epi_pedidos_list.php");
      render(Array.isArray(data.data) ? data.data : []);
      msg.textContent = "";
    } catch (e) {
      msg.textContent = e.message || "Erro ao carregar pedidos.";
      list.innerHTML = "";
      count.textContent = "";
    } finally {
      btnReload.disabled = false;
    }
  }

  btnReload.addEventListener("click", load);
  load();
})();