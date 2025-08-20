<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

// **ALTERADO: Validação para id_cidade**
if (!isset($input['nome_prov']) || !isset($input['id_cidade'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos. Nome e cidade são obrigatórios.']);
    exit();
}

$nome_prov = $input['nome_prov'];
$id_cidade = $input['id_cidade'];

try {
    // Inserir o novo provedor
    // **ALTERADO: Query e bind_param**
    $stmt = $conn->prepare("INSERT INTO provedor (nome_prov, id_cidade) VALUES (?, ?)");
    $stmt->bind_param("si", $nome_prov, $id_cidade);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Provedor adicionado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao adicionar provedor.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar provedor: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>