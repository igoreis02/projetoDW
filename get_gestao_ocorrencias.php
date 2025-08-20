<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

// --- Pega os parâmetros do GET ---
$type_filtro = $_GET['type'] ?? 'manutencao'; // Novo filtro de tipo
$status_filtro = $_GET['status'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;

$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // ADICIONADO: Campos de status de instalação (inst_laco, dt_laco, etc.)
    $sql = "SELECT
                m.id_manutencao,
                m.tipo_manutencao,
                m.ocorrencia_reparo,
                m.reparo_finalizado,
                m.inicio_reparo,
                m.fim_reparo,
                m.status_reparo,
                m.inst_laco, m.dt_laco,
                m.inst_base, m.dt_base,
                m.inst_infra, m.data_infra,
                m.inst_energia, m.dt_energia,
                e.nome_equip,
                e.referencia_equip,
                c.nome AS cidade,
                p.nome_prov,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                GROUP_CONCAT(DISTINCT u_tec.nome SEPARATOR ', ') AS tecnicos_nomes
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN provedor AS p ON m.id_provedor = p.id_provedor
            LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
            LEFT JOIN usuario AS u_tec ON mt.id_tecnico = u_tec.id_usuario
            WHERE 1=1";

    $params = [];
    $types = '';
    
    // Filtro por TIPO (Manutenção ou Instalação)
    if ($type_filtro === 'manutencao') {
        $sql .= " AND m.tipo_manutencao IN ('corretiva', 'preditiva', 'preventiva')";
    } elseif ($type_filtro === 'instalacao') {
        $sql .= " AND m.tipo_manutencao = 'instalação'";
    }

    // Filtro por STATUS
    if ($status_filtro !== 'todos') {
        $sql .= " AND m.status_reparo = ?";
        $params[] = $status_filtro;
        $types .= 's';
    }

    // Filtro por DATA DE INÍCIO
    if (!empty($data_inicio)) {
        $sql .= " AND DATE(m.inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }

    // Filtro por DATA DE FIM
    if (!empty($data_fim)) {
        $sql .= " AND DATE(m.inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    $sql .= " GROUP BY m.id_manutencao ORDER BY c.nome, m.inicio_reparo DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro ao preparar a consulta SQL: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

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
        sort($cidades_com_ocorrencias);
        
        $response_data['ocorrencias'] = $ocorrencias_por_cidade;
        $response_data['cidades'] = $cidades_com_ocorrencias;
        
        echo json_encode(['success' => true, 'data' => $response_data]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência encontrada para os filtros selecionados.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>