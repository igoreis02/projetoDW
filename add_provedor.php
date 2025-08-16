<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['nome_prov']) || !isset($input['cidade_prov'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit();
}

$nome_prov = $input['nome_prov'];
$cidade_prov = $input['cidade_prov'];

try {
    // Verificar se o provedor já existe
    $stmt = $conn->prepare("SELECT COUNT(*) FROM provedor WHERE nome_prov = ?");
    $stmt->bind_param("s", $nome_prov);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    if ($row[0] > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'Provedor com este nome já existe.']);
        exit();
    }
    $stmt->close();

    // Inserir o novo provedor
    $stmt = $conn->prepare("INSERT INTO provedor (nome_prov, cidade_prov) VALUES (?, ?)");
    $stmt->bind_param("ss", $nome_prov, $cidade_prov);
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