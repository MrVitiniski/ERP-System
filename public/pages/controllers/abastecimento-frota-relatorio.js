(function () {
  const $ = (id) => document.getElementById(id);

  const relDe = $("relDe");
  const relAte = $("relAte");
  const btnRelGerar = $("btnRelGerar");
  const msg = $("msgRel");

  const kpiLitros = $("kpiLitros");
  const kpiQtd = $("kpiQtd");
  const kpiMedia = $("kpiMedia");

  const tblBody = $("tblRel")?.querySelector("tbody");
  const ctx = $("chartSemana")?.getContext("2d");

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

  function todayISO() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  function daysAgoISO(n) {
    const d = new Date();
    d.setDate(d.getDate() - n);
    const pad = (x) => String(x).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  // defaults: últimos 7 dias
  if (relAte) relAte.value = todayISO();
  if (relDe) relDe.value = daysAgoISO(6);

  let chart = null;

  async function fetchJson(url) {
    const res = await fetch(url, { credentials: "same-origin" });
    const json = await res.json();
    if (!json?.ok) throw new Error(json?.error || "Falha");
    return json;
  }

  function renderTable(rows) {
    if (!tblBody) return;
    tblBody.innerHTML = "";

    (rows || []).forEach((r) => {
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

  function renderKPIs(summary) {
    const litros = Number(summary?.total_litros || 0);
    const qtd = Number(summary?.qtd || 0);
    const media = qtd > 0 ? litros / qtd : 0;

    if (kpiLitros) kpiLitros.value = litros.toFixed(3);
    if (kpiQtd) kpiQtd.value = String(qtd);
    if (kpiMedia) kpiMedia.value = media.toFixed(3);
  }

  // payload esperado:
  // { days: ["2026-04-02",...], series: [{frota_id, label, data:[..7]}] }
  function renderChart(payload) {
    if (!ctx || !window.Chart) return;

    const labels = payload?.days || [];
    const series = payload?.series || [];

    const palette = [
      "rgba(59, 130, 246, 1)",   // azul
      "rgba(16, 185, 129, 1)",   // verde
      "rgba(245, 158, 11, 1)",   // laranja
      "rgba(239, 68, 68, 1)",    // vermelho
      "rgba(168, 85, 247, 1)",   // roxo
      "rgba(20, 184, 166, 1)",   // teal
      "rgba(100, 116, 139, 1)",  // slate
    ];

    const datasets = series.map((s, idx) => ({
      label: s.label,
      data: Array.isArray(s.data) ? s.data.map((n) => Number(n || 0)) : [],
      borderColor: palette[idx % palette.length],
      backgroundColor: palette[idx % palette.length],
      tension: 0.25,
      fill: false,
      pointRadius: 3,
      pointHoverRadius: 5,
    }));

    if (chart) chart.destroy();

    chart = new window.Chart(ctx, {
      type: "line",
      data: { labels, datasets },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true },
          tooltip: { enabled: true },
        },
        scales: {
          y: { beginAtZero: true },
        },
      },
    });
  }

  async function gerar() {
    clearMsg();

    const de = relDe?.value || "";
    const ate = relAte?.value || "";
    if (!de || !ate) return showMsg("err", "Selecione o período (De/Até).");

    try {
      // 1) detalhado do período
      const det = await fetchJson(
        `${window.APP_BASE}/api/abastecimento_frota_report.php?de=${encodeURIComponent(de)}&ate=${encodeURIComponent(ate)}`
      );
      renderTable(det.data);

      // 2) resumo do período
      const sum = await fetchJson(
        `${window.APP_BASE}/api/abastecimento_frota_report_summary.php?de=${encodeURIComponent(de)}&ate=${encodeURIComponent(ate)}`
      );
      renderKPIs(sum.data);

      // 3) gráfico semanal: litros por dia por equipamento (top 5)
      const week = await fetchJson(
        `${window.APP_BASE}/api/abastecimento_frota_report_week_by_equip.php?top=5`
      );
      renderChart(week.data);
    } catch (e) {
      showMsg("err", e?.message || "Erro ao gerar relatório");
    }
  }

  btnRelGerar?.addEventListener("click", gerar);

  // auto
  gerar().catch(() => {});
})();