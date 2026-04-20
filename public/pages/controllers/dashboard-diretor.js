(function(){

async function carregar(){
  const res = await fetch('/sistema/public/api/producao_listar.php');
  const dados = await res.json();

  if(!dados.ok){
    alert("Erro ao carregar dados");
    return;
  }

  analisar(dados.data);
}

function analisar(lista){

  let total=0, alimentacao=0;

  let turnos={};
  let maquinas={WA320:0,WA380:0,WA430:0};
  let perdas=[];

  let datas={};

  lista.forEach(r=>{

    total+=r.total;
    alimentacao+=r.tc01;

    // TURNOS
    turnos[r.turno]=(turnos[r.turno]||0)+r.total;

    // MÁQUINAS
    if(r.maquina){
      maquinas[r.maquina]=(maquinas[r.maquina]||0)+r.tc01;
    }

    // PERDA
    perdas.push(r.tc01 - r.total);

    // EVOLUÇÃO
    datas[r.data]=(datas[r.data]||0)+r.total;
  });

  // KPI
  document.getElementById("kpi_total").innerText=total.toFixed(0)+" t";
  document.getElementById("kpi_alimentacao").innerText=alimentacao.toFixed(0)+" t";

  let eficiencia=(total/alimentacao)*100 || 0;
  document.getElementById("kpi_eficiencia").innerText=eficiencia.toFixed(1)+"%";

  let perdaTotal = alimentacao-total;
  document.getElementById("kpi_perda").innerText=perdaTotal.toFixed(0)+" t";

  // MELHOR TURNO
  let melhorTurno = Object.entries(turnos).sort((a,b)=>b[1]-a[1])[0];
  document.getElementById("kpi_turno").innerText = melhorTurno?melhorTurno[0]:"-";

  // MELHOR MÁQUINA
  let melhorMaq = Object.entries(maquinas).sort((a,b)=>b[1]-a[1])[0];
  document.getElementById("kpi_maquina").innerText = melhorMaq?melhorMaq[0]:"-";

  gerarGraficos(datas, turnos, maquinas, perdas);
  gerarAlertas(eficiencia, perdaTotal);
}

function gerarGraficos(datas, turnos, maquinas, perdas){

  new Chart(grafEvolucao,{
    type:"line",
    data:{
      labels:Object.keys(datas),
      datasets:[{label:"Produção diária",data:Object.values(datas)}]
    }
  });

  new Chart(grafTurnos,{
    type:"bar",
    data:{
      labels:Object.keys(turnos),
      datasets:[{label:"Produção por turno",data:Object.values(turnos)}]
    }
  });

  new Chart(grafMaquinas,{
    type:"bar",
    data:{
      labels:Object.keys(maquinas),
      datasets:[{label:"Uso de máquinas",data:Object.values(maquinas)}]
    }
  });

  new Chart(grafPerdas,{
    type:"line",
    data:{
      labels:perdas.map((_,i)=>i+1),
      datasets:[{label:"Perda por turno",data:perdas}]
    }
  });
}

function gerarAlertas(eficiencia, perda){

  const el = document.getElementById("alerta");

  if(eficiencia < 70){
    el.innerHTML = "🔴 ALERTA: Eficiência BAIXA!";
    el.style.color="red";
  }
  else if(eficiencia < 85){
    el.innerHTML = "🟡 Atenção: eficiência média";
    el.style.color="orange";
  }
  else{
    el.innerHTML = "🟢 Operação eficiente";
    el.style.color="green";
  }

  if(perda > 500){
    el.innerHTML += "<br>⚠️ Perda operacional alta!";
  }
}

carregar();

})();
