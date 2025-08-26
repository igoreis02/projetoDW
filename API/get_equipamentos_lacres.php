<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

// Novos parâmetros para filtro de data
$search_term = $_GET['search'] ?? '';
$date_type = $_GET['date_type'] ?? null;
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

$response = ['success' => false];

try {
    $sql_equip = "SELECT 
                    e.id_equipamento,
                    e.nome_equip, 
                    e.referencia_equip, 
                    e.qtd_faixa, 
                    e.km, e.sentido, e.num_instrumento, e.dt_afericao, e.dt_vencimento,
                    c.nome as cidade_nome
                  FROM equipamentos e
                  LEFT JOIN cidades c ON e.id_cidade = c.id_cidade
                  WHERE (e.nome_equip LIKE ? OR e.referencia_equip LIKE ?)
                  AND e.tipo_equip NOT IN ('CCO', 'DOME')";
    
    $params = ["%" . $search_term . "%", "%" . $search_term . "%"];
    $types = "ss";

    // Adiciona o filtro de data à query se os parâmetros forem válidos
    if ($date_type && $start_date && $end_date) {
        if ($date_type === 'dt_afericao' || $date_type === 'dt_vencimento') {
            $sql_equip .= " AND e.{$date_type} BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $types .= "ss";
        }
    }
                  
    $sql_equip .= " ORDER BY c.nome, e.nome_equip ASC";
                  
    $stmt_equip = $conn->prepare($sql_equip);
    $stmt_equip->bind_param($types, ...$params);
    $stmt_equip->execute();
    $result_equip = $stmt_equip->get_result();
    
    $equipamentos = [];
    while ($row = $result_equip->fetch_assoc()) {
        $row['lacres'] = [];
        $equipamentos[$row['id_equipamento']] = $row;
    }
    $stmt_equip->close();

    if (!empty($equipamentos)) {
        $ids = array_keys($equipamentos);
        $id_list = implode(',', $ids);

        $sql_lacres = "SELECT lc.id_equipamento, lc.local_lacre, lc.num_lacre
                       FROM controle_lacres lc
                       INNER JOIN (
                           SELECT id_equipamento, local_lacre, MAX(id_controle_lacres) as max_id
                           FROM controle_lacres
                           WHERE id_equipamento IN ($id_list)
                           GROUP BY id_equipamento, local_lacre
                       ) as latest_lacres ON lc.id_controle_lacres = latest_lacres.max_id
                       WHERE lc.lacre_afixado = 1";
                       
        $result_lacres = $conn->query($sql_lacres);
        while ($lacre = $result_lacres->fetch_assoc()) {
            if (isset($equipamentos[$lacre['id_equipamento']])) {
                $equipamentos[$lacre['id_equipamento']]['lacres'][] = $lacre;
            }
        }
    }
    
    $response['success'] = true;
    $response['equipamentos'] = array_values($equipamentos);

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>