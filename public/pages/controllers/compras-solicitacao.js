(() => {
  const BASE = "/sistema/public";

  const frm = document.getElementById("frmCompra");
  if (!frm) return;

  const msg = document.getElementById("msg");
  const btnImprimir = document.getElementById("btnImprimir");
  const inputId = document.getElementById("solicitacao_id");
  const numeroInterno = document.getElementById("numero_interno"); 
  function show(text, type = "") {
    if (!msg) { alert(text); return; }
    msg.style.display = "block";
    msg.textContent = text;
    msg.className = "clean-alert" + (type ? ` clean-alert--${type}` : "");
  }

  frm.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    show("Salvando...");

    const payload = Object.fromEntries(new FormData(frm).entries());
    console.log("payload =", payload);
    console.log("data_pedido =", payload.data_pedido);

    try {
      const res = await fetch(`${BASE}/api/compras_solicitacao_salvar.php`, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      const out = await res.json().catch(() => ({}));

      if (!res.ok || !out.ok) {
        show("Erro: " + (out.error || "falha ao salvar"), "error");
        return;
      }

      inputId.value = out.id;
      btnImprimir.disabled = false;
      if (numeroInterno) numeroInterno.value = out.numero_interno || "";
btnImprimir.disabled = false;
            // 1. Mostra a mensagem de sucesso
      show(`Salvo com sucesso! Número: ${out.numero_interno}`, "ok");

      // 2. Preenche o ID e o Número que voltaram do banco (caso queira imprimir na hora)
      if (inputId) inputId.value = out.id;
      if (numeroInterno) numeroInterno.value = out.numero_interno || "";
      if (btnImprimir) btnImprimir.disabled = false;

      
      setTimeout(() => {
        
        window.location.hash = "/compras-solicitacoes"; 
      }, 2000);

    } catch (e) {
      show("Erro de rede ao salvar.", "error");
    }

  });

  btnImprimir?.addEventListener("click", () => {
    const id = inputId.value;
    if (!id) return;
    window.open(`${BASE}/api/compras_solicitacao_print.php?id=${encodeURIComponent(id)}`, "_blank");
  });
})();