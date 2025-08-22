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

if (!isset($data['id_cidade']) || !isset($data['solicitante']) || !isset($data['tipo_solicitacao']) || !isset($data['desc_solicitacao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para adicionar a solicitação.']);
    exit();
}

try {
    // A query agora insere o tipo_solicitacao e o status_solicitacao padrão é 'pendente' (definido no DB)
    $stmt = $conn->prepare("INSERT INTO solicitacao_cliente (id_usuario, id_cidade, solicitante, tipo_solicitacao, desc_solicitacao, desdobramento_soli) VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iissss", $id_usuario_logado, $data['id_cidade'], $data['solicitante'], $data['tipo_solicitacao'], $data['desc_solicitacao'], $data['desdobramento_soli']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitação adicionada com sucesso!']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar solicitação: ' . $e->getMessage()]);
}

$conn->close();
?>