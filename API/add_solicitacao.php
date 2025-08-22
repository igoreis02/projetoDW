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

$id_usuario_logado = $_SESSION['user_id'];

if (!isset($data['id_cidade']) || !isset($data['solicitante']) || !isset($data['desc_solicitacao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para adicionar a solicitação.']);
    exit();
}

try {
    // A query agora inclui o status 'concluido' e a data de conclusão
    $stmt = $conn->prepare("INSERT INTO solicitacao_cliente (id_usuario, id_cidade, solicitante, desc_solicitacao, desdobramento_soli, status_solicitacao, data_conclusao) VALUES (?, ?, ?, ?, ?, 'concluido', NOW())");
    
    // O bind_param foi ajustado para os campos corretos
    $stmt->bind_param("iisss", $id_usuario_logado, $data['id_cidade'], $data['solicitante'], $data['desc_solicitacao'], $data['desdobramento_soli']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitação adicionada e concluída com sucesso!']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar solicitação: ' . $e->getMessage()]);
}

$conn->close();
?>