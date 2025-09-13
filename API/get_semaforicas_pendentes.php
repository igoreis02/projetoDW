<?php
header('Content-Type: application/json');
require_once '../conexao_bd.php'; // Ajuste o caminho se necessário

$response_data = [];
$ocorrencias_por_cidade = [];
$cidades_com_ocorrencias = [];

try {
    // Verifica as duas tabelas que definem o estado de "pendentes"
    $tables = ['manutencoes', 'ocorrencia_semaforica'];
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

    // Adicionamos um JOIN com a tabela 'manutencoes' para buscar o ID correto.
    $sql = "SELECT 
                m.id_manutencao, -- Buscando o ID REAL da manutenção
                'semaforica' AS tipo_manutencao,
                os.referencia AS nome_equip,
                os.endereco AS referencia_equip,
                os.descricao_problema AS ocorrencia_reparo,
                os.data AS inicio_reparo,
                os.status AS status_reparo,
                os.endereco AS local_completo,
                c.nome AS cidade,
                os.qtd,
                os.unidade,
                os.tipo,
                os.observacao
            FROM ocorrencia_semaforica AS os
            JOIN manutencoes AS m ON os.id = m.id_ocorrencia_semaforica
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
        
         echo json_encode([
        'success' => true,
        'checksum' => $totalChecksum,
        'data' => $response_data
    ]);

    } else {
        echo json_encode([
            'success' => false,
            'checksum' => $totalChecksum,
            'data' => ['ocorrencias' => [], 'cidades' => []],
            'message' => 'Nenhuma ocorrência semafórica pendente encontrada.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar ocorrências semafóricas: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>