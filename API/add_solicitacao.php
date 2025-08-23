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

$id_usuario = $_SESSION['user_id'];
$id_cidade = $data['id_cidade'] ?? null;
$solicitante = $data['solicitante'] ?? '';
$tipo_solicitacao = $data['tipo_solicitacao'] ?? '';
$desc_solicitacao = $data['desc_solicitacao'] ?? '';
$status_solicitacao = $data['status_solicitacao'] ?? 'pendente';
$desdobramento_soli = $data['desdobramento_soli'] ?? null;

if (empty($id_cidade) || empty($solicitante) || empty($tipo_solicitacao) || empty($desc_solicitacao)) {
    $response['message'] = 'Todos os campos obrigatórios devem ser preenchidos.';
    echo json_encode($response);
    exit();
}

if ($status_solicitacao === 'concluido' && empty($desdobramento_soli)) {
    $response['message'] = 'O desdobramento é obrigatório para solicitações concluídas.';
    echo json_encode($response);
    exit();
}

try {
    if ($status_solicitacao === 'concluido') {
        $sql = "INSERT INTO solicitacao_cliente (id_usuario, id_cidade, solicitante, tipo_solicitacao, desc_solicitacao, status_solicitacao, desdobramento_soli, data_conclusao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssss", $id_usuario, $id_cidade, $solicitante, $tipo_solicitacao, $desc_solicitacao, $status_solicitacao, $desdobramento_soli);
    } else {
        $sql = "INSERT INTO solicitacao_cliente (id_usuario, id_cidade, solicitante, tipo_solicitacao, desc_solicitacao, status_solicitacao) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", $id_usuario, $id_cidade, $solicitante, $tipo_solicitacao, $desc_solicitacao, $status_solicitacao);
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Solicitação adicionada com sucesso!';
    } else {
        throw new Exception('Erro ao inserir no banco de dados.');
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>