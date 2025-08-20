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

if (!isset($data['id_veiculo'])) {
    echo json_encode(['success' => false, 'message' => 'ID do veículo não fornecido.']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM veiculos WHERE id_veiculo = ?");
    $stmt->bind_param("i", $data['id_veiculo']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Veículo excluído com sucesso!']);
    } else {
        throw new Exception('Erro ao executar a exclusão.');
    }
    $stmt->close();

} catch (Exception $e) {
     // Código 1451 é erro de restrição de chave estrangeira
    if ($conn->errno == 1451) {
        echo json_encode(['success' => false, 'message' => 'Não é possível excluir o veículo, pois ele está associado a uma ou mais manutenções.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
}

$conn->close();
?>