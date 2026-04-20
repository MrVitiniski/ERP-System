// public/pages/controllers/sst-funcionarios-epi.js
(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const q = document.getElementById("q");
  const btnBuscar = document.getElementById("btnBuscar");
  const msg = document.getElementById("msg");
  const list = document.getElementById("list");
  const count = document.getElementById("count");

  if (!q || !btnBuscar || !msg || !list || !count) return;

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  async function getJson(url) {
    const res = await fetch(url, { credentials: "same-origin" });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    if (data.ok !== true) throw new Error(data.error || "Erro");
    return data;
  }

  function render(items) {
    count.textContent = items.length ? `${items.length} funcionário(s)` : "0 funcionários";

    if (!items.length) {
      list.innerHTML = `<div class="empty">Nenhum funcionário encontrado.</div>`;
      return;
    }

    list.innerHTML = `
      <table class="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th class="col-wide">Nome</th>
            <th>Setor</th>
            <th class="col-wide">Cargo</th>
            <th>Status</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
          ${items
            .map(
              (f) => `
            <tr>
              <td>${f.id}</td>
              <td class="col-wide">${escapeHtml(f.nome_completo)}</td>
              <td>${escapeHtml(f.setor || "")}</td>
              <td class="col-wide">${escapeHtml(f.cargo || "")}</td>
              <td><span class="pill">${escapeHtml(f.status || "")}</span></td>
              <td><button class="btn-mini" data-id="${f.id}">Abrir ficha</button></td>
            </tr>
          `
            )
            .join("")}
        </tbody>
      </table>
    `;

    list.querySelectorAll("button[data-id]").forEach((b) => {
      b.addEventListener("click", () => {
        const id = Number(b.dataset.id);
        if (!id) return;
        location.hash = `#/sst-ficha-epi?id=${id}`;
      });
    });
  }

  async function load() {
    msg.textContent = "Carregando...";
    try {
      const term = (q.value || "").trim();
      const url = `${BASE}/api/funcionarios_list_sst.php${term ? `?q=${encodeURIComponent(term)}` : ""}`;
      const data = await getJson(url);
      render(Array.isArray(data.items) ? data.items : []);
      msg.textContent = "";
    } catch (e) {
      msg.textContent = e.message || "Erro";
      list.innerHTML = "";
      count.textContent = "";
    }
  }

  btnBuscar.addEventListener("click", load);
  q.addEventListener("keydown", (ev) => {
    if (ev.key === "Enter") load();
  });

  load();
})();