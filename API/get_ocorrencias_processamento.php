<?php
// /API/get_ocorrencias_processamento.php - VERSÃO CORRIGIDA

header('Content-Type: application/json');
require_once 'conexao_bd.php';

// Parâmetros de filtro
$status_filter = $_GET['status'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

try {
    //Checksum de ambas as tabelas relevantes ---
    $checksum_result_proc = $conn->query("CHECKSUM TABLE `ocorrencia_processamento`");
    $checksum_result_manut = $conn->query("CHECKSUM TABLE `manutencoes`");
    $totalChecksum = 0;
    if($checksum_result_proc) {
        $row = $checksum_result_proc->fetch_assoc();
        $totalChecksum += (int)$row['Checksum'];
    }
    if($checksum_result_manut) {
        $row = $checksum_result_manut->fetch_assoc();
        $totalChecksum += (int)$row['Checksum'];
    }

    // Prepara as duas consultas separadamente ---

    // Query 1: Ocorrências da tabela 'ocorrencia_processamento'
    $sql_processamento = "
        SELECT
            op.id_ocorrencia_processamento AS id,
            'ocorrencia_processamento' AS origem,
            op.descricao AS ocorrencia_reparo,
            op.reparo AS reparo_finalizado,
            op.status,
            op.dt_ocorrencia AS inicio_reparo,
            op.dt_resolucao AS fim_reparo,
            e.nome_equip,
            e.referencia_equip,
            c.nome AS nome_cidade,
            CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
            SUBSTRING_INDEX(u_reg.nome, ' ', 2) AS atribuido_por,
            SUBSTRING_INDEX(u_conc.nome, ' ', 2) AS concluido_por
        FROM ocorrencia_processamento op
        JOIN equipamentos e ON op.id_equipamento = e.id_equipamento
        LEFT JOIN cidades c ON op.id_cidade = c.id_cidade
        LEFT JOIN endereco en ON e.id_endereco = en.id_endereco
        LEFT JOIN usuario u_reg ON op.id_usuario_registro = u_reg.id_usuario
        LEFT JOIN usuario u_conc ON op.id_usuario_concluiu = u_conc.id_usuario
    ";

    // Query 2: Ocorrências da tabela 'manutencoes' que estão em validação
    $sql_validacao = "
        SELECT 
            m.id_manutencao as id,
            'manutencao' as origem,
            m.ocorrencia_reparo,
            m.reparo_finalizado,
            m.status_reparo as status,
            m.inicio_reparo,
            m.fim_reparo,
            e.nome_equip,
            e.referencia_equip,
            c.nome as nome_cidade,
            CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
            SUBSTRING_INDEX(u.nome, ' ', 2) AS atribuido_por,
            NULL AS concluido_por
        FROM manutencoes m
        JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
        LEFT JOIN cidades c ON e.id_cidade = c.id_cidade
        LEFT JOIN endereco en ON e.id_endereco = en.id_endereco
        LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
        WHERE m.status_reparo = 'validacao'
    ";

    // Combina as queries com base no filtro de status 
    $final_sql = "";
    if ($status_filter === 'validacao') {
        // Se o filtro for 'validacao', busca apenas da tabela de manutenções
        $final_sql = $sql_validacao;
    } else if ($status_filter === 'todos') {
        // Se o filtro for 'todos', une as duas consultas
        $final_sql = "($sql_processamento) UNION ALL ($sql_validacao)";
    } else {
        // Para outros status ('pendente', 'concluido', etc.), busca apenas em 'ocorrencia_processamento'
        $final_sql = $sql_processamento;
    }

    // --- Construção dinâmica da cláusula WHERE para a consulta final ---
    $where_conditions = [];
    $params = [];
    $types = '';

    // Aplica o filtro de status apenas se não for 'todos' ou 'validacao' (já filtrados na estrutura da query)
    if ($status_filter !== 'todos' && $status_filter !== 'validacao') {
        $where_conditions[] = "sub.status = ?";
        $params[] = $status_filter;
        $types .= 's';
    }
    if (!empty($data_inicio)) {
        $where_conditions[] = "DATE(sub.inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if (!empty($data_fim)) {
        $where_conditions[] = "DATE(sub.inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    // Envolve a query final em uma subconsulta para aplicar os filtros de data
    $query_to_execute = "SELECT * FROM ($final_sql) AS sub";
    if (!empty($where_conditions)) {
        $query_to_execute .= " WHERE " . implode(' AND ', $where_conditions);
    }
    $query_to_execute .= " ORDER BY FIELD(sub.status, 'validacao', 'pendente', 'concluido', 'cancelado'), nome_cidade, sub.inicio_reparo DESC";

    $stmt = $conn->prepare($query_to_execute);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Agrupamento dos dados por cidade 
    $grouped_ocorrencias = [];
    $cities = [];
    foreach ($ocorrencias_data as $item) {
        $city_name = $item['nome_cidade'] ?? 'Sem Cidade';
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