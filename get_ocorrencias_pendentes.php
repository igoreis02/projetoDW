<?php
// Define o cabeçalho da resposta como JSON.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// --- Configurações do Banco de Dados ---
require_once 'conexao_bd.php';

// Pega os parâmetros de data da URL
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;

// --- Variáveis para armazenar os dados ---
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // --- Consulta SQL base ---
    $sql = "SELECT
                m.id_manutencao,
                m.tipo_manutencao,
                m.ocorrencia_reparo,
                m.inicio_reparo,
                m.status_reparo,
                m.motivo_devolucao,
                m.observacao_instalacao,
                m.inst_laco, m.dt_laco,
                m.inst_base, m.dt_base,
                m.inst_infra, m.data_infra,
                m.inst_energia, m.dt_energia,
                m.inst_prov, m.data_provedor,
                e.nome_equip,
                e.referencia_equip,
                c.nome AS cidade,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                SUBSTRING_INDEX(u.nome, ' ', 1) AS atribuido_por
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario AS u ON m.id_usuario = u.id_usuario
            WHERE m.status_reparo = 'pendente' and m.id_provedor IS NULL";

    $params = [];
    $types = "";

    // Adiciona o filtro de data de início se fornecido
    if (!empty($data_inicio)) {
        $sql .= " AND DATE(m.inicio_reparo) >= ?";
        $params[] = $data_inicio;
        $types .= 's';
    }

    // Adiciona o filtro de data de fim se fornecido
    if (!empty($data_fim)) {
        $sql .= " AND DATE(m.inicio_reparo) <= ?";
        $params[] = $data_fim;
        $types .= 's';
    }

    $sql .= " GROUP BY m.id_manutencao
              ORDER BY c.nome, m.inicio_reparo DESC";
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
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
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência pendente encontrada para os filtros selecionados.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>