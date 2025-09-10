<?php
header('Content-Type: application/json');
require_once '../conexao_bd.php'; // Ajuste o caminho se necessário

try {
    $sql = "SELECT 1 FROM ocorrencia_semaforica WHERE status = 'pendente' LIMIT 1";
    $result = $conn->query($sql);

    // Se a consulta retornar pelo menos uma linha, existem pendentes.
    echo json_encode(['success' => true, 'data' => ['has_pending' => $result->num_rows > 0]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>