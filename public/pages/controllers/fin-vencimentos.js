(() => {
  const BASE = window.APP_BASE || "";

  queueMicrotask(() => {
    console.log("fin-vencimentos.js carregou ✅");

    const inpFornecedor = document.getElementById("inpFornecedor");
    const inpDe = document.getElementById("inpDe");
    const inpAte = document.getElementById("inpAte");
    const btnFiltrar = document.getElementById("btnFiltrar");
    const btnLimpar = document.getElementById("btnLimpar");
    const btnRecarregar = document.getElementById("btnRecarregar");

    const tblBody = document.querySelector("#tblVencimentos tbody");
    const msg = document.getElementById("msg");
    const resumo = document.getElementById("resumo");

    const required = { inpFornecedor, inpDe, inpAte, btnFiltrar, btnLimpar, btnRecarregar, tblBody, msg, resumo };
    const missing = Object.entries(required).filter(([, el]) => !el).map(([k]) => k);
    if (missing.length) {
      console.error("fin-vencimentos: elementos não encontrados:", missing);
      return;
    }

    function todayISO(){
      const t = new Date();
      const y = t.getFullYear();
      const m = String(t.getMonth()+1).padStart(2,"0");
      const d = String(t.getDate()).padStart(2,"0");
      return `${y}-${m}-${d}`;
    }

    function addDaysISO(iso, days){
      const dt = new Date(iso + "T00:00:00");
      dt.setDate(dt.getDate() + days);
      const y = dt.getFullYear();
      const m = String(dt.getMonth()+1).padStart(2,"0");
      const d = String(dt.getDate()).padStart(2,"0");
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

    async function fetchJson(url){
      console.log("[FETCH]", url);
      const res = await fetch(url, { credentials: "same-origin" });
      const text = await res.text();

      let out;
      try { out = text ? JSON.parse(text) : null; }
      catch {
        throw new Error(`Resposta não é JSON (HTTP ${res.status}). Início: ${text.slice(0, 180)}`);
      }

      if (!res.ok || (out && out.ok === false)) throw new Error(out?.error || `Erro HTTP ${res.status}`);
      return out;
    }

    async function carregar(){
      const hoje = todayISO();

      const q = (inpFornecedor.value || "").trim();
      const de = inpDe.value || "";   // opcional
      const ate = inpAte.value || ""; // opcional

      const url =
        `${BASE}/api/fin_lancamentos_list.php?tipo=pagar&status=aberto&limit=500` +
        (q ? `&q=${encodeURIComponent(q)}` : "") +
        (de ? `&de=${encodeURIComponent(de)}` : "") +
        (ate ? `&ate=${encodeURIComponent(ate)}` : "");

      const out = await fetchJson(url);
      const items = out.items || [];

      // garante ordenação por vencimento
      items.sort((a, b) => String(a.data_prevista).localeCompare(String(b.data_prevista)));

      let total = 0, vencidos = 0, venceHoje = 0, aVencer = 0;

      tblBody.innerHTML = items.map(it => {
        const dp = String(it.data_prevista || "");
        const v = Number(it.valor) || 0;
        total += v;

        const isVencido = dp && dp < hoje;
        const isHoje = dp && dp === hoje;

        if (isVencido) vencidos++;
        else if (isHoje) venceHoje++;
        else aVencer++;

        const parc = (it.parcela_num && it.parcela_total) ? `${it.parcela_num}/${it.parcela_total}` : "—";
        const statusLabel = isVencido ? "Vencido" : (isHoje ? "Vence hoje" : "A vencer");

        return `
          <tr class="${isVencido ? "fin-vencido-bg" : (isHoje ? "fin-hoje-bg" : "")}">
            <td class="${isVencido ? "fin-vencido" : ""}">${dp}</td>
            <td>${it.pessoa}</td>
            <td>${it.descricao ?? ""}</td>
            <td class="right">${money(v)}</td>
            <td>${parc}</td>
            <td>${statusLabel}</td>
          </tr>
        `;
      }).join("") || `<tr><td colspan="6" class="clean-help">Sem contas em aberto.</td></tr>`;

      resumo.textContent = `Itens: ${items.length} | Vencidos: ${vencidos} | Hoje: ${venceHoje} | A vencer: ${aVencer} | Total: ${money(total)}`;
      show("Lista atualizada.", "ok");
    }

    // Eventos
    btnFiltrar.addEventListener("click", () => carregar().catch(e => show(e.message, "error")));
    btnRecarregar.addEventListener("click", () => carregar().catch(e => show(e.message, "error")));

    inpFornecedor.addEventListener("keydown", (e) => {
      if (e.key === "Enter") carregar().catch(err => show(err.message, "error"));
    });

    btnLimpar.addEventListener("click", () => {
      inpFornecedor.value = "";
      inpDe.value = "";
      inpAte.value = "";
      carregar().catch(err => show(err.message, "error"));
    });

    // Init (padrão: próximos 30 dias)
    const h = todayISO();
    inpDe.value = h;
    inpAte.value = addDaysISO(h, 30);

    carregar().catch(err => show(err.message, "error"));
  });
})();