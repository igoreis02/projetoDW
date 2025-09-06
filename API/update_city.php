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
if (!isset($data['id_cidade']) || !isset($data['nome']) || !isset($data['sigla_cidade']) || !isset($data['cod_cidade']) || !isset($data['semaforica']) || !isset($data['radares'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para a atualização.']);
    exit();
}

$conn->begin_transaction();

try {
    // Query atualizada
    $stmt = $conn->prepare("UPDATE cidades SET nome = ?, sigla_cidade = ?, cod_cidade = ?, semaforica = ?, radares = ? WHERE id_cidade = ?");
    // Bind param atualizado
    $stmt->bind_param("sssiii", $data['nome'], $data['sigla_cidade'], $data['cod_cidade'], $data['semaforica'], $data['radares'], $data['id_cidade']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cidade atualizada com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao atualizar cidade: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
}

$conn->close();
?>