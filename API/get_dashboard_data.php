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

    // Filtro para data de CONCLUSÃO (KPIs como 'Concluídas' e MTTR)
    // Esta lógica garante que o filtro seja aplicado corretamente.
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
    // --- KPIs ---
// Adicionamos a cláusula "AND tipo_manutencao = 'corretiva'" em todas as consultas de KPIs de manutenção.

    // 1. Manutenções Abertas (Pendentes + Em Andamento) - APENAS CORRETIVAS
    $stmt_abertas = $conn->prepare("SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo IN ('pendente', 'em andamento') AND tipo_manutencao = 'corretiva' $filtro_manutencao_sql");
    if (!empty($params)) {
        $stmt_abertas->bind_param($types, ...$params);
    }
    $stmt_abertas->execute();
    $dashboard_data['kpi_manutencoes_abertas'] = $stmt_abertas->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_abertas->close();

    // 2. Concluídas (no período ou todo o período) - APENAS CORRETIVAS
    $sql_concluidas = "SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo = 'concluido' AND tipo_manutencao = 'corretiva' $filtro_conclusao_sql";
    if (!empty($params_conclusao)) {
        $stmt_concluidas = $conn->prepare($sql_concluidas);
        $stmt_concluidas->bind_param($types_conclusao, ...$params_conclusao);
    } else {
        $stmt_concluidas = $conn->prepare($sql_concluidas);
    }
    $stmt_concluidas->execute();
    $dashboard_data['kpi_concluidas_mes'] = $stmt_concluidas->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_concluidas->close();

    // 3. Tempo Médio de Reparo (MTTR) - CORREÇÃO APLICADA AQUI
