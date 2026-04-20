(() => {
  const BASE = window.APP_BASE || "";

  queueMicrotask(() => {
    console.log("fin-caixa-dia.js carregou ✅");

    // Conta fixa (agora só SCAVARE)
    const CONTA_FIXA = "SCAVARE MINERAÇÃO";

    const inpDia = document.getElementById("inpDiaCaixa");
    const inpSaldo = document.getElementById("inpSaldoBanco");
    const btnSalvar = document.getElementById("btnSalvarSaldo");
    const lblInfo = document.getElementById("lblSaldoInfo");
    const msg = document.getElementById("msgSaldo");

    const missing = [
      ["inpDiaCaixa", inpDia],
      ["inpSaldoBanco", inpSaldo],
      ["btnSalvarSaldo", btnSalvar],
      ["lblSaldoInfo", lblInfo],
      ["msgSaldo", msg],
    ].filter(([, el]) => !el).map(([k]) => k);

    if (missing.length) {
      console.warn("Caixa do Dia: faltando no HTML:", missing);
      return;
    }

    function todayISO(){
      const t = new Date();
      const y = t.getFullYear();
      const m = String(t.getMonth()+1).padStart(2,"0");
      const d = String(t.getDate()).padStart(2,"0");
      return `${y}-${m}-${d}`;
    }

    function parseBRL(str){
  // aceita: "1350,36" | "1.350,36" | "1350.36" | "1,350.36"
  let s = String(str ?? "").trim();
  if (!s) return 0;

  // remove espaços e símbolo
  s = s.replace(/\s+/g, "").replace(/^R\$\s*/i, "");

  // se tem vírgula, assume vírgula como decimal e remove pontos de milhar
  if (s.includes(",")) {
    s = s.replace(/\./g, "").replace(",", ".");
    return Number(s) || 0;
  }

  // se não tem vírgula, tenta número normal (pode ter ponto decimal)
  s = s.replace(/,/g, "");
  return Number(s) || 0;
}

function fmtBRL(v){
  return new Intl.NumberFormat("pt-BR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(v || 0));
}

    function show(text, type){
      msg.style.display = "block";
      msg.className = "clean-alert " + (type === "ok" ? "clean-alert--ok" : "clean-alert--error");
      msg.textContent = text;
      console.log("[MSG]", type, text);
    }

    async function fetchJson(url, options){
      const res = await fetch(url, options);
      const text = await res.text();

      let out;
      try { out = text ? JSON.parse(text) : null; }
      catch { throw new Error(`Resposta não é JSON. Início: ${text.slice(0,120)}`); }

      if (!res.ok || out?.ok === false) throw new Error(out?.error || `Erro HTTP ${res.status}`);
      return out;
    }

    async function carregarHistorico(){
      const out = await fetchJson(
        `${BASE}/api/fin_caixa_saldo_dia_list.php?limit=60`,
        { credentials: "same-origin" }
      );

      const tb = document.getElementById("tbHistoricoSaldo");
      if (!tb) return;

      const items = out.items || [];
      if (!items.length) {
        tb.innerHTML = `<tr><td colspan="4" style="padding:8px;">Nenhum saldo informado ainda.</td></tr>`;
        return;
      }

      tb.innerHTML = items.map(it => `
        <tr>
          <td style="padding:8px; border-bottom:1px solid #2a2f3a;">${it.dia ?? ""}</td>
          <td style="padding:8px; border-bottom:1px solid #2a2f3a; text-align:right;">R$ ${fmtBRL(it.saldo_bancario)}</td>
          <td style="padding:8px; border-bottom:1px solid #2a2f3a;">${it.informado_por ?? ""}</td>
          <td style="padding:8px; border-bottom:1px solid #2a2f3a;">${it.informado_em ?? ""}</td>
        </tr>
      `).join("");
    }

    async function carregarSaldo(){
      const dia = inpDia.value || todayISO();

      const out = await fetchJson(
        `${BASE}/api/fin_caixa_saldo_dia_get.php?dia=${encodeURIComponent(dia)}&conta=${encodeURIComponent(CONTA_FIXA)}`,
        { credentials: "same-origin" }
      );

      const item = out.item;
      if (item) {
        inpSaldo.value = item.saldo_bancario;
        lblInfo.textContent = `Informado por ${item.informado_por ?? "—"} em ${item.informado_em ?? "—"}`;
      } else {
        inpSaldo.value = "";
        lblInfo.textContent = "Ainda não informado para este dia.";
      }
    }

    async function salvarSaldo(){
      const dia = inpDia.value || todayISO();
      const saldo = parseBRL(inpSaldo.value || "0");

      await fetchJson(`${BASE}/api/fin_caixa_saldo_dia_save.php`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ dia, conta: CONTA_FIXA, saldo_bancario: saldo })
      });

      show("Saldo bancário salvo.", "ok");
      await carregarSaldo();
      await carregarHistorico();
    }

    // init
    if (!inpDia.value) inpDia.value = todayISO();
    carregarSaldo().catch(e => show(e.message, "error"));
    carregarHistorico().catch(e => show(e.message, "error"));

    // events
    inpDia.addEventListener("change", () => carregarSaldo().catch(e => show(e.message, "error")));
    btnSalvar.addEventListener("click", () => salvarSaldo().catch(e => show(e.message, "error")));
  });
})();