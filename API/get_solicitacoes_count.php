<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$response = ['success' => false, 'total_count' => 0];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit();
}

try {
    $result = $conn->query("SELECT COUNT(*) as total_count FROM solicitacao_cliente");
    if ($result) {
        $row = $result->fetch_assoc();
        $response['success'] = true;
        $response['total_count'] = (int)$row['total_count'];
    } else {
        throw new Exception("Erro ao contar solicitações.");
    }
} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
