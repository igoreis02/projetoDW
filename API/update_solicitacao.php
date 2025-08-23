<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id_solicitacao = $data['id_solicitacao'] ?? null;
$action = $data['action'] ?? '';

if (!$id_solicitacao) {
    $response['message'] = 'ID da solicitação não fornecido.';
    echo json_encode($response);
    exit();
}

try {
    if ($action === 'concluir') {
        $desdobramento_soli = $data['desdobramento_soli'] ?? '';
        if (empty($desdobramento_soli)) {
            $response['message'] = 'O desdobramento é obrigatório para concluir a solicitação.';
        } else {
            $sql = "UPDATE solicitacao_cliente SET status_solicitacao = 'concluido', desdobramento_soli = ?, data_conclusao = NOW() WHERE id_solicitacao = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $desdobramento_soli, $id_solicitacao);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Solicitação concluída com sucesso!';
            } else {
                throw new Exception('Erro ao concluir a solicitação.');
            }
            $stmt->close();
        }
    } else { // Ação de editar
        $solicitante = $data['solicitante'] ?? '';
        $tipo_solicitacao = $data['tipo_solicitacao'] ?? '';
        $desc_solicitacao = $data['desc_solicitacao'] ?? '';
        $status_solicitacao = $data['status_solicitacao'] ?? '';

        if (empty($solicitante) || empty($status_solicitacao)) {
            $response['message'] = 'Todos os campos obrigatórios devem ser preenchidos.';
        } else {
            // Pega o status atual da solicitação no banco
            $sql_get_status = "SELECT status_solicitacao FROM solicitacao_cliente WHERE id_solicitacao = ?";
            $stmt_get_status = $conn->prepare($sql_get_status);
            $stmt_get_status->bind_param("i", $id_solicitacao);
            $stmt_get_status->execute();
            $result_status = $stmt_get_status->get_result()->fetch_assoc();
            $stmt_get_status->close();
            $current_status = $result_status['status_solicitacao'];

            if ($current_status !== 'concluido' && $status_solicitacao === 'concluido') {
                 $response['message'] = 'Para marcar como "Concluído", utilize a opção "Concluir" no card da solicitação.';
                 echo json_encode($response);
                 exit();
            }
            
            // Lógica para limpar data de conclusão se reabrir a solicitação
            $sql = "UPDATE solicitacao_cliente SET solicitante=?, tipo_solicitacao=?, desc_solicitacao=?, status_solicitacao=?, data_conclusao = IF(? = 'concluido', data_conclusao, NULL), desdobramento_soli = IF(? = 'concluido', desdobramento_soli, NULL) WHERE id_solicitacao=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $solicitante, $tipo_solicitacao, $desc_solicitacao, $status_solicitacao, $status_solicitacao, $status_solicitacao, $id_solicitacao);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Solicitação atualizada com sucesso!';
            } else {
                throw new Exception('Erro ao atualizar a solicitação.');
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>