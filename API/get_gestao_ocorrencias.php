<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

// --- Pega os parâmetros do GET ---
$type_filtro = $_GET['type'] ?? 'manutencao';
$status_filtro = $_GET['status'] ?? 'todos';
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$search_term = $_GET['search'] ?? null;

$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // --- BLOCO DE CÁLCULO DE CHECKSUM GLOBAL ---
     $tables = ['manutencoes', 'ocorrencia_semaforica', 'ocorrencia_provedor', 'ocorrencia_processamento', 'solicitacao_cliente', 'equipamentos'];
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

    $sql = "SELECT
                m.id_manutencao, m.tipo_manutencao, m.ocorrencia_reparo,
                m.reparo_finalizado, m.inicio_reparo, m.fim_reparo, m.status_reparo,
                m.inst_laco, m.dt_laco, m.inst_base, m.dt_base,
                m.inst_infra, m.data_infra, m.inst_energia, m.dt_energia,
                m.inst_prov, m.data_provedor, e.nome_equip, e.referencia_equip,
                e.tipo_equip, c.nome AS cidade, p.nome_prov,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                SUBSTRING_INDEX(u.nome, ' ', 2) AS atribuido_por, 
                GROUP_CONCAT(DISTINCT u_tec.nome SEPARATOR ', ') AS tecnicos_nomes
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN provedor AS p ON m.id_provedor = p.id_provedor
            LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
            LEFT JOIN usuario AS u_tec ON mt.id_tecnico = u_tec.id_usuario
            LEFT JOIN usuario AS u ON m.id_usuario = u.id_usuario 
            WHERE 1=1";

    $params = [];
    $types = '';

    if ($type_filtro === 'manutencao') {
        $sql .= " AND m.tipo_manutencao = 'corretiva'";
    } elseif ($type_filtro === 'instalacao') {
        $sql .= " AND m.tipo_manutencao = 'instalação'";
    }

    if ($status_filtro !== 'todos') {
        $sql .= " AND m.status_reparo = ?";
        $params[] = $status_filtro;
        $types .= 's';
    }

    if (!empty($data_inicio)) {
        $sql .= " AND DATE(m.inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }

    if (!empty($data_fim)) {
        $sql .= " AND DATE(m.inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    if (!empty($search_term)) {
        $search_like = "%" . $search_term . "%";
        $sql .= " AND (e.nome_equip LIKE ? OR e.referencia_equip LIKE ? OR c.nome LIKE ? OR m.status_reparo LIKE ? OR m.ocorrencia_reparo LIKE ? OR u_tec.nome LIKE ? OR u.nome LIKE ?)"; // Adicionado u.nome na busca
        for ($i = 0; $i < 7; $i++) { 
            $params[] = $search_like;
            $types .= 's';
        }
    }

    $sql .= " GROUP BY m.id_manutencao ORDER BY m.inicio_reparo DESC, c.nome";


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

        echo json_encode([
            'success' => true,
            'checksum' => $totalChecksum,
            'data' => $response_data
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'checksum' => $totalChecksum,
            'message' => 'Nenhuma ocorrência encontrada para os filtros selecionados.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>