<?php
header('Content-Type: application/json');
require_once __DIR__ . "/../includes/db.php";

// AJUSTE: Se não vier data, assume HOJE como padrão. 
$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fim    = $_GET['fim']    ?? date('Y-m-d');

try {
    $pdo = db();
    
    // Base do WHERE com status finalizado
    $where = " WHERE status = 'finalizado' ";
    $params = [];

    // Filtro de data
    $where .= " AND data_saida BETWEEN :inicio AND :fim ";
    $params[':inicio'] = $inicio . " 00:00:00";
    $params[':fim']    = $fim . " 23:59:59";

    /**
     * SQL CORRIGIDO PARA KG:
     * 1. Removemos o "/ 1000", pois queremos o total em KG.
     * 2. Removemos os REPLACEs, pois o banco agora guarda apenas números limpos.
     * 3. Usamos ABS(peso_entrada - peso_saida) para garantir o cálculo real da carga.
     */
    
    // 1. Resumo por Cliente
    $sqlClientes = "SELECT cliente_nome, COUNT(*) as viagens, 
                           SUM(CAST(ABS(peso_entrada - peso_saida) AS UNSIGNED)) as ton
                    FROM balanca_pesagens 
                    $where AND cliente_nome != '' AND cliente_nome IS NOT NULL
                    GROUP BY cliente_nome ORDER BY ton DESC";

    // 2. Resumo por Material
    $sqlMateriais = "SELECT material_tipo, COUNT(*) as viagens, 
                            SUM(CAST(ABS(peso_entrada - peso_saida) AS UNSIGNED)) as ton
                     FROM balanca_pesagens 
                     $where AND material_tipo != '' AND material_tipo IS NOT NULL
                     GROUP BY material_tipo ORDER BY ton DESC";

    $stmtC = $pdo->prepare($sqlClientes); 
    $stmtC->execute($params);
    
    $stmtM = $pdo->prepare($sqlMateriais); 
    $stmtM->execute($params);

    echo json_encode([
        "ok" => true,
        "periodo" => ["inicio" => $inicio, "fim" => $fim],
        "por_cliente" => $stmtC->fetchAll(PDO::FETCH_ASSOC),
        "por_material" => $stmtM->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}