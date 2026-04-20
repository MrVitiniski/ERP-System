(function () {

  async function carregarDados() {
    const res = await fetch('/sistema/public/api/producao_listar.php');
    const dados = await res.json();

    if (!dados.ok) {
      alert("Erro ao carregar dados");
      return;
    }

    processar(dados.data);
  }

  function processar(lista) {

    let total = 0;
    let totalTC01 = 0;

    let turnos = { "Turno 1":0, "Turno 2":0, "Turno 3":0 };

    let tcs = [0,0,0,0,0];

    lista.forEach(r => {
      total += r.total;
      totalTC01 += r.tc01;

      if (turnos[r.turno] !== undefined) {
        turnos[r.turno] += r.total;
      }

      tcs[0]+=r.tc01;
      tcs[1]+=r.tc02;
      tcs[2]+=r.tc03;
      tcs[3]+=r.tc04;
      tcs[4]+=r.tc05;
    });

    // KPI
    document.getElementById("kpi_total").innerText = total.toFixed(2)+" t";
    document.getElementById("kpi_tc01").innerText = totalTC01.toFixed(2)+" t";

    let eficiencia = totalTC01 > 0 ? (total / totalTC01)*100 : 0;
    document.getElementById("kpi_eficiencia").innerText = eficiencia.toFixed(1)+"%";

    // Melhor turno
    let melhor = Object.entries(turnos).sort((a,b)=>b[1]-a[1])[0];
    document.getElementById("kpi_turno").innerText = melhor ? melhor[0] : "-";

    gerarGraficos(turnos, tcs);
    gerarRanking(lista);
  }

  function gerarGraficos(turnos, tcs) {

    new Chart(document.getElementById("grafTurnos"), {
      type: "bar",
      data: {
        labels: Object.keys(turnos),
        datasets: [{
          label: "Produção por Turno",
          data: Object.values(turnos)
        }]
      }
    });

    new Chart(document.getElementById("grafTCs"), {
      type: "bar",
      data: {
        labels: ["TC01","TC02","TC03","TC04","TC05"],
        datasets: [{
          label: "Produção por TC",
          data: tcs
        }]
      }
    });
  }

  function gerarRanking(lista) {

    const tbody = document.getElementById("rankingTabela");
    tbody.innerHTML = "";

    lista
      .sort((a,b)=>b.total-a.total)
      .forEach(r => {

        const tr = document.createElement("tr");

        tr.innerHTML = `
          <td>${r.data}</td>
          <td>${r.turno}</td>
          <td>${r.responsavel}</td>
          <td><b>${r.total.toFixed(2)} t</b></td>
        `;

        tbody.appendChild(tr);
      });
  }

  carregarDados();

})();