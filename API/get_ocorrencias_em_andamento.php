<?php
// Define o cabeçalho da resposta como JSON.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// --- Configurações do Banco de Dados ---
require_once 'conexao_bd.php';

// --- Variáveis para armazenar os dados ---
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // --- Consulta SQL atualizada para incluir o tipo de equipamento ---
    $sql = "SELECT
                m.id_manutencao,
                m.tipo_manutencao,
                m.ocorrencia_reparo,
                m.inicio_reparo,
                m.status_reparo,
                m.inst_laco, m.dt_laco,
                m.inst_base, m.dt_base,
                m.inst_infra, m.data_infra,
                m.inst_energia, m.dt_energia,
                e.nome_equip,
                e.referencia_equip,
                e.tipo_equip,
                c.nome AS cidade,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                GROUP_CONCAT(DISTINCT u.nome SEPARATOR ', ') AS tecnicos_nomes,
                GROUP_CONCAT(DISTINCT CONCAT(v.nome, ' (', v.placa, ')') SEPARATOR ', ') AS veiculos_nomes,
                MIN(mt.inicio_reparoTec) AS inicio_periodo_reparo,
                MAX(mt.fim_reparoT) AS fim_periodo_reparo
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN manutencoes_tecnicos AS mt ON m.id_manutencao = mt.id_manutencao
            LEFT JOIN usuario AS u ON mt.id_tecnico = u.id_usuario
            LEFT JOIN veiculos AS v ON mt.id_veiculo = v.id_veiculo
            WHERE m.status_reparo = 'em andamento'
            GROUP BY m.id_manutencao
            ORDER BY c.nome, m.inicio_reparo DESC";

    $result = $conn->query($sql);

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
        sort($cidades_com_ocorrencias);
        
        $response_data['ocorrencias'] = $ocorrencias_por_cidade;
        $response_data['cidades'] = $cidades_com_ocorrencias;
        
        echo json_encode(['success' => true, 'data' => $response_data]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência em andamento encontrada.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>