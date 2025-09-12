<?php
// /API/check_validacoes_pendentes.php

header('Content-Type: application/json');
require_once 'conexao_bd.php'; // Ajuste o caminho conforme sua estrutura

try {
    // Consulta para verificar se existe alguma manutenção com o status 'validacao'
    $sql = "SELECT 1 FROM manutencoes WHERE status_reparo = 'validacao' LIMIT 1";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    // Retorna um JSON simples indicando se encontrou (true) ou não (false)
    echo json_encode(['success' => true, 'data' => ['has_pending_validation' => $result->num_rows > 0]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>