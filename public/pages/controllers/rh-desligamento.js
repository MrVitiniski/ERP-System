(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const q = document.getElementById("qFuncionario");
  const list = document.getElementById("acList");

  const funcId = document.getElementById("funcId");
  const funcNome = document.getElementById("funcNome");
  const funcCpf = document.getElementById("funcCpf");
  const funcAdmissao = document.getElementById("funcAdmissao");

  const tipo = document.getElementById("tipo");
  const dataDeslig = document.getElementById("dataDeslig");
  const motivo = document.getElementById("motivo");
  const obs = document.getElementById("obs");

  const msg = document.getElementById("msgDeslig");
  const btnLimpar = document.getElementById("btnDesligLimpar");
  const btnSalvar = document.getElementById("btnDesligSalvar");

  const histQ = document.getElementById("histQ");
  const histTipo = document.getElementById("histTipo");
  const histTable = document.getElementById("tblDeslig");
  const histTbody = document.querySelector("#tblDeslig tbody");

  if (!q || !list || !btnSalvar || !histTbody) return;

  let debounceTimer = null;
  let lastItems = [];
  let histDebounce = null;

  function showMsg(text, type) {
    msg.style.display = "block";
    msg.className =
      "clean-alert " +
      (type === "ok" ? "clean-alert--ok" : type === "error" ? "clean-alert--error" : "");
    msg.textContent = text;
  }
  function hideMsg() {
    msg.style.display = "none";
    msg.textContent = "";
  }

  function openList() { list.classList.add("is-open"); }
  function closeList() { list.classList.remove("is-open"); }

  function digits(s) { return String(s || "").replace(/\D/g, ""); }
  function fmtCpf(cpf) {
    const d = digits(cpf);
    if (d.length !== 11) return cpf || "";
    return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
  }

  function todayISO() {
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth() + 1).padStart(2, "0");
    const d = String(t.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }

  function clearFuncionario() {
    funcId.value = "";
    funcNome.value = "";
    funcCpf.value = "";
    funcAdmissao.value = "";
  }

  function clearForm() {
    tipo.value = "";
    dataDeslig.value = "";
    motivo.value = "";
    obs.value = "";
  }

  function renderList(items) {
    lastItems = items || [];
    if (!lastItems.length) {
      list.innerHTML = `<div class="ac__item"><div><div class="ac__primary">Nenhum resultado</div><div class="ac__secondary">Tente outro termo.</div></div></div>`;
      openList();
      return;
    }

    list.innerHTML = lastItems
      .map((it) => {
        const pillClass = (it.status || "").toLowerCase() === "inativo" ? "ac__pill ac__pill--off" : "ac__pill";
        return `
          <div class="ac__item" role="option" data-id="${it.id}">
            <div style="min-width:0;">
              <div class="ac__primary">${it.nome_completo || ""}</div>
              <div class="ac__secondary">CPF: ${fmtCpf(it.cpf)} • Admissão: ${it.data_admissao || "—"}</div>
            </div>
            <div class="${pillClass}">${(it.status || "ativo").toUpperCase()}</div>
          </div>
        `;
      })
      .join("");

    openList();
  }

  async function doSearch(term) {
    const t = term.trim();
    if (t.length < 2 && digits(t).length < 3) {
      closeList();
      return;
    }

    const res = await fetch(`${BASE}/api/rh_funcionarios_search.php?q=${encodeURIComponent(t)}`, {
      cache: "no-store",
      credentials: "same-origin",
    });

    const out = await res.json().catch(() => ({}));
    if (!res.ok || !out.ok) {
      renderList([]);
      return;
    }
    renderList(out.items || []);
  }

  function selectFuncionarioById(id) {
    const it = lastItems.find((x) => String(x.id) === String(id));
    if (!it) return;

    funcId.value = it.id;
    funcNome.value = it.nome_completo || "";
    funcCpf.value = fmtCpf(it.cpf || "");
    funcAdmissao.value = it.data_admissao || "";
    dataDeslig.value = todayISO();

    showMsg("Funcionário selecionado.", "ok");
  }

  function fmtTipo(t) {
    if (t === "demissao") return "Demissão";
    if (t === "sem_justa_causa") return "Sem justa causa";
    if (t === "justa_causa") return "Justa causa";
    return t || "";
  }

  async function loadHistorico() {
    const qv = (histQ?.value || "").trim();
    const tv = (histTipo?.value || "").trim();

    const url = new URL(`${BASE}/api/rh_desligamento_list.php`, window.location.origin);
    if (qv) url.searchParams.set("q", qv);
    if (tv) url.searchParams.set("tipo", tv);
    url.searchParams.set("limit", "80");

    const res = await fetch(url.toString(), { cache: "no-store", credentials: "same-origin" });
    const out = await res.json().catch(() => ({}));

    if (!res.ok || !out.ok) {
      histTbody.innerHTML = `<tr><td colspan="9">Erro ao carregar histórico.</td></tr>`;
      return;
    }

    const items = out.items || [];
    histTbody.innerHTML = items
      .map((it) => {
        const isCancelado = String(it.status || "") === "cancelado";
        const btn = isCancelado
          ? `<button class="clean-btn-icon clean-btn-icon--danger" disabled>Cancelado</button>`
          : `<button class="clean-btn-icon clean-btn-icon--danger" data-action="cancel" data-id="${it.id}">Cancelar</button>`;

        return `
          <tr>
            <td>${it.id}</td>
            <td>${it.nome_completo || ""}</td>
            <td>${fmtCpf(it.cpf || "")}</td>
            <td>${it.data_admissao || ""}</td>
            <td>${it.data_desligamento || ""}</td>
            <td>${fmtTipo(it.tipo)}</td>
            <td title="${String(it.observacoes || "").replaceAll('"', '&quot;')}">${it.motivo || ""}</td>
            <td>${String(it.status_funcionario || "").toUpperCase()}</td>
            <td>${btn}</td>
          </tr>
        `;
      })
      .join("");
  }

  // ===== Eventos =====

  // Autocomplete
  q.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => doSearch(q.value), 200);
  });

  list.addEventListener("click", (ev) => {
    const item = ev.target.closest(".ac__item");
    if (!item) return;
    const id = item.getAttribute("data-id");
    if (!id) return;
    closeList();
    selectFuncionarioById(id);
  });

  document.addEventListener("click", (ev) => {
    if (!ev.target.closest(".ac")) closeList();
  });

  // Histórico filtros
  if (histQ) {
    histQ.addEventListener("input", () => {
      clearTimeout(histDebounce);
      histDebounce = setTimeout(loadHistorico, 200);
    });
  }
  if (histTipo) {
    histTipo.addEventListener("change", loadHistorico);
  }

  // Botão cancelar na tabela (ESSA É A “ÚLTIMA PARTE” — já está no lugar certo aqui)
  histTable?.addEventListener("click", async (ev) => {
    const btn = ev.target.closest("button[data-action='cancel']");
    if (!btn) return;

    const desligamentoId = btn.getAttribute("data-id");
    if (!desligamentoId) return;

    const ok = confirm(`Cancelar o desligamento #${desligamentoId} e reativar o funcionário?`);
    if (!ok) return;

    showMsg("Cancelando desligamento...", "");

    const res = await fetch(`${BASE}/api/rh_desligamento_cancel.php`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ desligamento_id: parseInt(desligamentoId, 10) }),
    });

    const out = await res.json().catch(() => ({}));
    if (!res.ok || !out.ok) {
      showMsg("Erro ao cancelar: " + (out.error || res.status), "error");
      return;
    }

    showMsg("Desligamento cancelado e funcionário reativado.", "ok");
    loadHistorico();
  });

  // Botões do formulário
  btnLimpar.addEventListener("click", () => {
    q.value = "";
    clearFuncionario();
    clearForm();
    hideMsg();
    closeList();
  });

  btnSalvar.addEventListener("click", async () => {
    hideMsg();

    if (!funcId.value) return showMsg("Selecione um funcionário.", "error");
    if (!tipo.value) return showMsg("Selecione o tipo de desligamento.", "error");
    if (!dataDeslig.value) return showMsg("Informe a data do desligamento.", "error");
    if (!motivo.value.trim()) return showMsg("Informe o motivo do desligamento.", "error");

    const payload = {
      funcionario_id: parseInt(funcId.value, 10),
      tipo: tipo.value,
      data_desligamento: dataDeslig.value,
      motivo: motivo.value.trim(),
      observacoes: obs.value.trim() || null,
    };

    showMsg("Salvando...", "");

    const res = await fetch(`${BASE}/api/rh_desligamento_save.php`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const out = await res.json().catch(() => ({}));
    if (!res.ok || !out.ok) {
      showMsg("Erro ao salvar: " + (out.error || res.status), "error");
      return;
    }

    showMsg(`Desligamento registrado! ID: ${out.id} (funcionário inativado)`, "ok");
    clearForm();
    loadHistorico();
  });

  // init
  clearFuncionario();
  clearForm();
  loadHistorico();
})();