(function () {
    const MAQUINAS = {
        WA320: 3.2,
        WA380: 4.5,
        WA430: 5.0
    };

    // ====================== CÁLCULO ======================
    function calcular() {
        let totais = [0, 0, 0, 0, 0]; // TC01 a TC05
        let totalMinutos = 0;

        document.querySelectorAll("#corpoTabelaHoraria tr").forEach(tr => {
            const maq = tr.querySelector(".maq")?.value || "WA320";
            const conchadas = parseFloat(tr.querySelector(".conchadas")?.value) || 0;
            const ton = conchadas * (MAQUINAS[maq] || 0);

            const tc01El = tr.querySelector(".tc01");
            if (tc01El) tc01El.innerText = ton.toFixed(2);
            totais[0] += ton;

            tr.querySelectorAll(".input-num").forEach((inp, index) => {
                totais[index + 1] += parseFloat(inp.value) || 0;
            });

            const minInput = tr.querySelector(".minutos-parada");
            if (minInput) {
                totalMinutos += parseFloat(minInput.value) || 0;
            }
        });

        for (let i = 0; i < 5; i++) {
            const el = document.getElementById(`res_tc0${i + 1}`);
            if (el) el.innerText = totais[i].toFixed(2) + " t";
        }

        const minEl = document.getElementById("res_min_parada");
        if (minEl) minEl.innerText = totalMinutos + " min";
    }

    // ====================== GERAR LINHAS ======================
    window.gerarLinhasHorarias = function () {
        const tbody = document.getElementById("corpoTabelaHoraria");
        const turno = document.getElementById("select_turno").value || "1";
        if (!tbody) return;

        tbody.innerHTML = "";
        let horas = (turno == "1") ? gerarIntervalo(7, 15) : (turno == "2" ? gerarIntervalo(15, 23) : gerarNoturno());

        horas.forEach(h => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td style="font-weight:bold;">${h}:00</td>
                <td>
                  <select class="maq" style="width:90px;">
                    <option value="WA320">WA320</option>
                    <option value="WA380">WA380</option>
                    <option value="WA430">WA430</option>
                  </select>
                </td>
                <td><input type="number" class="conchadas" style="width:65px; text-align:center;"></td>
                <td class="tc01" style="font-weight:bold; width:70px;">0.00</td>
                <td><input type="number" class="input-num" style="width:60px; text-align:center;"></td>
                <td><input type="number" class="input-num" style="width:60px; text-align:center;"></td>
                <td><input type="number" class="input-num" style="width:60px; text-align:center;"></td>
                <td><input type="number" class="input-num" style="width:60px; text-align:center;"></td>
                <td style="border-left: 2px solid #ddd; padding-left:10px;">
                  <input type="number" class="minutos-parada" placeholder="0" min="0" style="width:75px; text-align:center; border-color:#fca5a5;">
                </td>
                <td>
                  <input type="text" class="motivo-parada" placeholder="Descreva o motivo..." style="width:200px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;">
                </td>
            `;
            tbody.appendChild(tr);
        });

        adicionarEventos();
        calcular();
    };

    function gerarIntervalo(inicio, fim) {
        let arr = [];
        for (let i = inicio; i <= fim; i++) arr.push(i.toString().padStart(2, '0'));
        return arr;
    }

    function gerarNoturno() {
        return ["23", "00", "01", "02", "03", "04", "05", "06", "07"];
    }

    function adicionarEventos() {
        document.querySelectorAll("#corpoTabelaHoraria input, #corpoTabelaHoraria select").forEach(el => {
            el.addEventListener("input", calcular);
            el.addEventListener("change", calcular);
        });
    }

    // ====================== ENCERRAR TURNO ======================
    window.encerrarTurnoEGerarRelatorio = async function () {
        const dataProd = document.getElementById("data_prod")?.value;
        const responsavel = document.getElementById("responsavel_turno")?.value;
        const operador = document.getElementById("operador_sala")?.value;
        const corpoTabela = document.getElementById('corpoTabelaHoraria');

        if (!dataProd || !responsavel || !corpoTabela) {
            alert("⚠️ Preencha a Data da Produção e o Responsável antes de encerrar.");
            return;
        }

        const btn = document.getElementById("btnEncerrar");
        btn.disabled = true;
        btn.innerHTML = "Salvando...";

        const dadosLoteFinal = [];
        corpoTabela.querySelectorAll('tr').forEach(linha => {
            const inputsNum = linha.querySelectorAll('.input-num');
            const payload = {
                data: dataProd,
                responsavel_turno: responsavel, // Envia o nome para o banco
                operador_sala: operador,       // Envia o nome para o banco
                hora: linha.cells[0]?.innerText.trim(),
                maquina: linha.querySelector('.maq')?.value || 'WA320',
                conchadas: linha.querySelector('.conchadas')?.value || 0,
                tc01: linha.querySelector('.tc01')?.innerText || 0,
                tc02: inputsNum[0]?.value || 0,
                tc03: inputsNum[1]?.value || 0,
                tc04: inputsNum[2]?.value || 0,
                tc05: inputsNum[3]?.value || 0,
                minutos_parada: linha.querySelector('.minutos-parada')?.value || 0,
                motivo_parada: linha.querySelector('.motivo-parada')?.value || ''
            };
            if (parseFloat(payload.conchadas) > 0 || parseFloat(payload.minutos_parada) > 0) {
                dadosLoteFinal.push(payload);
            }
        });

        try {
            const response = await fetch('/sistema/public/api/save_producao_lote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dadosLoteFinal) 
            });
            const result = await response.json();
            if (result.ok) {
                alert("✅ Turno encerrado e sincronizado!");
                window.location.href = "login.php";
            }
        } catch (error) {
            alert("❌ Erro ao encerrar: " + error.message);
            btn.disabled = false;
            btn.innerHTML = "🔒 Encerrar Turno";
        }
    };

    setTimeout(gerarLinhasHorarias, 300);
})();

// ====================== AUTO-SAVE (5 SEGUNDOS) ======================
setInterval(function () {
    const campoData = document.getElementById('data_prod');
    const responsavel = document.getElementById('responsavel_turno');
    const operador = document.getElementById('operador_sala');
    const corpoTabela = document.getElementById('corpoTabelaHoraria');

    if (!campoData || !campoData.value || !corpoTabela) return;

    const dadosLote = [];
    corpoTabela.querySelectorAll('tr').forEach(linha => {
        const inputsNum = linha.querySelectorAll('.input-num');
        
        const payload = {
            data: campoData.value,
            responsavel_turno: responsavel?.value || '', // Pega o nome do Gilberto
            operador_sala: operador?.value || '',       // Pega o nome do Valmir
            hora: linha.cells[0]?.innerText.trim(),
            maquina: linha.querySelector('.maq')?.value || 'WA320',
            conchadas: linha.querySelector('.conchadas')?.value || 0,
            tc01: linha.querySelector('.tc01')?.innerText || 0,
            tc02: inputsNum[0]?.value || 0,
            tc03: inputsNum[1]?.value || 0,
            tc04: inputsNum[2]?.value || 0,
            tc05: inputsNum[3]?.value || 0,
            minutos_parada: linha.querySelector('.minutos-parada')?.value || 0,
            motivo_parada: linha.querySelector('.motivo-parada')?.value || ''
        };

        if (parseFloat(payload.conchadas) > 0 || parseFloat(payload.minutos_parada) > 0) {
            dadosLote.push(payload);
        }
    });

    if (dadosLote.length > 0) {
        fetch('/sistema/public/api/save_producao_lote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dadosLote)
        })
        .then(res => res.json())
        .then(json => {
            if (json.ok) console.log("%c✔ DASHBOARD ATUALIZADO", "background: #10b981; color: white; padding: 2px 5px; border-radius: 4px;");
        })
        .catch(err => console.error("Erro Auto-Save:", err));
    }
}, 5000);