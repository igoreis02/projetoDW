<?php
// /API/get_ocorrencias_em_andamento.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

try {

    // --- BLOCO DE CÁLCULO DE CHECKSUM ---
    $tables = ['manutencoes', 'ocorrencia_semaforica'];
    $totalChecksum = 0;
    foreach ($tables as $table) {
        $result = $conn->query("CHECKSUM TABLE `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            // Somamos os checksums para criar uma assinatura única do estado dos dados.
            $totalChecksum += (int)$row['Checksum'];
        } else {
            // Lançar uma exceção em caso de falha no cálculo
            throw new Exception("Erro ao calcular checksum para a tabela: $table");
        }
    }
    $where_clauses = ["m.status_reparo = 'em andamento'"];
    $params = [];
    $types = '';

    if (!empty($_GET['data_inicio'])) {
        $where_clauses[] = "m.inicio_reparo >= ?";
        $params[] = $_GET['data_inicio'] . ' 00:00:00';
        $types .= 's';
    }

    if (!empty($_GET['data_fim'])) {
        $where_clauses[] = "m.inicio_reparo <= ?";
        $params[] = $_GET['data_fim'] . ' 23:59:59';
        $types .= 's';
    }

    $where_sql = implode(' AND ', $where_clauses);

    // Consulta SQL com a correção para buscar as datas de execução corretas
    $sql = "
    -- PARTE 1: Manutenções de Equipamentos
    (SELECT
        m.id_manutencao,
        m.tipo_manutencao COLLATE utf8mb4_general_ci as tipo_manutencao,
        m.ocorrencia_reparo COLLATE utf8mb4_general_ci as ocorrencia_reparo,
        m.inicio_reparo,
        m.status_reparo COLLATE utf8mb4_general_ci as status_reparo,
        e.nome_equip COLLATE utf8mb4_general_ci as nome_equip,
        e.referencia_equip COLLATE utf8mb4_general_ci as referencia_equip,
        e.tipo_equip COLLATE utf8mb4_general_ci as tipo_equip,
        c.nome COLLATE utf8mb4_general_ci AS cidade,
        CONCAT(en.logradouro, ', ', en.bairro) COLLATE utf8mb4_general_ci AS local_completo,
        GROUP_CONCAT(DISTINCT u.nome SEPARATOR ', ') COLLATE utf8mb4_general_ci AS tecnicos_nomes,
        GROUP_CONCAT(DISTINCT CONCAT(v.nome, ' (', v.placa, ')') SEPARATOR ', ') COLLATE utf8mb4_general_ci AS veiculos_nomes,
        -- CORREÇÃO APLICADA AQUI: Busca a data de início e fim da execução
        mt_dates.inicio_reparoTec AS inicio_periodo_reparo,
        mt_dates.fim_reparoT AS fim_periodo_reparo,
        m.inst_laco, m.dt_laco,
        m.inst_base, m.dt_base,
        m.inst_infra, m.data_infra,
        m.inst_energia, m.dt_energia
    FROM manutencoes AS m
    JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
    JOIN cidades AS c ON m.id_cidade = c.id_cidade
    LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
    LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
    -- Subconsulta para pegar a data correta sem duplicar linhas no GROUP BY principal
    LEFT JOIN (
        SELECT id_manutencao, MIN(inicio_reparoTec) as inicio_reparoTec, MAX(fim_reparoT) as fim_reparoT 
        FROM manutencoes_tecnicos GROUP BY id_manutencao
    ) as mt_dates ON m.id_manutencao = mt_dates.id_manutencao
    LEFT JOIN usuario AS u ON mt.id_tecnico = u.id_usuario
    LEFT JOIN veiculos AS v ON mt.id_veiculo = v.id_veiculo
    WHERE $where_sql AND m.id_ocorrencia_semaforica IS NULL
    GROUP BY m.id_manutencao)

    UNION ALL

    -- PARTE 2: Manutenções de Ocorrências Semafóricas
    (SELECT
        m.id_manutencao,
        'semaforica' COLLATE utf8mb4_general_ci as tipo_manutencao,
        os.descricao_problema COLLATE utf8mb4_general_ci as ocorrencia_reparo,
        m.inicio_reparo,
        m.status_reparo COLLATE utf8mb4_general_ci as status_reparo,
        os.referencia COLLATE utf8mb4_general_ci as nome_equip,
        'Ocorrência Semafórica' COLLATE utf8mb4_general_ci as referencia_equip,
        os.tipo COLLATE utf8mb4_general_ci as tipo_equip,
        c.nome COLLATE utf8mb4_general_ci AS cidade,
        os.endereco COLLATE utf8mb4_general_ci AS local_completo,
        GROUP_CONCAT(DISTINCT u.nome SEPARATOR ', ') COLLATE utf8mb4_general_ci AS tecnicos_nomes,
        GROUP_CONCAT(DISTINCT CONCAT(v.nome, ' (', v.placa, ')') SEPARATOR ', ') COLLATE utf8mb4_general_ci AS veiculos_nomes,
        -- CORREÇÃO APLICADA AQUI TAMBÉM
        mt_dates.inicio_reparoTec AS inicio_periodo_reparo,
        mt_dates.fim_reparoT AS fim_periodo_reparo,
        NULL as inst_laco, NULL as dt_laco,
        NULL as inst_base, NULL as dt_base,
        NULL as inst_infra, NULL as data_infra,
        NULL as inst_energia, NULL as dt_energia
    FROM manutencoes AS m
    JOIN ocorrencia_semaforica AS os ON m.id_ocorrencia_semaforica = os.id
    JOIN cidades AS c ON os.id_cidade = c.id_cidade
    LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
    LEFT JOIN (
        SELECT id_manutencao, MIN(inicio_reparoTec) as inicio_reparoTec, MAX(fim_reparoT) as fim_reparoT 
        FROM manutencoes_tecnicos GROUP BY id_manutencao
    ) as mt_dates ON m.id_manutencao = mt_dates.id_manutencao
    LEFT JOIN usuario AS u ON mt.id_tecnico = u.id_usuario
    LEFT JOIN veiculos AS v ON mt.id_veiculo = v.id_veiculo
    WHERE $where_sql AND m.id_ocorrencia_semaforica IS NOT NULL
    GROUP BY m.id_manutencao)

    ORDER BY cidade, inicio_reparo DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $ocorrencias_por_cidade = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cidade = $row['cidade'];
            if (!isset($ocorrencias_por_cidade[$cidade])) {
                $ocorrencias_por_cidade[$cidade] = [];
            }
            $ocorrencias_por_cidade[$cidade][] = $row;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'checksum' => $totalChecksum, 
        'data' => ['ocorrencias' => $ocorrencias_por_cidade]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    if(isset($conn)) {
        $conn->close();
    }
}
?>