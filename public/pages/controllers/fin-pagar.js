(() => {
  const BASE = window.APP_BASE || "";

  queueMicrotask(() => {
    console.log("FIN-PAGAR ATIVO (build 2026-03-03)");

    // ===== Elementos =====
    const inpPessoa = document.getElementById("inpPessoa");
    const inpDesc = document.getElementById("inpDesc");
    const inpDataBase = document.getElementById("inpDataBase");
    const inpValorBase = document.getElementById("inpValorBase");

    const selParc = document.getElementById("selParc");
    const wrapDias = document.getElementById("wrapDias");
    const wrapDatas = document.getElementById("wrapDatas");
    const inpDias = document.getElementById("inpDias");
    const inpDatas = document.getElementById("inpDatas");

    const btnGerar = document.getElementById("btnGerar");
    const btnSalvar = document.getElementById("btnSalvarParcelas");
    const btnAddParcela = document.getElementById("btnAddParcela");
    const btnRecarregar = document.getElementById("btnRecarregar");

    const tblParcelasBody = document.querySelector("#tblParcelas tbody");
    const tblAbertosBody = document.querySelector("#tblAbertos tbody");
    const msg = document.getElementById("msg");

    const required = {
      inpPessoa, inpDesc, inpDataBase, inpValorBase,
      selParc, wrapDias, wrapDatas, inpDias, inpDatas,
      btnGerar, btnSalvar, btnAddParcela, btnRecarregar,
      tblParcelasBody, tblAbertosBody, msg,
    };
    const missing = Object.entries(required).filter(([, el]) => !el).map(([k]) => k);
    if (missing.length) {
      console.error("fin-pagar.js: elementos não encontrados no HTML:", missing);
      return;
    }

    // ===== Helpers =====
    function todayISO(){
      const t = new Date();
      const y = t.getFullYear();
      const m = String(t.getMonth()+1).padStart(2,"0");
      const d = String(t.getDate()).padStart(2,"0");
      return `${y}-${m}-${d}`;
    }

    const money = (v) =>
      (Number(v) || 0).toLocaleString("pt-BR", { style:"currency", currency:"BRL" });

    function show(text, type){
      msg.style.display = "block";
      msg.className = "clean-alert " + (type === "ok" ? "clean-alert--ok" : "clean-alert--error");
      msg.textContent = text;
      console.log("[MSG]", type, text);
    }

    async function fetchJson(url, options){
      console.log("[FETCH]", url);
      const res = await fetch(url, options);
      const text = await res.text();

      let out;
      try { out = text ? JSON.parse(text) : null; }
      catch {
        throw new Error(`Resposta não é JSON (HTTP ${res.status}). Início: ${text.slice(0, 180)}`);
      }

      if (!res.ok || (out && out.ok === false)) {
        throw new Error(out?.error || `Erro HTTP ${res.status}`);
      }
      return out;
    }

    // ===== UI Parcelamento =====
    function uiParcelamento(){
      wrapDias.style.display = (selParc.value === "custom") ? "" : "none";
      wrapDatas.style.display = (selParc.value === "datas") ? "" : "none";
    }

    // ===== Parcelas =====
    function reindex(){
      [...tblParcelasBody.querySelectorAll("tr")].forEach((tr, i) => {
        const idx = tr.querySelector(".rowidx");
        if (idx) idx.textContent = String(i+1);
      });
    }

    function addParcelaRow(dataPrev = "", valor = ""){
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td class="rowidx"></td>
        <td><input class="clean-input p-data" type="date" value="${dataPrev}"></td>
        <td class="right"><input class="clean-input p-valor" type="number" step="0.01" min="0" value="${valor}"></td>
        <td><button class="clean-btn-icon clean-btn-icon--danger" type="button" data-del="1">Remover</button></td>
      `;
      tblParcelasBody.appendChild(tr);
      reindex();
    }

    tblParcelasBody.addEventListener("click", (e) => {
      const b = e.target.closest("[data-del='1']");
      if (!b) return;
      b.closest("tr")?.remove();
      reindex();
    });

    function parseDias(raw){
      const arr = raw.split(",").map(s=>parseInt(s.trim(),10)).filter(n=>Number.isFinite(n));
      if (!arr.length) throw new Error("Dias inválidos.");
      return arr;
    }

    function parseDatas(raw){
      const parts = raw.split(",").map(s=>s.trim()).filter(Boolean);
      if (!parts.length) throw new Error("Datas inválidas.");
      for (const d of parts){
        if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) throw new Error("Datas devem estar em YYYY-MM-DD.");
      }
      return parts;
    }

    function gerarParcelas(){
      tblParcelasBody.innerHTML = "";

      const base = inpDataBase.value || todayISO();
      const vbase = parseFloat(inpValorBase.value || "0");

      if (vbase <= 0) throw new Error("Informe o valor base.");
      if (!/^\d{4}-\d{2}-\d{2}$/.test(base)) throw new Error("Informe o 1º vencimento (base).");

      const addOffsetDays = (d) => {
        const dt = new Date(base + "T00:00:00");
        dt.setDate(dt.getDate() + d);
        const y = dt.getFullYear();
        const m = String(dt.getMonth()+1).padStart(2,"0");
        const dd = String(dt.getDate()).padStart(2,"0");
        addParcelaRow(`${y}-${m}-${dd}`, vbase.toFixed(2));
      };

      if (selParc.value === "avista") { addParcelaRow(base, vbase.toFixed(2)); return; }
      if (selParc.value === "30-60-90-120") { [30,60,90,120].forEach(addOffsetDays); return; }
      if (selParc.value === "30-60-90") { [30,60,90].forEach(addOffsetDays); return; }
      if (selParc.value === "custom") { parseDias((inpDias.value || "").trim()).forEach(addOffsetDays); return; }
      if (selParc.value === "datas") { parseDatas((inpDatas.value || "").trim()).forEach((d)=>addParcelaRow(d, vbase.toFixed(2))); return; }

      throw new Error("Selecione um tipo de parcelamento.");
    }

    function coletarParcelas(){
      const rows = [...tblParcelasBody.querySelectorAll("tr")];
      if (!rows.length) throw new Error("Gere ou adicione ao menos 1 parcela.");

      const parcelas = rows.map(tr => {
        const d = tr.querySelector(".p-data")?.value || "";
        const v = parseFloat(tr.querySelector(".p-valor")?.value || "0");
        return { data_prevista: d, valor: v };
      });

      const bad = parcelas.find(p => !/^\d{4}-\d{2}-\d{2}$/.test(p.data_prevista) || !(p.valor > 0));
      if (bad) throw new Error("Preencha vencimento e valor em todas as parcelas.");

      return parcelas;
    }

    async function salvarParcelas(){
      const payload = {
        tipo: "pagar",
        pessoa: inpPessoa.value.trim(),
        descricao: inpDesc.value.trim(),
        parcelas: coletarParcelas()
      };
      if (!payload.pessoa) throw new Error("Informe o fornecedor/para quem pagar.");

      const out = await fetchJson(`${BASE}/api/fin_lancamentos_save_parcelas.php`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      show(`Salvo! ${out.ids.length} parcela(s) cadastrada(s) em aberto.`, "ok");

      tblParcelasBody.innerHTML = "";
      inpDesc.value = "";
      inpValorBase.value = "";
      inpDataBase.value = todayISO();

      await carregarAbertos();
    }

    // ===== Em aberto =====
    async function carregarAbertos(){
      const out = await fetchJson(`${BASE}/api/fin_lancamentos_list.php?tipo=pagar&status=aberto&limit=300`, {
        credentials: "same-origin"
      });

      const hoje = todayISO();

      tblAbertosBody.innerHTML = (out.items || []).map(it => {
        const vencido = it.data_prevista < hoje;
        const parc = (it.parcela_num && it.parcela_total) ? `${it.parcela_num}/${it.parcela_total}` : "—";

        return `
          <tr class="${vencido ? "fin-vencido-bg" : ""}">
            <td class="${vencido ? "fin-vencido" : ""}">${it.data_prevista}</td>
            <td>${it.pessoa}</td>
            <td>${it.descricao ?? ""}</td>
            <td class="right">${money(it.valor)}</td>
            <td>${parc}</td>
            <td>
              <button class="clean-btn clean-btn--sm" type="button" data-quitar="${it.id}">Quitar</button>
            </td>
          </tr>
        `;
      }).join("") || `<tr><td colspan="6" class="clean-help">Sem contas em aberto.</td></tr>`;
    }

   async function quitarLancamento(id){
  // Se quiser SEM prompt: comente as 2 linhas abaixo e envie só {id}
  const data = prompt("Data da quitação (YYYY-MM-DD):", todayISO());
  if (!data) return;

  await fetchJson(`${BASE}/api/fin_lancamentos_quitar.php`, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, data_quitacao: data })
  });

  show("Quitado com sucesso.", "ok");
  await carregarAbertos();
}

    // ===== Events =====
    selParc.addEventListener("change", uiParcelamento);

    btnGerar.addEventListener("click", () => {
      try { gerarParcelas(); show("Parcelas geradas. Você pode editar datas/valores.", "ok"); }
      catch(e){ console.error(e); show(e.message, "error"); }
    });

    btnAddParcela.addEventListener("click", () => addParcelaRow(todayISO(), (inpValorBase.value || "")));

    btnSalvar.addEventListener("click", (e) => {
      e.preventDefault();
      salvarParcelas().catch(err => {
        console.error(err);
        show(err?.message || "Erro ao salvar.", "error");
      });
    });

    btnRecarregar.addEventListener("click", () => carregarAbertos().catch(console.error));

    // Delegação para o botão "Quitar" que é gerado dinamicamente
    tblAbertosBody.addEventListener("click", (e) => {
      const b = e.target.closest("[data-quitar]");
      if (!b) return;

      const id = parseInt(b.getAttribute("data-quitar") || "0", 10);
      if (!Number.isFinite(id) || id <= 0) return;

      quitarLancamento(id)
        .then(() => (show("Quitado com sucesso.", "ok"), carregarAbertos()))
        .catch((err) => (console.error(err), show(err?.message || "Erro ao quitar.", "error")));
    });

    // ===== Init =====
    if (!inpDataBase.value) inpDataBase.value = todayISO();
    uiParcelamento();
    carregarAbertos().catch(console.error);
  });
})();