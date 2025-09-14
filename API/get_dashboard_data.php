<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;

try {

    // ---BLOCO DE CÁLCULO DE CHECKSUM GLOBAL ---
    $tables = ['manutencoes', 'ocorrencia_semaforica', 'ocorrencia_provedor', 'ocorrencia_processamento', 'solicitacao_cliente', 'equipamentos'];
    $totalChecksum = 0;
    foreach ($tables as $table) {
        $result = $conn->query("CHECKSUM TABLE `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalChecksum += (int) $row['Checksum'];
        } else {
            throw new Exception("Erro ao calcular checksum para a tabela: $table");
        }
    }

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

    // 1. Manutenções Abertas (Pendentes + Em Andamento) - APENAS CORRETIVAS
    $stmt_abertas = $conn->prepare("SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo IN ('pendente', 'em andamento') AND tipo_manutencao = 'corretiva' $filtro_manutencao_sql");
    if (!empty($params)) {
        $stmt_abertas->bind_param($types, ...$params);
    }
    $stmt_abertas->execute();
    $dashboard_data['kpi_manutencoes_abertas'] = $stmt_abertas->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_abertas->close();

    // 2. Concluídas (no período ou todo o período) - APENAS CORRETIVAS
    $sql_concluidas = "SELECT COUNT(id_manutencao) as total FROM manutencoes WHERE status_reparo = 'concluido' AND tipo_manutencao = 'corretiva'";
    if (!empty($data_inicio) || !empty($data_fim)) {
        // CORREÇÃO: Agora usa o filtro correto de CONCLUSÃO ($filtro_conclusao_sql)
        $stmt_concluidas = $conn->prepare($sql_concluidas . $filtro_conclusao_sql);
        if (!empty($params_conclusao)) {
            // CORREÇÃO: Usa os parâmetros corretos ($types_conclusao, $params_conclusao)
            $stmt_concluidas->bind_param($types_conclusao, ...$params_conclusao);
        }
    } else {
        // Sem filtro de data, conta todas as concluídas (esta parte já estava correta)
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

    if ($mttr_horas > 0) {
        // Converte o total de horas para dias
        $mttr_dias = $mttr_horas / 24;
        // Arredonda o valor para cima para o próximo dia inteiro
        $rounded_dias = ceil($mttr_dias); 
        // Formata o número para exibição
        $formatted_number = number_format($rounded_dias, 0, ',', '.');
        // Define a unidade correta (singular ou plural)
        $unit = ($rounded_dias == 1) ? ' dia' : ' dias';
        // Salva o resultado final
        $dashboard_data['kpi_mttr'] = $formatted_number . $unit;
    } else {
        // Se não houver tempo de reparo, exibe "N/A"
        $dashboard_data['kpi_mttr'] = 'N/A';
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
    $filtro_proc_sql = "";
    $params_proc = [];
    $types_proc = '';
    if (!empty($data_inicio)) {
        $filtro_proc_sql .= " AND DATE(dt_ocorrencia) >= ?";
        $params_proc[] = $data_inicio;
        $types_proc .= 's';
    }
    if (!empty($data_fim)) {
        $filtro_proc_sql .= " AND DATE(dt_ocorrencia) <= ?";
        $params_proc[] = $data_fim;
        $types_proc .= 's';
    }

    $stmt_proc = $conn->prepare("SELECT status, COUNT(id_ocorrencia_processamento) as total FROM ocorrencia_processamento WHERE 1=1 $filtro_proc_sql GROUP BY status");
    if (!empty($params_proc)) {
        $stmt_proc->bind_param($types_proc, ...$params_proc);
    }
    $stmt_proc->execute();
    $dashboard_data['ocorrencias_processamento'] = $stmt_proc->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_proc->close();

    // Filtro para a data de resolução do processamento (para o MTTR)
    $filtro_proc_concl_sql = "";
    $params_proc_concl = [];
    $types_proc_concl = '';
     if (!empty($data_inicio)) {
        $filtro_proc_concl_sql .= " AND DATE(dt_resolucao) >= ?";
        $params_proc_concl[] = $data_inicio;
        $types_proc_concl .= 's';
    }
    if (!empty($data_fim)) {
        $filtro_proc_concl_sql .= " AND DATE(dt_resolucao) <= ?";
        $params_proc_concl[] = $data_fim;
        $types_proc_concl .= 's';
    }
    
    $stmt_mttr_proc = $conn->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, dt_ocorrencia, dt_resolucao)) as mttr_horas FROM ocorrencia_processamento WHERE status = 'concluido' AND dt_resolucao IS NOT NULL AND dt_ocorrencia IS NOT NULL $filtro_proc_concl_sql");
    if(!empty($params_proc_concl)) {
        $stmt_mttr_proc->bind_param($types_proc_concl, ...$params_proc_concl);
    }
    $stmt_mttr_proc->execute();
    $mttr_result_proc = $stmt_mttr_proc->get_result()->fetch_assoc();
    $dashboard_data['mttr_processamento'] = $mttr_result_proc['mttr_horas'] ?? null;
    $stmt_mttr_proc->close();


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

    echo json_encode([
        'success' => true,
        'checksum' => $totalChecksum,
        'data' => $dashboard_data
    ]);



} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados do dashboard: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>