<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];
$response_data = [];

try {
    // Consulta para buscar manutenções preditivas e pendentes, associadas a um provedor
    $sql = "SELECT
                m.id_manutencao,
                m.ocorrencia_reparo,
                m.inicio_reparo,
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
            LEFT JOIN usuario AS u ON m.id_usuario = u.id_usuario
            WHERE m.status_reparo = 'pendente' AND m.tipo_manutencao = 'preditiva' AND m.id_provedor IS NOT NULL
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
        
        $response_data['ocorrencias'] = $ocorrencias_por_cidade;
        $response_data['cidades'] = $cidades_com_ocorrencias;
        
        echo json_encode(['success' => true, 'data' => $response_data]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência de provedor pendente encontrada.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>