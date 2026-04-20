(function () {

  const MAQUINAS = { "WA320": 3.2, "WA380": 4.5, "WA430": 5.0 };

  function calcularTudo() {
    let totais = [0, 0, 0, 0, 0];
    let minTotal = 0;

    // 🔹 TON + TC
    document.querySelectorAll("#corpoTabelaHoraria tr").forEach(tr => {

      const maq = tr.querySelector(".maq")?.value || "WA320";
      const conchadas = parseFloat(tr.querySelector(".conchadas")?.value) || 0;
      const ton = conchadas * (MAQUINAS[maq] || 0);

      const tc01El = tr.querySelector(".tc01-valor");
      if (tc01El) tc01El.value = ton.toFixed(2);
      totais[0] += ton;

      tr.querySelectorAll(".input-num").forEach(inp => {
        const idx = parseInt(inp.dataset.idx);
        if (idx >= 1 && idx <= 4) {
          totais[idx] += parseFloat(inp.value) || 0;
        }
      });
    });

    // 🔥 MINUTOS (DEBUG ATIVO)
    document.querySelectorAll(".minutos-parada").forEach(input => {

  let valor = input.value;

  // 🔥 se vier vazio ou "Min", ignora
  if (!valor || valor.toLowerCase() === "min") return;

  // 🔥 força número
  valor = valor.replace(",", ".");
  valor = valor.replace(/[^\d.]/g, "");

  const numero = parseFloat(valor);

  if (!isNaN(numero)) {
    minTotal += numero;
  }
});

    console.log("TOTAL MIN:", minTotal); // 👈 DEBUG FINAL

    // Atualiza toneladas
    for (let i = 0; i < 5; i++) {
      const el = document.getElementById(`res_tc0${i+1}`);
      if (el) el.textContent = totais[i].toFixed(2) + " t";
    }

    // Atualiza minutos
    document.getElementById("res_min_parada").textContent = minTotal + " min";
  }

  window.gerarLinhasHorarias = function () {
    const tbody = document.getElementById("corpoTabelaHoraria");

    const horas = ["07","08","09","10","11","12","13","14","15"];

    tbody.innerHTML = horas.map(h => `
      <tr>
        <td><input type="text" value="${h}:00" readonly style="width:70px;text-align:center;font-weight:bold;"></td>
        
        <td>
          <select class="maq">
            <option value="WA320">WA320</option>
            <option value="WA380">WA380</option>
            <option value="WA430">WA430</option>
          </select>
        </td>

        <td><input type="number" class="conchadas"></td>

        <td><input type="text" class="tc01-valor" value="0.00" readonly></td>

        <td><input type="number" class="input-num" data-idx="1"></td>
        <td><input type="number" class="input-num" data-idx="2"></td>
        <td><input type="number" class="input-num" data-idx="3"></td>
        <td><input type="number" class="input-num" data-idx="4"></td>

        <td>
          <input type="number" class="minutos-parada">
        </td>

        <td>
          <select>
            <option value="">Sem Parada</option>
            <option value="Mecânica">Mecânica</option>
          </select>
        </td>
      </tr>
    `).join('');

    calcularTudo();
  };

  // 🔥 EVENTO GLOBAL FORTE (RESOLVE 100%)
  document.addEventListener("input", function(e){
    if (
      e.target.classList.contains("minutos-parada") ||
      e.target.classList.contains("conchadas") ||
      e.target.classList.contains("input-num")
    ) {
      calcularTudo();
    }
  });

  document.addEventListener("change", calcularTudo);

  document.addEventListener("DOMContentLoaded", gerarLinhasHorarias);

})();