<?php

header('Content-Type: application/json');
require_once 'conexao_bd.php'; // Ajuste o caminho conforme sua estrutura

try {
    // Seleciona todos os dados necessários das ocorrências com status 'em andamento'
    $sql = "SELECT 
                id_manutencao, 
                'semaforica' as tipo_manutencao,
                descricao as ocorrencia_reparo,
                data_ocorrencia as inicio_reparo,
                status as status_reparo,
                endereco as local_completo,
                referencia as nome_equip,
                tipo_servico as tipo,
                quantidade as qtd,
                unidade,
                observacao
            FROM ocorrencia_semaforica 
            WHERE status = 'em andamento'
            ORDER BY data_ocorrencia DESC";
            
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    $ocorrencias = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $ocorrencias[] = $row;
        }
    }
    
    // A resposta é formatada para ser compatível com a função de renderização existente
    echo json_encode(['success' => true, 'data' => ['ocorrencias' => ['Semafóricas' => $ocorrencias]]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
     if (isset($conn)) {
        $conn->close();
    }
}
?>