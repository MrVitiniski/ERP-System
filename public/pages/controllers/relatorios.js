(function(){

  const tbody = document.getElementById("tbodyRelatorios");

  // 🔍 BUSCAR RELATÓRIOS
  window.buscarRelatorios = async function(){

    const inicio = document.getElementById("filtro_inicio").value;
    const fim = document.getElementById("filtro_fim").value;

    try {

      const res = await fetch(`/sistema/public/api/producao_listar.php?inicio=${inicio}&fim=${fim}`);
      const out = await res.json();

      if(!out.ok){
        alert("Erro ao buscar dados");
        return;
      }

      renderizarTabela(out.items);
      gerarResumo(out.items);

    } catch(e){
      console.error(e);
      alert("Erro de conexão");
    }
  };

  // 📋 TABELA
function renderizarTabela(lista) {
    const tbody = document.getElementById("corpoTabela"); // Verifique se o ID é este

    if (!lista || !lista.length) {
        tbody.innerHTML = `<tr><td colspan="15">Nenhum registro encontrado</td></tr>`;
        return;
    }

    tbody.innerHTML = lista.map(r => {
        // 1. TRATA OS MOTIVOS (Extrai do JSON que o PHP mandou)
        let motivosTexto = "Sem Parada";
        if (r.lista_paradas && Array.isArray(r.lista_paradas) && r.lista_paradas.length > 0) {
            motivosTexto = r.lista_paradas.map(p => p.motivo).join(" | ");
        }

        return `
            <tr>
                <td>${r.data}</td>
                <td>${r.turno}</td>
                <td>${r.responsavel}</td>
                <td>${r.operador}</td>
                <td>${r.material}</td>
                <td>${r.tc01}</td>
                <td>${r.tc02}</td>
                <td>${r.tc03}</td>
                <td>${r.tc04}</td>
                <td>${r.tc05}</td>
                <td style="font-weight:bold;">${r.total}</td>
                
                <!-- EXIBE O MOTIVO DIGITADO PELO OPERADOR -->
                <td style="color: #666; font-style: italic;">${motivosTexto}</td>
                
                <!-- EXIBE A HORA DE ENCERRAMENTO (vinda do SELECT *) -->
                <td style="font-weight:bold; color: blue;">${r.hora_encerramento || '--:--'}</td>
            </tr>
        `;
    }).join("");
}




  // 📊 RESUMO + RANKING
  function gerarResumo(lista){

    let totalGeral = 0;
    let porTurno = {};

    lista.forEach(r => {
      totalGeral += parseFloat(r.total);

      if(!porTurno[r.turno]) porTurno[r.turno] = 0;
      porTurno[r.turno] += parseFloat(r.total);
    });

    // ranking
    const ranking = Object.entries(porTurno)
      .sort((a,b)=>b[1]-a[1])
      .map((t,i)=>`${i+1}º ${t[0]}: ${t[1].toFixed(2)} t`)
      .join("<br>");

    document.getElementById("resumo").innerHTML = `
      <div style="background:#ecfeff; padding:15px; border-radius:8px; border:1px solid #67e8f9;">
        <b>📦 Produção Total:</b> ${totalGeral.toFixed(2)} t
        <br><br>
        <b>🏆 Ranking de Turnos:</b><br>
        ${ranking}
      </div>
    `;
  }

})();