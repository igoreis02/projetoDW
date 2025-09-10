<?php
header('Content-Type: application/json');
require_once '../conexao_bd.php'; // Ajuste o caminho se necessário

$response_data = [];
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];

try {
    // Consulta atualizada para incluir todos os campos necessários
    $sql = "SELECT 
                os.id AS id_manutencao,
                'semaforica' AS tipo_manutencao,
                os.referencia AS nome_equip,       -- Mapeado para o título do card
                os.endereco AS referencia_equip,   -- Mapeado para o subtítulo do card
                os.descricao_problema AS ocorrencia_reparo,
                os.data AS inicio_reparo,
                os.status AS status_reparo,
                os.endereco AS local_completo,
                c.nome AS cidade,
                os.qtd,                            -- <<< ADICIONADO
                os.unidade,                        -- <<< ADICIONADO
                os.tipo,                           -- <<< ADICIONADO
                os.observacao                      -- <<< ADICIONADO
            FROM ocorrencia_semaforica AS os
            LEFT JOIN cidades AS c ON os.id_cidade = c.id_cidade
            WHERE os.status = 'pendente'
            ORDER BY c.nome, os.data DESC";
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cidade = $row['cidade'] ?? 'Sem Cidade';
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
        echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência semafórica pendente encontrada.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>