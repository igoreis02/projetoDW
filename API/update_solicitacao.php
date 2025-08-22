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

if (!isset($data['id_solicitacao']) || !isset($data['id_usuario']) || !isset($data['id_cidade']) || !isset($data['solicitante']) || !isset($data['desc_solicitacao']) || !isset($data['status_solicitacao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para a atualização.']);
    exit();
}

try {
    $sql = "UPDATE solicitacao_cliente SET id_usuario = ?, id_cidade = ?, solicitante = ?, desc_solicitacao = ?, desdobramento_soli = ?, status_solicitacao = ?";
    $params = [$data['id_usuario'], $data['id_cidade'], $data['solicitante'], $data['desc_solicitacao'], $data['desdobramento_soli'], $data['status_solicitacao']];
    $types = "iissss";
    
    // Se o status for 'concluido', atualiza a data de conclusão
    if ($data['status_solicitacao'] === 'concluido') {
        $sql .= ", data_conclusao = CURRENT_TIMESTAMP()";
    } else {
        $sql .= ", data_conclusao = NULL";
    }
    
    $sql .= " WHERE id_solicitacao = ?";
    $params[] = $data['id_solicitacao'];
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitação atualizada com sucesso!']);
    } else {
        throw new Exception($stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar solicitação: ' . $e->getMessage()]);
}

$conn->close();
?>
