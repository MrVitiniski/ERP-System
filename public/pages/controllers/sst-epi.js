// public/pages/controllers/sst-epi.js
(() => {
  const BASE = window.APP_BASE || "/sistema/public";

const f = document.getElementById("f") || document.querySelector("form");
const msg = document.getElementById("msg") || f?.querySelector("#msg,[data-msg]") || null;
const btn = document.getElementById("btnGerar") || f?.querySelector('button[type="submit"]') || null;

if (!f || !msg || !btn) {
  console.warn("[sst-epi] elementos não encontrados", { f, msg, btn });
  return;
}

  function setMsg(text) {
    msg.textContent = text || "";
  }

  async function getJson(url) {
    const res = await fetch(url, { credentials: "same-origin", cache: "no-store" });
    const text = await res.text();

    let data = {};
    try { data = JSON.parse(text); } catch {}

    if (!res.ok) {
      // se o backend devolveu HTML/login/forbidden, o text ajuda a achar
      console.error("[sst-epi] HTTP", res.status, "em", url, "resposta:", text);
      throw new Error(data.error || data.message || `HTTP ${res.status}`);
    }
    if (data.ok !== true) {
      console.error("[sst-epi] API ok!=true em", url, "resposta:", data);
      throw new Error(data.error || data.message || "Erro");
    }
    return data;
  }

  // ===== Preencher lista de funcionários =====
  async function carregarFuncionarios() {
    // tenta achar um SELECT (funcionario_id ou colaborador)
    const select =
      f.querySelector('select[name="funcionario_id"]') ||
      document.getElementById("funcionario_id") ||
      f.querySelector('select[name="colaborador"]') ||
      document.getElementById("colaborador");

    // tenta achar um INPUT com datalist
    const inputComList = f.querySelector('input[name="colaborador"][list]') || null;
    const datalistId = inputComList?.getAttribute("list");
    const datalist = datalistId ? document.getElementById(datalistId) : null;

    if (!select && !datalist) {
      console.warn("[sst-epi] Nenhum <select> ou <datalist> para funcionários encontrado.");
      return;
    }

    try {
      if (select) {
        select.innerHTML = `<option value="">Carregando...</option>`;
        select.disabled = true;
      }
      if (datalist) {
        datalist.innerHTML = "";
      }

      const out = await getJson(`${BASE}/api/funcionarios_list_sst.php`);
      const items = Array.isArray(out.items) ? out.items : [];

      if (select) {
        select.innerHTML =
          `<option value="">Selecione...</option>` +
          items
            .map((f) => {
              const nome = String(f.nome_completo || "").trim();
              // valor: se for funcionario_id, normalmente é ID; se for colaborador pode ser nome.
              // Vamos preferir ID se existir name="funcionario_id"; senão usar nome.
              const isIdField = (select.name === "funcionario_id" || select.id === "funcionario_id");
              const value = isIdField ? String(f.id) : nome;
              const label = `${nome} (ID: ${f.id})`;
              return `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
            })
            .join("");
        select.disabled = false;
      }

      if (datalist) {
        datalist.innerHTML = items
          .map((f) => {
            const nome = String(f.nome_completo || "").trim();
            return `<option value="${escapeHtml(nome)}"></option>`;
          })
          .join("");
      }
    } catch (e) {
      const err = e?.message || "Erro ao carregar funcionários";
      console.error("[sst-epi]", err);
      if (select) {
        select.innerHTML = `<option value="">${escapeHtml(err)}</option>`;
        select.disabled = false;
      }
      // Não bloqueia a tela; só informa
      setMsg(err);
    }
  }

  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  // data hoje
  const dateInput = f.querySelector('input[name="data"]');
  const setToday = () => {
    if (!dateInput) return;
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    dateInput.value = `${yyyy}-${mm}-${dd}`;
  };
  if (dateInput && !dateInput.value) setToday();

  // carrega funcionários ao abrir a página
  carregarFuncionarios();

  f.addEventListener("submit", async (ev) => {
    ev.preventDefault();
    setMsg("");

    if (!f.reportValidity()) return;

    btn.disabled = true;
    btn.style.opacity = "0.85";

    try {
      const payload = Object.fromEntries(new FormData(f).entries());

      const res = await fetch(`${BASE}/api/epi_pedidos_create.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json; charset=utf-8" },
        credentials: "same-origin",
        body: JSON.stringify(payload),
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) throw new Error(data.error || data.message || `HTTP ${res.status}`);
      if (data.ok !== true) throw new Error(data.error || data.message || "Falha ao gerar o pedido.");

      setMsg(`Pedido gerado e enviado ao Almoxarifado. Nº ${data.pedido_id}`);
      f.reset();
      setToday();

      // foca no primeiro campo
      (f.querySelector('input[name="colaborador"]') ||
        f.querySelector('select[name="funcionario_id"]') ||
        f.querySelector("input, select, textarea"))?.focus();

      // recarrega a lista (opcional)
      // await carregarFuncionarios();
    } catch (e) {
      setMsg(e?.message || "Erro ao gerar pedido.");
    } finally {
      btn.disabled = false;
      btn.style.opacity = "";
    }
  });
})();