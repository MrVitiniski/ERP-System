(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const msg = document.getElementById("msg");
  const tbody = document.querySelector("table tbody");
  const q = document.getElementById("q");
  const btnBuscar = document.getElementById("btnBuscar") || document.getElementById("btnReload");

  if (!tbody || !q) return;

  function showMsg(text, type) {
    if (!msg) return;
    msg.style.display = "block";
    msg.className =
      "clean-alert " +
      (type === "ok" ? "clean-alert--ok" : type === "error" ? "clean-alert--error" : "");
    msg.textContent = text;
  }

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function badgeStatus(status) {
    const s = String(status || "").toLowerCase();
    if (s === "ativo") return `<span class="clean-badge clean-badge--ok">Ativo</span>`;
    if (s === "inativo") return `<span class="clean-badge clean-badge--off">Inativo</span>`;
    return `<span class="clean-badge">${escapeHtml(status || "")}</span>`;
  }

  function render(items) {
    const term = (q.value || "").trim().toLowerCase();

    const filtered = !term
      ? items
      : items.filter((it) => {
          const hay = [
            it.id,
            it.nome_completo,
            it.cpf,
            it.cargo,
            it.setor,
            it.status,
          ]
            .join(" ")
            .toLowerCase();
          return hay.includes(term);
        });

    if (!filtered.length) {
      tbody.innerHTML = `<tr><td colspan="7">Nenhum funcionário encontrado.</td></tr>`;
      showMsg("Nenhum funcionário encontrado.", "ok");
      return;
    }

    tbody.innerHTML = filtered
      .map(
        (it) => `
          <tr>
            <td><b>${escapeHtml(it.id)}</b></td>
            <td>${escapeHtml(it.nome_completo || "")}</td>
            <td>${escapeHtml(it.cpf || "")}</td>
            <td>${escapeHtml(it.cargo || "")}</td>
            <td>${escapeHtml(it.setor || "")}</td>
            <td>${escapeHtml(it.data_admissao || "")}</td>
            <td>${badgeStatus(it.status)}</td>
          </tr>
        `
      )
      .join("");

    showMsg(`Total: ${filtered.length} funcionário(s)`, "ok");
  }

  async function load() {
    showMsg("Carregando...", "");
    tbody.innerHTML = `<tr><td colspan="7">Carregando dados...</td></tr>`;

    // IMPORTANTE: ajuste o endpoint abaixo para o que você usa no RH listar
    // Ex.: rh_list.php / funcionarios_list.php etc.
    const url = `${BASE}/api/rh_list.php`;

    const res = await fetch(url, {
      cache: "no-store",
      credentials: "same-origin",
    });

    const text = await res.text();

    let out = {};
    try {
      out = JSON.parse(text);
    } catch {
      out = {};
    }

    if (!res.ok || out.ok !== true) {
      console.error("[rh_list] status:", res.status, "response:", text);
      showMsg("Erro ao carregar lista: " + (out.error || out.message || `HTTP ${res.status}`), "error");
      tbody.innerHTML = `<tr><td colspan="7">Falha ao carregar.</td></tr>`;
      return;
    }

    window.__RH_FUNC_ITEMS__ = Array.isArray(out.items) ? out.items : [];
    render(window.__RH_FUNC_ITEMS__);
  }

  btnBuscar?.addEventListener("click", load);
  q.addEventListener("input", () => render(window.__RH_FUNC_ITEMS__ || []));

  load();
})();