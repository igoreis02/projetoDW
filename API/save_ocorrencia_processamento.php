<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$id_usuario_logado = $_SESSION['user_id'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Dados da ocorrência de processamento
$id_manutencao = $data['id_manutencao'] ?? null;
$tipo_ocorrencia = $data['tipo_ocorrencia'] ?? null;
$descricao = $data['descricao'] ?? null;

// Validação
if (empty($id_manutencao) || empty($tipo_ocorrencia) || empty($descricao)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para registrar ocorrência de processamento.']);
    exit();
}

$status = 'pendente'; // Status sempre pendente ao criar

try {
    $sql = "INSERT INTO ocorrencia_processamento 
                (id_manutencao, id_usuario_registro, tipo_ocorrencia, descricao, status) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", $id_manutencao, $id_usuario_logado, $tipo_ocorrencia, $descricao, $status);
    
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Ocorrência de processamento registrada com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar ocorrência de processamento: ' . $e->getMessage()]);
}

$conn->close();
?>