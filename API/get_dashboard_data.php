<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;

try {
    $dashboard_data = [];

    // --- Montagem dos Filtros de Data ---
    $filtro_manutencao_sql = "";
    $params = [];
    $types = '';
    if (!empty($data_inicio)) {
        $filtro_manutencao_sql .= " AND DATE(inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }
    if (!empty($data_fim)) {
        $filtro_manutencao_sql .= " AND DATE(inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    $filtro_conclusao_sql = "";
    $params_conclusao = [];
    $types_conclusao = '';
    if (!empty($data_inicio)) {
        $filtro_conclusao_sql .= " AND DATE(fim_reparo) >= ?";
        $params_conclusao[] = $data_inicio;
        $types_conclusao .= 's';
    }
    if (!empty($data_fim)) {
        $filtro_conclusao_sql .= " AND DATE(fim_reparo) <= ?";
        $params_conclusao[] = $data_fim;
        $types_conclusao .= 's';
    }

    // --- KPIs ---
    $stmt = $conn->prepare("SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo IN ('pendente', 'em andamento') $filtro_manutencao_sql");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $dashboard_data['kpi_manutencoes_abertas'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo = 'concluido' $filtro_conclusao_sql");
    if (!empty($params_conclusao)) {
        $stmt->bind_param($types_conclusao, ...$params_conclusao);
    }
    $stmt->execute();
    $dashboard_data['kpi_concluidas_mes'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $stmt = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(DAY, inicio_reparo, fim_reparo)) as mttr_dias FROM manutencoes WHERE status_reparo = 'concluido' AND fim_reparo IS NOT NULL AND inicio_reparo IS NOT NULL $filtro_conclusao_sql");
    if (!empty($params_conclusao)) {
        $stmt->bind_param($types_conclusao, ...$params_conclusao);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $mttr_dias = $result['mttr_dias'] ?? 0;
    $rounded_mttr = round($mttr_dias, 1);
    $formatted_number = number_format($rounded_mttr, ($rounded_mttr == (int) $rounded_mttr) ? 0 : 1, ',', '.');
    $unit = ($rounded_mttr == 1) ? ' dia' : ' dias';
    $dashboard_data['kpi_mttr'] = $formatted_number . $unit;

    if (!empty($data_inicio) && !empty($data_fim)) {
        $stmt = $conn->prepare("SELECT COUNT(id_equipamento) as total FROM equipamentos WHERE dt_vencimento BETWEEN ? AND ?");
        $stmt->bind_param("ss", $data_inicio, $data_fim);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(id_equipamento) as total FROM equipamentos WHERE dt_vencimento >= CURDATE()");
    }
    $stmt->execute();
    $dashboard_data['kpi_afericoes_vencendo'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // --- GRÁFICOS ---
    $stmt = $conn->prepare("SELECT status_reparo, COUNT(id_manutencao) as total FROM manutencoes WHERE 1=1 $filtro_manutencao_sql GROUP BY status_reparo");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $dashboard_data['manutencoes_por_status'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT tipo_manutencao, COUNT(id_manutencao) as total FROM manutencoes WHERE 1=1 $filtro_manutencao_sql GROUP BY tipo_manutencao");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $dashboard_data['manutencoes_por_tipo'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $conn->prepare("SELECT c.nome, COUNT(m.id_manutencao) as total FROM manutencoes m JOIN cidades c ON m.id_cidade = c.id_cidade WHERE m.status_reparo IN ('pendente', 'em andamento') $filtro_manutencao_sql GROUP BY c.nome ORDER BY total DESC");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $dashboard_data['manutencoes_abertas_cidade'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // LÓGICA REVERTIDA: Evolução
    if (!empty($data_inicio) && !empty($data_fim)) {
        // Se houver filtro, gera a série de dias DENTRO do período
        $sql_evolucao = "WITH RECURSIVE date_range AS (SELECT ? as a_date UNION ALL SELECT a_date + INTERVAL 1 DAY FROM date_range WHERE a_date < ?) SELECT dr.a_date AS dia, (SELECT COUNT(id_manutencao) FROM manutencoes WHERE DATE(inicio_reparo) = dr.a_date) AS abertas, (SELECT COUNT(id_manutencao) FROM manutencoes WHERE DATE(fim_reparo) = dr.a_date AND status_reparo = 'concluido') AS fechadas FROM date_range dr ORDER BY dr.a_date ASC";
        $stmt = $conn->prepare($sql_evolucao);
        $stmt->bind_param("ss", $data_inicio, $data_fim);
    } else {
        // Se NÃO houver filtro, agrupa por MÊS para todo o período (LÓGICA RESTAURADA)
        $sql_evolucao = "
            SELECT 
                CONCAT(periodo, '-01') as dia,
                (SELECT COUNT(id_manutencao) FROM manutencoes WHERE DATE_FORMAT(inicio_reparo, '%Y-%m') = periodo) AS abertas,
                (SELECT COUNT(id_manutencao) FROM manutencoes WHERE DATE_FORMAT(fim_reparo, '%Y-%m') = periodo AND status_reparo = 'concluido') AS fechadas
            FROM (
                SELECT DISTINCT DATE_FORMAT(inicio_reparo, '%Y-%m') as periodo FROM manutencoes WHERE inicio_reparo IS NOT NULL
                UNION
                SELECT DISTINCT DATE_FORMAT(fim_reparo, '%Y-%m') as periodo FROM manutencoes WHERE fim_reparo IS NOT NULL
            ) AS periodos
            WHERE periodo IS NOT NULL
            ORDER BY periodo ASC;
        ";
        $stmt = $conn->prepare($sql_evolucao);
    }
    $stmt->execute();
    $dashboard_data['evolucao_diaria'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'data' => $dashboard_data]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados do dashboard: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>