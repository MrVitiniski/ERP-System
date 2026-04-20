// public/pages/controllers/sst-ficha-epi.js
(() => {
  const BASE = window.APP_BASE || "/sistema/public";
  const HIST_ENDPOINT = `${BASE}/api/epi_entregas_by_funcionario.php`;
  const FUNC_LIST_ENDPOINT = `${BASE}/api/funcionarios_list_sst.php`;

  const elWho = document.getElementById("who");
  const elMeta = document.getElementById("meta");
  const elMsg = document.getElementById("msg");
  const elList = document.getElementById("list");
  const elBtnVoltar = document.getElementById("btnVoltar");

  if (!elWho || !elMeta || !elMsg || !elList || !elBtnVoltar) {
    console.warn("[sst-ficha-epi] elementos não encontrados");
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

  function setMsg(text) {
    elMsg.textContent = text || "";
  }

  function getHashQueryParam(name) {
    const hash = window.location.hash || "";
    const idx = hash.indexOf("?");
    if (idx === -1) return null;
    return new URLSearchParams(hash.slice(idx + 1)).get(name);
  }

  async function getJson(url) {
    const res = await fetch(url, { credentials: "same-origin", cache: "no-store" });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || data.message || `HTTP ${res.status}`);
    if (data.ok !== true) throw new Error(data.error || data.message || "Erro");
    return data;
  }

  function fmtDate(dt) {
    const s = String(dt || "").replace("T", " ");
    return s.length >= 16 ? s.slice(0, 16) : s;
  }

  function render(items) {
    if (!items.length) {
      elList.innerHTML = `<div class="empty">Nenhuma entrega registrada para este funcionário.</div>`;
      return;
    }

    const totalQtd = items.reduce((acc, it) => acc + Number(it.qtd || 0), 0);

    elList.innerHTML = `
      <div class="epi-wrap">
        <div class="epi-panel">
          <div class="epi-head">
            <div>
              <div class="epi-title">Histórico de Entregas</div>
              <div class="epi-sub">Mais recente primeiro</div>
            </div>
            <div class="epi-badge">Registros: ${items.length} · Qtd total: ${totalQtd}</div>
          </div>

          <table class="epi-table">
            <thead>
              <tr>
                <th>Data</th>
                <th class="col-center">Pedido</th>
                <th class="col-wide">EPI</th>
                <th class="col-center">Qtd</th>
                <th class="col-wide">Observação</th>
              </tr>
            </thead>
            <tbody>
              ${items.map(it => `
                <tr>
                  <td class="epi-muted">${escapeHtml(fmtDate(it.entregue_em))}</td>
                  <td class="col-center epi-muted">${escapeHtml(String(it.pedido_id || ""))}</td>
                  <td class="col-wide epi-strong">${escapeHtml(it.epi || "")}</td>
                  <td class="col-center epi-strong">${escapeHtml(String(it.qtd ?? ""))}</td>
                  <td class="col-wide">${escapeHtml(it.obs || "")}</td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }

  async function loadFuncionario(funcionarioId) {
    const out = await getJson(FUNC_LIST_ENDPOINT);
    const items = Array.isArray(out.items) ? out.items : [];
    const f = items.find((x) => Number(x.id) === Number(funcionarioId));
    if (!f) return null;

    return {
      id: Number(f.id),
      nome: String(f.nome_completo || "").trim(),
      setor: String(f.setor || "").trim(),
      cargo: String(f.cargo || "").trim(),
      status: String(f.status || "").trim(),
    };
  }

  elBtnVoltar.addEventListener("click", () => {
    location.hash = "#/sst-funcionarios-epi";
  });

  const funcionarioId = Number(getHashQueryParam("id") || 0);
  if (!funcionarioId) {
    elWho.textContent = "Funcionário não informado";
    elMeta.textContent = "—";
    setMsg("ID do funcionário não informado.");
    elList.innerHTML = "";
    return;
  }

  elWho.textContent = "Carregando funcionário...";
  elMeta.textContent = `ID: ${funcionarioId}`;
  setMsg("Carregando histórico...");
  elList.innerHTML = "";

  (async () => {
    try {
      const [hist, func] = await Promise.all([
        getJson(`${HIST_ENDPOINT}?funcionario_id=${encodeURIComponent(String(funcionarioId))}`),
        loadFuncionario(funcionarioId).catch(() => null),
      ]);

      const items = Array.isArray(hist.items) ? hist.items : [];

      if (func?.nome) {
        elWho.textContent = func.nome;
        const parts = [`ID: ${func.id}`];
        if (func.status) parts.push(`Status: ${func.status}`);
        if (func.setor) parts.push(`Setor: ${func.setor}`);
        if (func.cargo) parts.push(`Cargo: ${func.cargo}`);
        elMeta.textContent = parts.join(" · ");
      } else {
        elWho.textContent = `Funcionário #${funcionarioId}`;
        elMeta.textContent = `ID: ${funcionarioId}`;
      }

      render(items);
      setMsg("");
    } catch (e) {
      console.error(e);
      elWho.textContent = `Funcionário #${funcionarioId}`;
      elMeta.textContent = `ID: ${funcionarioId}`;
      setMsg(e?.message || "Erro ao carregar histórico.");
      elList.innerHTML = "";
    }
  })();
})();