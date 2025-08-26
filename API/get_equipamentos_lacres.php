<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$search_term = $_GET['search'] ?? '';
$response = ['success' => false];

try {
    // Busca principal dos equipamentos, agora com filtro para tipo
    $sql_equip = "SELECT 
                    e.id_equipamento,
                     e.nome_equip, 
                     e.referencia_equip, 
                     e.qtd_faixa, 
                    e.km, e.sentido, e.num_instrumento, e.dt_afericao, e.dt_vencimento
                  FROM equipamentos e
                  WHERE (e.nome_equip LIKE ? OR e.referencia_equip LIKE ?)
                  AND e.tipo_equip NOT IN ('CCO', 'DOME')
                  ORDER BY e.nome_equip ASC";
                  
    $stmt_equip = $conn->prepare($sql_equip);
    $param = "%" . $search_term . "%";
    $stmt_equip->bind_param("ss", $param, $param);
    $stmt_equip->execute();
    $result_equip = $stmt_equip->get_result();
    
    $equipamentos = [];
    while ($row = $result_equip->fetch_assoc()) {
        $row['lacres'] = []; // Inicializa um array para os lacres
        $equipamentos[$row['id_equipamento']] = $row;
    }
    $stmt_equip->close();

    // Busca os lacres para os equipamentos encontrados
    if (!empty($equipamentos)) {
        $ids = array_keys($equipamentos);
        $id_list = implode(',', $ids);

        $sql_lacres = "SELECT id_equipamento, local_lacre, num_lacre 
                       FROM controle_lacres 
                       WHERE id_equipamento IN ($id_list) AND lacre_afixado = 1 
                       ORDER BY id_controle_lacres DESC";
                       
        $result_lacres = $conn->query($sql_lacres);
        while ($lacre = $result_lacres->fetch_assoc()) {
            // Adiciona cada lacre ao seu respectivo equipamento
            $equipamentos[$lacre['id_equipamento']]['lacres'][] = $lacre;
        }
    }
    
    $response['success'] = true;
    // Reindexa o array para ser uma lista JSON
    $response['equipamentos'] = array_values($equipamentos);

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>