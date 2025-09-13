<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

// Parâmetros de filtro
$tipo_filter = $_GET['tipo'] ?? 'manutencao';
$status_filter = $_GET['status'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$sql = "";
$params = [];
$types = '';

try {
    // Verificamos as duas tabelas que alimentam esta página
    $tables = ['ocorrencia_processamento', 'manutencoes'];
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
    

    if ($status_filter === 'validacao') {
        // Consulta específica para o status 'validação' (busca em 'manutencoes')
        $sql = "
            SELECT
                m.id_manutencao AS id, 'manutencao' AS origem, m.ocorrencia_reparo,
                m.reparo_finalizado, m.status_reparo AS status, m.inicio_reparo, m.fim_reparo,
                e.nome_equip, e.referencia_equip, c.nome AS nome_cidade,
                CONCAT(e.referencia_equip, ', ', en.logradouro, ', ', en.bairro) AS local_completo,
                u.nome AS atribuido_por
            FROM manutencoes m
            JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
            JOIN cidades c ON e.id_cidade = c.id_cidade
            JOIN endereco en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
            WHERE m.status_reparo = 'validacao'
        ";
        if (!empty($data_inicio)) { $sql .= " AND DATE(m.inicio_reparo) >= ?"; $params[] = $data_inicio; $types .= 's'; }
        if (!empty($data_fim)) { $sql .= " AND DATE(m.inicio_reparo) <= ?"; $params[] = $data_fim; $types .= 's'; }
        $sql .= " ORDER BY c.nome, m.inicio_reparo DESC";

    } elseif ($status_filter === 'todos') {
        $sql = "
            (SELECT
                m.id_manutencao AS id, 'manutencao' AS origem, m.ocorrencia_reparo,
                m.reparo_finalizado, m.status_reparo AS status, m.inicio_reparo, m.fim_reparo,
                e.nome_equip, e.referencia_equip, c.nome AS nome_cidade,
                CONCAT(e.referencia_equip, ', ', en.logradouro, ', ', en.bairro) AS local_completo,
                u.nome AS atribuido_por
            FROM manutencoes m
            JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
            JOIN cidades c ON e.id_cidade = c.id_cidade
            JOIN endereco en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario u ON m.id_usuario = u.id_usuario
            WHERE m.status_reparo = 'validacao')
            
            UNION ALL
            
            (SELECT
                op.id_ocorrencia_processamento AS id, 'processamento' AS origem, op.descricao AS ocorrencia_reparo,
                m.reparo_finalizado, op.status, op.dt_ocorrencia AS inicio_reparo, op.dt_resolucao AS fim_reparo,
                e.nome_equip, e.referencia_equip, c.nome AS nome_cidade,
                CONCAT(e.referencia_equip, ', ', en.logradouro, ', ', en.bairro) AS local_completo,
                u.nome AS atribuido_por
            FROM ocorrencia_processamento op
            JOIN manutencoes m ON op.id_manutencao = m.id_manutencao
            JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
            JOIN cidades c ON e.id_cidade = c.id_cidade
            JOIN endereco en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario u ON op.id_usuario_registro = u.id_usuario
            WHERE 1=1)

            ORDER BY
                FIELD(status, 'validacao', 'pendente', 'concluido', 'cancelado'),
                nome_cidade,
                inicio_reparo DESC
        ";
    } else {
        $sql = "
            SELECT
                op.id_ocorrencia_processamento AS id, 'processamento' AS origem, op.descricao AS ocorrencia_reparo,
                m.reparo_finalizado, op.status, op.dt_ocorrencia AS inicio_reparo, op.dt_resolucao AS fim_reparo,
                e.nome_equip, e.referencia_equip, c.nome AS nome_cidade,
                CONCAT(e.referencia_equip, ', ', en.logradouro, ', ', en.bairro) AS local_completo,
                u.nome AS atribuido_por
            FROM ocorrencia_processamento op
            JOIN manutencoes m ON op.id_manutencao = m.id_manutencao
            JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
            JOIN cidades c ON e.id_cidade = c.id_cidade
            JOIN endereco en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario u ON op.id_usuario_registro = u.id_usuario
            WHERE 1=1 AND op.status = ?
        ";
        $params[] = $status_filter;
        $types .= 's';

        if ($tipo_filter === 'manutencao') { $sql .= " AND op.tipo_ocorrencia IN ('preditiva', 'corretiva')"; } 
        elseif ($tipo_filter === 'instalacao') { $sql .= " AND op.tipo_ocorrencia = 'instalacao'"; }
        if (!empty($data_inicio)) { $sql .= " AND DATE(op.dt_ocorrencia) >= ?"; $params[] = $data_inicio; $types .= 's'; }
        if (!empty($data_fim)) { $sql .= " AND DATE(op.dt_ocorrencia) <= ?"; $params[] = $data_fim; $types .= 's'; }
        $sql .= " ORDER BY c.nome, op.dt_ocorrencia DESC";
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $ocorrencias_data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

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