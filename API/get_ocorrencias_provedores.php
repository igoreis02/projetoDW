<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

// --- COLETA E VALIDAÇÃO DOS FILTROS ---
$tipo = isset($_GET['tipo']) ? $conn->real_escape_string($_GET['tipo']) : 'manutencao';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'todos';
$data_inicio = isset($_GET['data_inicio']) ? $conn->real_escape_string($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? $conn->real_escape_string($_GET['data_fim']) : '';

$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // --- CONSTRUÇÃO DA QUERY DINÂMICA ---
    $sql = "SELECT
                m.id_manutencao,
                m.ocorrencia_reparo,
                m.inicio_reparo,
                m.status_reparo,
                m.reparo_finalizado, -- <<< ADICIONADO CAMPO
                e.nome_equip,
                e.referencia_equip,
                c.nome AS cidade,
                p.nome_prov,
                CONCAT(en.logradouro, ', ', en.bairro) AS local_completo,
                SUBSTRING_INDEX(u.nome, ' ', 1) AS atribuido_por
            FROM manutencoes AS m
            JOIN equipamentos AS e ON m.id_equipamento = e.id_equipamento
            JOIN cidades AS c ON m.id_cidade = c.id_cidade
            LEFT JOIN provedor AS p ON m.id_provedor = p.id_provedor
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN usuario AS u ON m.id_usuario = u.id_usuario";

    $where_clauses = ["m.id_provedor IS NOT NULL"];

    // Adiciona filtros à query
    if (!empty($tipo)) {
        if ($tipo === 'instalacao') {
            $where_clauses[] = "m.tipo_manutencao = 'instalacao'";
        } else { // 'manutencao'
             $where_clauses[] = "m.tipo_manutencao IN ('preditiva', 'corretiva')";
        }
    }
    
    // <<< LÓGICA ATUALIZADA PARA O STATUS "TODOS"
    if (!empty($status) && $status !== 'todos') {
        $where_clauses[] = "m.status_reparo = '$status'";
    }

    if (!empty($data_inicio)) {
        $where_clauses[] = "DATE(m.inicio_reparo) >= '$data_inicio'";
    }
    if (!empty($data_fim)) {
        $where_clauses[] = "DATE(m.inicio_reparo) <= '$data_fim'";
    }

    if (count($where_clauses) > 0) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $sql .= " GROUP BY m.id_manutencao ORDER BY FIELD(m.status_reparo, 'pendente', 'concluido', 'cancelado'), c.nome, m.inicio_reparo DESC";

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
        
        $response_data['ocorrencias'] = $ocorrencias_por_cidade;
        $response_data['cidades'] = $cidades_com_ocorrencias;
        
        echo json_encode(['success' => true, 'data' => $response_data]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência de provedor encontrada para os filtros selecionados.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>