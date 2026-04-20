(() => {
  const BASE = window.APP_BASE || "";
  const inpDia = document.getElementById("inpDia");
  const btn = document.getElementById("btnAbrir");
  const msg = document.getElementById("msg");

  function todayISO(){
    const t = new Date();
    const y = t.getFullYear();
    const m = String(t.getMonth()+1).padStart(2,"0");
    const d = String(t.getDate()).padStart(2,"0");
    return `${y}-${m}-${d}`;
  }
  function show(text){
    msg.style.display="block";
    msg.className="clean-alert clean-alert--error";
    msg.textContent=text;
  }

  inpDia.value = todayISO();

  btn.addEventListener("click", () => {
    if (!inpDia.value) return show("Informe a data.");
    const url = `${BASE}/api/fin_relatorio_dia_print.php?data=${encodeURIComponent(inpDia.value)}`;
    window.open(url, "_blank", "noopener,noreferrer");
  });
})();