<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

try {
    // Conta o número total de registros na tabela de equipamentos
    $result = $conn->query("SELECT COUNT(*) as total FROM equipamentos");
    
    if ($result) {
        $row = $result->fetch_assoc();
        // Retorna a contagem total em um formato JSON
        echo json_encode(['success' => true, 'total' => (int)$row['total']]);
    } else {
        // Lança uma exceção se a consulta falhar
        throw new Exception("Erro ao contar os equipamentos no banco de dados.");
    }

} catch (Exception $e) {
    // Em caso de erro, retorna uma resposta de falha
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>