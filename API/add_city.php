<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação atualizada
if (!isset($data['nome']) || !isset($data['sigla_cidade']) || !isset($data['cod_cidade']) || !isset($data['semaforica']) || !isset($data['radares'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para adicionar a cidade.']);
    exit();
}

$conn->begin_transaction();

try {
    // Query atualizada
    $stmt = $conn->prepare("INSERT INTO cidades (nome, sigla_cidade, cod_cidade, semaforica, radares) VALUES (?, ?, ?, ?, ?)");
    // Bind param atualizado
    $stmt->bind_param("sssii", $data['nome'], $data['sigla_cidade'], $data['cod_cidade'], $data['semaforica'], $data['radares']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cidade adicionada com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao adicionar cidade: " . $e->getMessage());
    
    if ($e->getCode() == 1062) {
        echo json_encode(['success' => false, 'message' => 'Já existe uma cidade com este nome, sigla ou código.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados ao adicionar a cidade.']);
    }
}

$conn->close();
?>