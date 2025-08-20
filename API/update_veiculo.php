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

if (!isset($data['id_veiculo']) || !isset($data['nome']) || !isset($data['placa']) || !isset($data['modelo'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para a atualização.']);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE veiculos SET nome = ?, placa = ?, modelo = ? WHERE id_veiculo = ?");
    $stmt->bind_param("sssi", $data['nome'], $data['placa'], $data['modelo'], $data['id_veiculo']);
    
    if ($stmt->execute()) {
         if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Veículo atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Nenhuma alteração foi feita.']);
        }
    } else {
        throw new Exception('Erro ao executar a atualização.');
    }
    $stmt->close();

} catch (Exception $e) {
    // Código 1062 é erro de entrada duplicada (ex: placa já existe se a coluna for UNIQUE)
    if ($conn->errno == 1062) {
         echo json_encode(['success' => false, 'message' => 'Erro: A placa informada já pertence a outro veículo.']);
    } else {
         echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
}

$conn->close();
?>