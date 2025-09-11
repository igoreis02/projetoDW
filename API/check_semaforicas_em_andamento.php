<?php
// /API/check_semaforicas_em_andamento.php

header('Content-Type: application/json');
require_once 'conexao_bd.php'; // Ajuste o caminho conforme sua estrutura

try {
    // A consulta está correta: verifica se existe alguma manutenção 'em andamento'
    // que esteja vinculada a uma ocorrência semafórica.
    $sql = "SELECT 1 
            FROM manutencoes 
            WHERE status_reparo = 'em andamento' 
            AND id_ocorrencia_semaforica IS NOT NULL 
            LIMIT 1";
            
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    // A resposta deve usar 'has_pending' para ser consistente com o JS
    echo json_encode(['success' => true, 'data' => ['has_pending' => $result->num_rows > 0]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>