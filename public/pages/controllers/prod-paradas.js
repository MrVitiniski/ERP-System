(function () {

  const btn = document.getElementById("btnSalvarParada");

  function validar() {
    let ok = true;

    const campos = [
      ["data_parada", "erro_data", "Informe a data"],
      ["turno_parada", "erro_turno", "Informe o turno"],
      ["linha_parada", "erro_linha", "Informe o setor"],
      ["inicio_parada", "erro_inicio", "Informe início"],
      ["fim_parada", "erro_fim", "Informe fim"],
      ["motivo_parada", "erro_motivo", "Informe motivo"]
    ];

    campos.forEach(c => {
      const el = document.getElementById(c[0]);
      const erro = document.getElementById(c[1]);

      if (!el.value.trim()) {
        el.classList.add("input-erro");
        erro.innerText = c[2];
        ok = false;
      } else {
        el.classList.remove("input-erro");
        erro.innerText = "";
      }
    });

    btn.disabled = !ok;
    return ok;
  }

  function calcularTempo(inicio, fim) {
    const [h1, m1] = inicio.split(":").map(Number);
    const [h2, m2] = fim.split(":").map(Number);

    let minutos = (h2 * 60 + m2) - (h1 * 60 + m1);
    if (minutos < 0) minutos += 1440;

    return minutos;
  }

  function salvarParada() {
    if (!validar()) return;

    const parada = {
      data: document.getElementById("data_parada").value,
      turno: document.getElementById("turno_parada").value,
      linha: document.getElementById("linha_parada").value,
      inicio: document.getElementById("inicio_parada").value,
      fim: document.getElementById("fim_parada").value,
      motivo: document.getElementById("motivo_parada").value
    };

    parada.tempo = calcularTempo(parada.inicio, parada.fim);

    // 🔗 SALVA JUNTO COM PRODUÇÃO (MESMO PADRÃO)
    let lista = JSON.parse(localStorage.getItem("paradasProducao") || "[]");
    lista.push(parada);
    localStorage.setItem("paradasProducao", JSON.stringify(lista));

    renderizar();

    alert("Parada registrada com sucesso!");

    document.querySelectorAll("input, select").forEach(el => el.value = "");
    btn.disabled = true;
  }

  function renderizar() {
    const tbody = document.getElementById("listaParadas");
    const lista = JSON.parse(localStorage.getItem("paradasProducao") || "[]");

    tbody.innerHTML = lista.map(p => `
      <tr>
        <td>${p.data}</td>
        <td>${p.turno}</td>
        <td>${p.linha}</td>
        <td>${p.inicio}</td>
        <td>${p.fim}</td>
        <td>${p.tempo}</td>
        <td>${p.motivo}</td>
      </tr>
    `).join("");
  }

  document.querySelectorAll("input, select").forEach(el => {
    el.addEventListener("input", validar);
  });

  btn.addEventListener("click", salvarParada);

  renderizar();

})();