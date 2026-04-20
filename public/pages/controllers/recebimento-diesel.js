(function () {
  const $ = (id) => document.getElementById(id);

  const dataHora = $("dieselDataHora");
  const litros = $("dieselLitros");

  // padronizado: dieselFornecedor (mas aceito dieselEmpresa se existir)
  const fornecedorEl = $("dieselFornecedor") || $("dieselEmpresa");

  const nf = $("dieselNF");
  const motorista = $("dieselMotorista");
  const placa = $("dieselPlaca");
  const obs = $("dieselObs");

  const msg = $("msgDiesel");
  const btnSalvar = $("btnDieselSalvar");
  const btnLimpar = $("btnDieselLimpar");

  const tblBody = $("tblDiesel")?.querySelector("tbody");

  const saldoEntradas = $("saldoEntradas");
  const saldoSaidas = $("saldoSaidas");
  const saldoAtual = $("saldoAtual");

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

  // Converte string pt-BR para número:
  // "1.215,45" -> 1215.45
  // "861"      -> 861
  function parseLitrosPtBR(input) {
    const raw = String(input ?? "").trim();
    if (!raw) return NaN;

    // remove espaços
    let s = raw.replace(/\s+/g, "");

    // remove separadores de milhar (.)
    s = s.replace(/\./g, "");

    // troca decimal (,) por (.)
    s = s.replace(/,/g, ".");

    // mantém só dígitos e ponto e sinal (segurança)
    s = s.replace(/[^0-9.\-]/g, "");

    // evita "12.3.4" virar NaN silencioso
    const dotCount = (s.match(/\./g) || []).length;
    if (dotCount > 1) return NaN;

    const n = Number(s);
    return n;
  }

  function formatLitrosPtBR(n) {
    const num = Number(n || 0);
    return num.toLocaleString("pt-BR", { minimumFractionDigits: 3, maximumFractionDigits: 3 });
  }

  async function loadSaldo() {
    const res = await fetch(`${window.APP_BASE}/api/diesel_saldo.php`, { credentials: "same-origin" });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || "Falha ao calcular saldo");

    const ent = Number(json.data?.entradas || 0);
    const sai = Number(json.data?.saidas || 0);
    const sal = Number(json.data?.saldo || 0);

    if (saldoEntradas) saldoEntradas.value = formatLitrosPtBR(ent);
    if (saldoSaidas) saldoSaidas.value = formatLitrosPtBR(sai);
    if (saldoAtual) saldoAtual.value = formatLitrosPtBR(sal);

    if (json.data?.low) {
      showMsg(
        "warn",
        `Atenção: diesel baixo! Saldo atual ${formatLitrosPtBR(sal)} L (limite ${Number(json.data?.threshold || 2000).toFixed(0)} L).`
      );
    }
  }

  async function loadTable() {
    if (!tblBody) return;

    const res = await fetch(`${window.APP_BASE}/api/diesel_recebimento_list.php?limit=120`, { credentials: "same-origin" });
    const json = await res.json();
    if (!json.ok) throw new Error(json.debug || json.error || "Falha ao listar");

    tblBody.innerHTML = "";
    (json.data || []).forEach((r) => {
      const tr = document.createElement("tr");
      const dt = new Date(String(r.data_hora || "").replace(" ", "T"));

      tr.innerHTML = `
        <td>${r.id}</td>
        <td>${isNaN(dt.getTime()) ? (r.data_hora || "") : dt.toLocaleString("pt-BR")}</td>
        <td>${formatLitrosPtBR(r.litros)}</td>
        <td>${r.fornecedor ?? ""}</td>
        <td>${r.nf ?? ""}</td>
        <td>${r.motorista ?? ""}</td>
        <td>${r.placa_caminhao ?? ""}</td>
        <td>${r.obs ?? ""}</td>
        <td>${r.usuario ?? ""}</td>
      `;
      tblBody.appendChild(tr);
    });
  }

  function validateRequired(litrosNumber) {
    const fornecedor = (fornecedorEl?.value || "").trim();
    const nfVal = (nf?.value || "").trim();
    const motoristaVal = (motorista?.value || "").trim();
    const placaVal = (placa?.value || "").trim();
    const obsVal = (obs?.value || "").trim();

    if (!dataHora?.value) return "Informe a data.";
    if (!Number.isFinite(litrosNumber) || litrosNumber <= 0) return "Informe os litros recebidos (ex: 861 ou 1.215,45).";
    if (!fornecedor) return "Informe o fornecedor.";
    if (!nfVal) return "Informe a NF.";
    if (!motoristaVal) return "Informe o motorista.";
    if (!placaVal) return "Informe a placa do caminhão.";
    if (!obsVal) return "Informe a observação.";

    return "";
  }

  async function save() {
    clearMsg();

    const litrosNumber = parseLitrosPtBR(litros?.value);

    const err = validateRequired(litrosNumber);
    if (err) return showMsg("err", err);

    const payload = {
      data_hora: dataHora.value ? dataHora.value.replace("T", " ") + ":00" : "",
      litros: litrosNumber,
      fornecedor: (fornecedorEl.value || "").trim(),
      nf: (nf.value || "").trim(),
      motorista: (motorista.value || "").trim(),
      placa_caminhao: (placa.value || "").trim(),
      obs: (obs.value || "").trim(),
    };

    if (btnSalvar) btnSalvar.disabled = true;
    try {
      const res = await fetch(`${window.APP_BASE}/api/diesel_recebimento_create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.debug || json.error || "Erro ao salvar");

      showMsg("ok", "Recebimento registrado com sucesso.");

      // limpa tudo
      if (litros) litros.value = "";
      if (fornecedorEl) fornecedorEl.value = "";
      if (nf) nf.value = "";
      if (motorista) motorista.value = "";
      if (placa) placa.value = "";
      if (obs) obs.value = "";

      setNow();
      await loadTable();
      await loadSaldo();
    } catch (e) {
      showMsg("err", e?.message || "Erro ao salvar");
    } finally {
      if (btnSalvar) btnSalvar.disabled = false;
    }
  }

  btnSalvar?.addEventListener("click", save);
  btnLimpar?.addEventListener("click", () => {
    clearMsg();
    setNow();
    if (litros) litros.value = "";
    if (fornecedorEl) fornecedorEl.value = "";
    if (nf) nf.value = "";
    if (motorista) motorista.value = "";
    if (placa) placa.value = "";
    if (obs) obs.value = "";
  });

  // init
  setNow();
  loadTable().catch((e) => showMsg("err", e?.message || "Erro ao carregar histórico"));
  loadSaldo().catch(() => {});
})();