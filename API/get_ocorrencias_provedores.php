<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

// --- COLETA E VALIDAÇÃO DOS FILTROS ---
$tipo = isset($_GET['tipo']) ? $conn->real_escape_string($_GET['tipo']) : 'manutencao';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'todos';
$data_inicio = isset($_GET['data_inicio']) ? $conn->real_escape_string($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? $conn->real_escape_string($_GET['data_fim']) : '';

$response_data = [];
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];

try {

    $tables = ['ocorrencia_provedor', 'manutencoes'];
    $totalChecksum = 0;
    foreach ($tables as $table) {
        $result = $conn->query("CHECKSUM TABLE `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalChecksum += (int)$row['Checksum'];
        } else {
            throw new Exception("Erro ao calcular checksum para a tabela: $table");
        }
    }
    // Query principal para buscar dados da nova tabela 'ocorrencia_provedor'
    $sql_nova = "SELECT
        op.id_ocorrencia_provedor AS id,
        op.des_ocorrencia AS ocorrencia_reparo,
        op.dt_inicio_reparo AS inicio_reparo,
        op.dt_fim_reparo as fim_reparo,
        op.status,
        op.des_reparo AS reparo_finalizado,
        op.provedor,
        op.inLoco,
        op.sem_intervencao,
        op.tecnico_dw,
        e.nome_equip,
        e.referencia_equip,
        c.nome AS cidade,
        p.nome_prov,
        CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
        SUBSTRING_INDEX(u_ini.nome, ' ', 1) AS atribuido_por,
        'ocorrencia_provedor' as origem
    FROM ocorrencia_provedor AS op
    JOIN equipamentos AS e ON op.id_equipamento = e.id_equipamento
    JOIN cidades AS c ON op.id_cidade = c.id_cidade
    LEFT JOIN provedor AS p ON op.id_provedor = p.id_provedor
    LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
    LEFT JOIN usuario AS u_ini ON op.id_usuario_iniciou = u_ini.id_usuario";

    // Query para buscar dados da tabela antiga 'manutencoes'
    $sql_antiga = "SELECT
        m.id_manutencao AS id,
        m.ocorrencia_reparo,
        m.inicio_reparo,
        m.fim_reparo,
        m.status_reparo AS status,
        m.reparo_finalizado,
        NULL as provedor,
        NULL as inLoco,
        NULL as sem_intervencao,
        NULL as tecnico_dw,
        e.nome_equip,
        e.referencia_equip,
        c.nome AS cidade,
        p.nome_prov,
        CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
        SUBSTRING_INDEX(u.nome, ' ', 1) AS atribuido_por,
        'manutencoes' as origem
    FROM manutencoes AS m
    JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
    JOIN cidades AS c ON m.id_cidade = c.id_cidade
    LEFT JOIN provedor AS p ON m.id_provedor = p.id_provedor
    LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
    LEFT JOIN usuario AS u ON m.id_usuario = u.id_usuario
    WHERE m.id_provedor IS NOT NULL";

    // Combina as duas queries com UNION ALL
    $sql = "($sql_nova) UNION ALL ($sql_antiga)";
    
    // Constrói a cláusula WHERE com base nos filtros
    $where_clauses = [];
    $params = [];
    $types = '';

    if (!empty($tipo)) {
        // Este filtro é complexo para UNION, então aplicamos no PHP
    }

    if (!empty($status) && $status !== 'todos') {
        $where_clauses[] = "sub.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    if (!empty($data_inicio)) {
        $where_clauses[] = "DATE(sub.inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if (!empty($data_fim)) {
        $where_clauses[] = "DATE(sub.inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    $final_sql = "SELECT 
    sub.id,
    sub.ocorrencia_reparo,
    sub.inicio_reparo,
    sub.fim_reparo,
    sub.status,
    sub.reparo_finalizado,
    sub.provedor,
    sub.inLoco,
    sub.sem_intervencao,
    sub.tecnico_dw,
    sub.nome_equip,
    sub.referencia_equip,
    sub.cidade,
    sub.nome_prov,
    sub.local_completo,
    sub.atribuido_por,
    sub.origem
FROM ($sql) AS sub";
    if (!empty($where_clauses)) {
        $final_sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    $final_sql .= " ORDER BY FIELD(sub.status, 'pendente', 'concluido', 'cancelado'), sub.cidade, sub.inicio_reparo DESC";

    $stmt = $conn->prepare($final_sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cidade = $row['cidade'];
            if (!isset($ocorrencias_por_cidade[$cidade])) {
                $ocorrencias_por_cidade[$cidade] = [];
            }
            $ocorrencias_por_cidade[$cidade][] = $row;
            if (!in_array($cidade, $cidades_com_ocorrencias)) {
                $cidades_com_ocorrencias[] = $cidade;
            }
        }
        $response_data['ocorrencias'] = $ocorrencias_por_cidade;
        $response_data['cidades'] = $cidades_com_ocorrencias;
        echo json_encode([
            'success' => true, 
            'checksum' => $totalChecksum,
            'data' => $response_data
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'checksum' => $totalChecksum,
            'data' => ['ocorrencias' => [], 'cidades' => []],
            'message' => 'Nenhuma ocorrência de provedor encontrada.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>