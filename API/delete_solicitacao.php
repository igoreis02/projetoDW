<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id_solicitacao'])) {
    echo json_encode(['success' => false, 'message' => 'ID da solicitação não fornecido.']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM solicitacao_cliente WHERE id_solicitacao = ?");
    $stmt->bind_param("i", $data['id_solicitacao']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitação excluída com sucesso!']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir solicitação: ' . $e->getMessage()]);
}

$conn->close();
?>