<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

// **ALTERADO: Validação para id_cidade**
if (!isset($input['id_provedor']) || !isset($input['nome_prov']) || !isset($input['id_cidade'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit();
}

$id_provedor = $input['id_provedor'];
$nome_prov = $input['nome_prov'];
$id_cidade = $input['id_cidade'];

try {
    // **ALTERADO: Query e bind_param**
    $stmt = $conn->prepare("UPDATE provedor SET nome_prov = ?, id_cidade = ? WHERE id_provedor = ?");
    $stmt->bind_param("sii", $nome_prov, $id_cidade, $id_provedor);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Provedor atualizado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita ou o provedor não foi encontrado.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar provedor: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>