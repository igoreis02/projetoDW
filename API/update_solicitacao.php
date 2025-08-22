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

$action = $data['action'] ?? 'editar'; // Padrão é 'editar' para manter compatibilidade

if ($action === 'concluir') {
    if (!isset($data['id_solicitacao']) || !isset($data['desdobramento_soli'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos para concluir a solicitação.']);
        exit();
    }
    try {
        $stmt = $conn->prepare("UPDATE solicitacao_cliente SET status_solicitacao = 'concluido', desdobramento_soli = ?, data_conclusao = NOW() WHERE id_solicitacao = ?");
        $stmt->bind_param("si", $data['desdobramento_soli'], $data['id_solicitacao']);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Solicitação concluída com sucesso!']);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao concluir solicitação: ' . $e->getMessage()]);
    }

} elseif ($action === 'editar') {
    if (!isset($data['id_solicitacao']) || !isset($data['id_cidade']) || !isset($data['solicitante']) || !isset($data['status_solicitacao'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos para a atualização.']);
        exit();
    }
    try {
        // Verifica se o status atual é 'concluido' para evitar alteração
        $check_stmt = $conn->prepare("SELECT status_solicitacao FROM solicitacao_cliente WHERE id_solicitacao = ?");
        $check_stmt->bind_param("i", $data['id_solicitacao']);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $current_status = $result->fetch_assoc()['status_solicitacao'];
        $check_stmt->close();

        if ($current_status === 'concluido') {
             echo json_encode(['success' => false, 'message' => 'Não é possível editar uma solicitação já concluída.']);
             exit();
        }

        $sql = "UPDATE solicitacao_cliente SET id_cidade = ?, solicitante = ?, tipo_solicitacao = ?, desc_solicitacao = ?, desdobramento_soli = ?, status_solicitacao = ? WHERE id_solicitacao = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssi", $data['id_cidade'], $data['solicitante'], $data['tipo_solicitacao'], $data['desc_solicitacao'], $data['desdobramento_soli'], $data['status_solicitacao'], $data['id_solicitacao']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Solicitação atualizada com sucesso!']);
        } else {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar solicitação: ' . $e->getMessage()]);
    }
}

$conn->close();
?>