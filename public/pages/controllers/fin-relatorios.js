(() => {
  const de = document.getElementById("relDe");
  const ate = document.getElementById("relAte");
  const btn = document.getElementById("btnAbrirRelatorio");
  const msg = document.getElementById("relMsg");

  if (!de || !ate || !btn) return;

  function show(text, type) {
    msg.style.display = "block";
    msg.className =
      "clean-alert " +
      (type === "ok" ? "clean-alert--ok" : type === "error" ? "clean-alert--error" : "");
    msg.textContent = text;
  }

  function todayISO() {
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth() + 1).padStart(2, "0");
    const d = String(t.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }

  // default: mês atual
  const now = new Date();
  const first = new Date(now.getFullYear(), now.getMonth(), 1);
  const y = first.getFullYear();
  const m = String(first.getMonth() + 1).padStart(2, "0");
  const d = String(first.getDate()).padStart(2, "0");
  de.value = `${y}-${m}-${d}`;
  ate.value = todayISO();

  btn.addEventListener("click", () => {
    if (!de.value || !ate.value) return show("Informe o período (De/Até).", "error");
    const url = `${window.APP_BASE}/api/fin_relatorio_print.php?de=${encodeURIComponent(de.value)}&ate=${encodeURIComponent(ate.value)}`;
    window.open(url, "_blank", "noopener,noreferrer");
  });
})();
// Placeholder para evitar 404. Substituir pela implementação do relatório.
console.log("fin-relatorio.js carregado(placeholder).");