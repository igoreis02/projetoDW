<?php
// /API/get_ocorrencias_processamento.php - VERSÃO SIMPLIFICADA

header('Content-Type: application/json');
require_once 'conexao_bd.php';

// Parâmetros de filtro
$status_filter = $_GET['status'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

try {
    // Checksum agora apenas da tabela relevante
    $checksum_result = $conn->query("CHECKSUM TABLE `ocorrencia_processamento`");
    $totalChecksum = 0;
    if($checksum_result) {
        $row = $checksum_result->fetch_assoc();
        $totalChecksum = (int)$row['Checksum'];
    }

    $sql = "
        SELECT
            op.id_ocorrencia_processamento AS id,
            'processamento' AS origem,
            op.descricao AS ocorrencia_reparo,
            op.reparo AS reparo_finalizado,
            op.status,
            op.dt_ocorrencia AS inicio_reparo,
            op.dt_resolucao AS fim_reparo,
            e.nome_equip,
            e.referencia_equip,
            c.nome AS nome_cidade,
            CONCAT(e.referencia_equip, ', ', en.logradouro, ', ', en.bairro) AS local_completo,
            u.nome AS atribuido_por
        FROM ocorrencia_processamento op
        JOIN equipamentos e ON op.id_equipamento = e.id_equipamento
        LEFT JOIN cidades c ON op.id_cidade = c.id_cidade
        LEFT JOIN endereco en ON e.id_endereco = en.id_endereco
        LEFT JOIN usuario u ON op.id_usuario_registro = u.id_usuario
    ";

    // --- Construção dinâmica da cláusula WHERE ---
    $where_conditions = [];
    $params = [];
    $types = '';

    if ($status_filter !== 'todos') {
        $where_conditions[] = "op.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    if (!empty($data_inicio)) {
        $where_conditions[] = "DATE(op.dt_ocorrencia) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if (!empty($data_fim)) {
        $where_conditions[] = "DATE(op.dt_ocorrencia) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }

    // Ordenação final
    $sql .= " ORDER BY FIELD(op.status, 'pendente', 'concluido', 'cancelado'), nome_cidade, op.dt_ocorrencia DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Agrupamento dos dados por cidade (lógica inalterada)
    $grouped_ocorrencias = [];
    $cities = [];
    foreach ($ocorrencias_data as $item) {
        $city_name = $item['nome_cidade'];
        if (!isset($grouped_ocorrencias[$city_name])) {
            $grouped_ocorrencias[$city_name] = [];
        }
        $grouped_ocorrencias[$city_name][] = $item;
        if (!in_array($city_name, $cities)) {
            $cities[] = $city_name;
        }
    }
    sort($cities);
    
    echo json_encode([
        'success' => true, 
        'checksum' => $totalChecksum,
        'data' => ['ocorrencias' => $grouped_ocorrencias, 'cidades' => $cities]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>