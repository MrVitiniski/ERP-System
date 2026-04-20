(function () {
  const $ = (id) => document.getElementById(id);

  const frentista = $("frentista");
  const dataHora = $("dataHora");
  const qFrota = $("qFrota");
  const frotaId = $("frotaId");
  const acFrota = $("acFrota");
  const frotaSelecionada = $("frotaSelecionada");
  const operador = $("operador");
  const horimetro = $("horimetro");
  const litros = $("litros");
  const msg = $("msgAbast");
  const btnSalvar = $("btnAbastSalvar");
  const btnLimpar = $("btnAbastLimpar");
  const tblBody = $("tblAbast")?.querySelector("tbody");

  function showMsg(type, text) {
    if (!msg) return;
    msg.style.display = "block";
    msg.classList.remove("clean-alert--ok", "clean-alert--warn", "clean-alert--err");
    msg.classList.add(type === "ok" ? "clean-alert--ok" : type === "warn" ? "clean-alert--warn" : "clean-alert--err");
    msg.textContent = text;
  }

  function clearMsg() {
    if (!msg) return;
    msg.style.display = "none";
    msg.textContent = "";
  }

  function setNow() {
    if (!dataHora) return;
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    const v = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    dataHora.value = v;
  }

  function clearFrotaSelection() {
    if (frotaId) frotaId.value = "";
    if (frotaSelecionada) frotaSelecionada.textContent = "";
  }

  function setFrotaSelection(it) {
    if (!it || it.id == null) return;
    frotaId.value = String(it.id);
    const label = `${it.placa} - ${it.modelo}`.trim();
    qFrota.value = label;
    frotaSelecionada.textContent = `Selecionado: ${label}`;
    if (acFrota) acFrota.innerHTML = "";
  }

  let acTimer = null;

  async function searchFrota(term) {
    const url = `${window.APP_BASE}/api/oficina_get_frota.php?q=${encodeURIComponent(term)}&limit=20`;
    const res = await fetch(url, { credentials: "same-origin" });
    const json = await res.json();
    if (!json || json.ok !== true) return [];
    return Array.isArray(json.items) ? json.items : [];
  }

  function renderAc(items) {
    if (!acFrota) return;
    acFrota.innerHTML = "";

    items.forEach((it) => {
      const opt = document.createElement("div");
      opt.className = "ac__item";
      opt.setAttribute("role", "option");
      opt.tabIndex = 0;
      opt.textContent = `${it.placa} - ${it.modelo}`;

      const select = () => setFrotaSelection(it);

      opt.addEventListener("click", select);
      opt.addEventListener("keydown", (e) => {
        if (e.key === "Enter") select();
      });

      acFrota.appendChild(opt);
    });
  }

  // Auto-seleção quando digitar uma placa exata e houver match
  async function tryAutoSelectExact(term) {
    const t = term.trim().toLowerCase();
    if (!t) return false;

    const items = await searchFrota(term);
    // tenta match exato por placa
    const exact = items.filter((x) => String(x.placa || "").trim().toLowerCase() === t);

    if (exact.length === 1) {
      setFrotaSelection(exact[0]);
      return true;
    }
    return false;
  }

  qFrota?.addEventListener("input", () => {
    clearFrotaSelection();
    const term = qFrota.value.trim();
    if (acTimer) clearTimeout(acTimer);

    if (term.length < 2) {
      if (acFrota) acFrota.innerHTML = "";
      return;
    }

    acTimer = setTimeout(async () => {
      try {
        const items = await searchFrota(term);

        // se digitou exatamente a placa e só tem 1 match, já seleciona
        const didSelect = await tryAutoSelectExact(term);
        if (didSelect) return;

        renderAc(items);
      } catch {
        if (acFrota) acFrota.innerHTML = "";
      }
    }, 180);
  });

  qFrota?.addEventListener("blur", async () => {
    // ao sair do campo, tenta selecionar automaticamente também
    if (frotaId.value) return;
    const term = qFrota.value.trim();
    if (!term) return;
    await tryAutoSelectExact(term);
  });

  document.addEventListener("click", (e) => {
    if (!acFrota) return;
    if (e.target === qFrota || acFrota.contains(e.target)) return;
    acFrota.innerHTML = "";
  });

  async function loadTable() {
    if (!tblBody) return;

    const res = await fetch(`${window.APP_BASE}/api/abastecimento_frota_list.php?limit=80`, {
      credentials: "same-origin",
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || "Falha ao listar");

    tblBody.innerHTML = "";
    (json.data || []).forEach((r) => {
      const tr = document.createElement("tr");
      const dt = new Date(String(r.data_hora || "").replace(" ", "T"));

      tr.innerHTML = `
        <td>${r.id}</td>
        <td>${isNaN(dt.getTime()) ? (r.data_hora || "") : dt.toLocaleString("pt-BR")}</td>
        <td>${r.frentista || ""}</td>
        <td>${(r.placa || "")} - ${(r.modelo || "")}</td>
        <td>${r.operador || ""}</td>
        <td>${r.horimetro ?? ""}</td>
        <td>${r.litros ?? ""}</td>
      `;
      tblBody.appendChild(tr);
    });
  }

  async function save() {
    clearMsg();

    // garante auto-select antes de validar
    if (!frotaId.value && qFrota.value.trim()) {
      await tryAutoSelectExact(qFrota.value.trim());
    }

    const payload = {
      frentista: frentista?.value || "",
      data_hora: dataHora?.value ? dataHora.value.replace("T", " ") + ":00" : "",
      frota_id: Number(frotaId?.value || 0),
      operador: (operador?.value || "").trim(),
      horimetro: horimetro?.value || "",
      litros: litros?.value || "",
    };

    if (!payload.frentista) return showMsg("err", "Selecione o frentista.");
    if (!payload.data_hora) return showMsg("err", "Informe a data.");
    if (!payload.frota_id) return showMsg("err", "Selecione o equipamento.");
    if (!payload.operador) return showMsg("err", "Informe o motorista/operador.");
    if (!payload.horimetro) return showMsg("err", "Informe o horímetro.");
    if (!payload.litros) return showMsg("err", "Informe os litros.");

    if (btnSalvar) btnSalvar.disabled = true;
    try {
      const res = await fetch(`${window.APP_BASE}/api/abastecimento_frota_create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || "Erro ao salvar");

      showMsg("ok", "Abastecimento salvo com sucesso.");

      if (operador) operador.value = "";
      if (horimetro) horimetro.value = "";
      if (litros) litros.value = "";
      clearFrotaSelection();
      if (qFrota) qFrota.value = "";
      setNow();
      await loadTable();
    } catch (e) {
      showMsg("err", e?.message || "Erro ao salvar");
    } finally {
      if (btnSalvar) btnSalvar.disabled = false;
    }
  }

  btnSalvar?.addEventListener("click", save);
  btnLimpar?.addEventListener("click", () => {
    clearMsg();
    if (frentista) frentista.value = "";
    setNow();
    if (qFrota) qFrota.value = "";
    clearFrotaSelection();
    if (operador) operador.value = "";
    if (horimetro) horimetro.value = "";
    if (litros) litros.value = "";
  });

  // init
  setNow();
  loadTable().catch(() => {});
})();