(() => {
  const BASE = window.APP_BASE || "/sistema/public";

  const frm = document.getElementById("frmFuncionario");
  const msg = document.getElementById("msg");
  if (!frm || !msg) return;

  function showMsg(text, type) {
    msg.style.display = "block";
    msg.className =
      "clean-alert " +
      (type === "ok" ? "clean-alert--ok" : type === "error" ? "clean-alert--error" : "");
    msg.textContent = text;
  }

  // converte "dd/mm/aaaa" -> "aaaa-mm-dd" (se já vier ISO, mantém)
  function brDateToIso(v) {
    const s = String(v ?? "").trim();
    if (!s) return "";
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
    const m = s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return s; // deixa como está (o backend vai validar)
    return `${m[3]}-${m[2]}-${m[1]}`;
  }

  frm.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    showMsg("Salvando...", "");

    const fd = new FormData(frm);
    const data = Object.fromEntries(fd.entries());

    // checkboxes
    data.emprestimo_folha = fd.get("emprestimo_folha") ? 1 : 0;
    data.pensao_folha = fd.get("pensao_folha") ? 1 : 0;

    // datas (evita erro no MySQL se o input estiver em dd/mm/aaaa)
    if ("data_admissao" in data) data.data_admissao = brDateToIso(data.data_admissao);
    if ("data_nascimento" in data) data.data_nascimento = brDateToIso(data.data_nascimento);

    try {
      const res = await fetch(`${BASE}/api/rh_create.php`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json; charset=utf-8" },
        body: JSON.stringify(data),
      });

      // lê como texto primeiro (pra não "engolir" HTML/warnings que quebrem JSON)
      const text = await res.text();

      let out = {};
      try {
        out = JSON.parse(text);
      } catch {
        out = {};
      }

      if (!res.ok || out.ok !== true) {
        console.error("[rh_create] status:", res.status, "response:", text);
        showMsg("Erro: " + (out.error || out.message || text || `HTTP ${res.status}`), "error");
        return;
      }

      showMsg(`Salvo com sucesso! ID: ${out.id}`, "ok");
      frm.reset();
    } catch (e) {
      console.error("[rh_create] network/error:", e);
      showMsg("Erro de rede ao salvar.", "error");
    }
  });
})();