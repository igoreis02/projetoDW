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

if (!isset($data['id_cidade'])) {
    echo json_encode(['success' => false, 'message' => 'ID da cidade não fornecido.']);
    exit();
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("DELETE FROM cidades WHERE id_cidade = ?");
    $stmt->bind_param("i", $data['id_cidade']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cidade excluída com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao excluir cidade: " . $e->getMessage());
    
    // Verifica se o erro é devido a uma restrição de chave estrangeira (código 1451)
    if ($e->getCode() == 1451) {
        echo json_encode(['success' => false, 'message' => 'Não é possível excluir a cidade, pois ela possui equipamentos associados.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
    }
}

$conn->close();
?>