// A consulta foi alterada para calcular em HORAS e a formatação agora imita a do JavaScript para garantir consistência.
    $stmt_mttr = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, inicio_reparo, fim_reparo)) as mttr_horas FROM manutencoes WHERE status_reparo = 'concluido' AND inicio_reparo IS NOT NULL AND fim_reparo IS NOT NULL and tipo_manutencao = 'corretiva' $filtro_conclusao_sql");
    if (!empty($params_conclusao)) {
        $stmt_mttr->bind_param($types_conclusao, ...$params_conclusao);
    }
    $stmt_mttr->execute();
    $result_mttr = $stmt_mttr->get_result()->fetch_assoc();
    $mttr_horas = $result_mttr['mttr_horas'] ?? 0;

    if ($mttr_horas > 0 && $mttr_horas < 24) {
        $rounded_mttr = round($mttr_horas, 1);
        $formatted_number = number_format($rounded_mttr, 1, ',', '.');
        $unit = ($rounded_mttr == 1) ? ' hora' : ' horas';
        $dashboard_data['kpi_mttr'] = $formatted_number . $unit;
    } else {
        $mttr_dias = $mttr_horas / 24;
        $rounded_mttr = round($mttr_dias, 1);
        $formatted_number = number_format($rounded_mttr, 1, ',', '.');
        $unit = ($rounded_mttr == 1) ? ' dia' : ' dias';
        $dashboard_data['kpi_mttr'] = ($mttr_horas == 0) ? 'N/A' : $formatted_number . $unit;
    }
    $stmt_mttr->close();


    // 4. Aferições a Vencer (não é afetado pela mudança, continua como está)
    if (!empty($data_inicio) && !empty($data_fim)) {
        $stmt_afericoes = $conn->prepare("SELECT COUNT(id_equipamento) as total FROM equipamentos WHERE dt_vencimento BETWEEN ? AND ?");
        $stmt_afericoes->bind_param("ss", $data_inicio, $data_fim);
    } else {
        $stmt_afericoes = $conn->prepare("SELECT COUNT(id_equipamento) as total FROM equipamentos WHERE dt_vencimento >= CURDATE()");
    }
    $stmt_afericoes->execute();
    $dashboard_data['kpi_afericoes_vencendo'] = $stmt_afericoes->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_afericoes->close();

    // --- GRÁFICOS ---
    // 1. Ocorrências Técnicas (Corretivas por Status) + MTTR
    $stmt_tecnicas = $conn->prepare("SELECT status_reparo, COUNT(id_manutencao) as total FROM manutencoes WHERE tipo_manutencao = 'corretiva' $filtro_manutencao_sql GROUP BY status_reparo");
    if (!empty($params)) {
        $stmt_tecnicas->bind_param($types, ...$params);
    }
    $stmt_tecnicas->execute();
    $dashboard_data['ocorrencias_tecnicas'] = $stmt_tecnicas->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_tecnicas->close();

    $stmt_mttr_tecnicas = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, inicio_reparo, fim_reparo)) as mttr_horas FROM manutencoes WHERE tipo_manutencao = 'corretiva' AND status_reparo = 'concluido' AND fim_reparo IS NOT NULL AND inicio_reparo IS NOT NULL $filtro_conclusao_sql");
    if (!empty($params_conclusao)) {
        $stmt_mttr_tecnicas->bind_param($types_conclusao, ...$params_conclusao);
    }
    $stmt_mttr_tecnicas->execute();
    $mttr_result_tecnicas = $stmt_mttr_tecnicas->get_result()->fetch_assoc();
    $dashboard_data['mttr_tecnicas'] = $mttr_result_tecnicas['mttr_horas'] ?? null;
    $stmt_mttr_tecnicas->close();

    // 2. Ocorrências de Provedores por Status + MTTR
    $sql_provedores = "SELECT status, COUNT(id_ocorrencia_provedor) as total FROM ocorrencia_provedor WHERE 1=1";
    if (!empty($data_inicio))
        $sql_provedores .= " AND DATE(dt_inicio_reparo) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_provedores .= " AND DATE(dt_inicio_reparo) <= '$data_fim'";
    $sql_provedores .= " GROUP BY status";
    $result_prov = $conn->query($sql_provedores);
    $dashboard_data['ocorrencias_provedores'] = $result_prov->fetch_all(MYSQLI_ASSOC);

    $sql_mttr_prov = "SELECT AVG(TIMESTAMPDIFF(HOUR, dt_inicio_reparo, dt_fim_reparo)) as mttr_horas FROM ocorrencia_provedor WHERE status = 'concluido' AND dt_fim_reparo IS NOT NULL AND dt_inicio_reparo IS NOT NULL";
    if (!empty($data_inicio))
        $sql_mttr_prov .= " AND DATE(dt_fim_reparo) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_mttr_prov .= " AND DATE(dt_fim_reparo) <= '$data_fim'";
    $result_mttr_prov = $conn->query($sql_mttr_prov);
    $mttr_result_prov = $result_mttr_prov->fetch_assoc();
    $dashboard_data['mttr_provedores'] = $mttr_result_prov['mttr_horas'] ?? null;

    // 3. Ocorrências de Processamento por Status + MTTR
    $sql_processamento = "SELECT status, COUNT(id_ocorrencia_processamento) as total FROM ocorrencia_processamento WHERE 1=1";
    if (!empty($data_inicio))
        $sql_processamento .= " AND DATE(dt_ocorrencia) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_processamento .= " AND DATE(dt_ocorrencia) <= '$data_fim'";
    $sql_processamento .= " GROUP BY status";
    $result_proc = $conn->query($sql_processamento);
    $dashboard_data['ocorrencias_processamento'] = $result_proc->fetch_all(MYSQLI_ASSOC);

    $sql_mttr_proc = "SELECT AVG(TIMESTAMPDIFF(HOUR, dt_ocorrencia, dt_resolucao)) as mttr_horas FROM ocorrencia_processamento WHERE status = 'concluido' AND dt_resolucao IS NOT NULL AND dt_ocorrencia IS NOT NULL";
    if (!empty($data_inicio))
        $sql_mttr_proc .= " AND DATE(dt_resolucao) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_mttr_proc .= " AND DATE(dt_resolucao) <= '$data_fim'";
    $result_mttr_proc = $conn->query($sql_mttr_proc);
    $mttr_result_proc = $result_mttr_proc->fetch_assoc();
    $dashboard_data['mttr_processamento'] = $mttr_result_proc['mttr_horas'] ?? null;

    // 4. Solicitações de Clientes por Status + MTTR
    $sql_clientes = "SELECT status_solicitacao as status, COUNT(id_solicitacao) as total FROM solicitacao_cliente WHERE 1=1";
    if (!empty($data_inicio))
        $sql_clientes .= " AND DATE(data_solicitacao) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_clientes .= " AND DATE(data_solicitacao) <= '$data_fim'";
    $sql_clientes .= " GROUP BY status_solicitacao";
    $result_cli = $conn->query($sql_clientes);
    $dashboard_data['solicitacoes_clientes'] = $result_cli->fetch_all(MYSQLI_ASSOC);

    $sql_mttr_cli = "SELECT AVG(TIMESTAMPDIFF(HOUR, data_solicitacao, data_conclusao)) as mttr_horas FROM solicitacao_cliente WHERE status_solicitacao = 'concluido' AND data_conclusao IS NOT NULL AND data_solicitacao IS NOT NULL";
    if (!empty($data_inicio))
        $sql_mttr_cli .= " AND DATE(data_conclusao) >= '$data_inicio'";
    if (!empty($data_fim))
        $sql_mttr_cli .= " AND DATE(data_conclusao) <= '$data_fim'";
    $result_mttr_cli = $conn->query($sql_mttr_cli);
    $mttr_result_cli = $result_mttr_cli->fetch_assoc();
    $dashboard_data['mttr_clientes'] = $mttr_result_cli['mttr_horas'] ?? null;

    // Gráfico de Barras - Manutenções Abertas por Cidade
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