(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const q = document.getElementById("qFuncionario");
  const list = document.getElementById("acList");

  const funcId = document.getElementById("funcId");
  const funcNome = document.getElementById("funcNome");
  const funcCpf = document.getElementById("funcCpf");
  const funcAdmissao = document.getElementById("funcAdmissao");

  const podeAPartir = document.getElementById("podeAPartir");
  const aquisInicio = document.getElementById("aquisInicio");
  const aquisFim = document.getElementById("aquisFim");
  const feriasVencida = document.getElementById("feriasVencida");

  const hAquisInicio = document.getElementById("hAquisInicio");
  const hAquisFim = document.getElementById("hAquisFim");

  const gozoInicio = document.getElementById("gozoInicio");
  const dias = document.getElementById("dias");
  const gozoFim = document.getElementById("gozoFim");
  const retorno = document.getElementById("retorno");
  const obs = document.getElementById("obs");

  const msg = document.getElementById("msgFerias");
  const btnLimpar = document.getElementById("btnFeriasLimpar");
  const btnSalvar = document.getElementById("btnFeriasSalvar");

  if (!q || !list || !btnSalvar) return;

  let lastItems = [];
  let debounceTimer = null;

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

  function parseISODate(s) {
    if (!s) return null;
    const [y, m, d] = String(s).split("-").map((x) => parseInt(x, 10));
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
  }
  function toISODate(dt) {
    if (!(dt instanceof Date) || isNaN(dt)) return "";
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, "0");
    const d = String(dt.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }
  function addDays(dt, n) {
    const d = new Date(dt.getTime());
    d.setDate(d.getDate() + n);
    return d;
  }

  function recalcFimRetorno() {
    const ini = parseISODate(gozoInicio.value);
    const nDias = parseInt(dias.value || "30", 10);
    if (!ini || !nDias || nDias <= 0) {
      gozoFim.value = "";
      retorno.value = "";
      return;
    }
    const end = addDays(ini, nDias - 1);
    gozoFim.value = toISODate(end);
    retorno.value = toISODate(addDays(end, 1));
  }

  function setFuncionarioFieldsEmpty() {
    funcId.value = "";
    funcNome.value = "";
    funcCpf.value = "";
    funcAdmissao.value = "";
    podeAPartir.value = "";
    aquisInicio.value = "";
    aquisFim.value = "";
    feriasVencida.value = "—";
    hAquisInicio.value = "";
    hAquisFim.value = "";

    gozoInicio.value = "";
    dias.value = "30";
    gozoFim.value = "";
    retorno.value = "";
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
    hideMsg();

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

  async function loadPreview(funcionario_id) {
    showMsg("Calculando férias...", "");

    const res = await fetch(`${BASE}/api/rh_ferias_preview.php`, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ funcionario_id }),
    });

    const out = await res.json().catch(() => ({}));
    if (!res.ok || !out.ok) {
      showMsg("Erro ao calcular: " + (out.error || res.status), "error");
      return;
    }

    const f = out.funcionario;
    const c = out.calculo;

    funcId.value = f.id;
    funcNome.value = f.nome_completo || "";
    funcCpf.value = fmtCpf(f.cpf || "");
    funcAdmissao.value = f.data_admissao || "";

    podeAPartir.value = c.pode_a_partir || "";
    aquisInicio.value = c.aquisitivo_inicio || "";
    aquisFim.value = c.aquisitivo_fim || "";
    feriasVencida.value = c.ferias_vencida ? "Sim" : "Não";

    hAquisInicio.value = c.aquisitivo_inicio || "";
    hAquisFim.value = c.aquisitivo_fim || "";

    gozoInicio.value = c.inicio_sugerido || "";
    dias.value = String(c.dias_sugerido || 30);
    recalcFimRetorno();

    if (c.ferias_vencida) {
      showMsg("Atenção: Férias vencidas para o período atual.", "error");
    } else if (c.ja_registrada_para_periodo) {
      showMsg("Já existe férias registrada para este período aquisitivo.", "error");
    } else {
      showMsg("Ok. Sugestão preenchida.", "ok");
    }
  }

  // Events
  q.addEventListener("input", () => {
    const term = q.value;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => doSearch(term), 200);
  });

  // click em um item
  list.addEventListener("click", (ev) => {
    const item = ev.target.closest(".ac__item");
    if (!item) return;
    const id = item.getAttribute("data-id");
    if (!id) return;

    closeList();
    loadPreview(parseInt(id, 10));
  });

  // fechar lista clicando fora
  document.addEventListener("click", (ev) => {
    if (!ev.target.closest(".ac")) closeList();
  });

  // recalcular fim/retorno se usuário ajustar inicio/dias
  gozoInicio.addEventListener("change", recalcFimRetorno);
  dias.addEventListener("input", recalcFimRetorno);

  btnLimpar.addEventListener("click", () => {
    q.value = "";
    setFuncionarioFieldsEmpty();
    hideMsg();
    closeList();
  });

  btnSalvar.addEventListener("click", async () => {
    hideMsg();

    if (!funcId.value) {
      showMsg("Selecione um funcionário na lista primeiro.", "error");
      return;
    }

    const payload = {
      funcionario_id: parseInt(funcId.value, 10),
      aquisitivo_inicio: hAquisInicio.value,
      aquisitivo_fim: hAquisFim.value,
      gozo_inicio: gozoInicio.value,
      dias: parseInt(dias.value || "30", 10),
      observacoes: obs.value || null,
    };

    if (!payload.aquisitivo_inicio || !payload.aquisitivo_fim || !payload.gozo_inicio) {
      showMsg("Dados incompletos. Selecione o funcionário novamente.", "error");
      return;
    }

    showMsg("Salvando férias...", "");

    const res = await fetch(`${BASE}/api/rh_ferias_save.php`, {
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

    showMsg(`Férias registradas com sucesso! ID: ${out.id}`, "ok");
  });

  // init
  setFuncionarioFieldsEmpty();
})();