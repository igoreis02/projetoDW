<?php
header('Content-Type: application/json');
require_once '../conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['ids']) || !is_array($input['ids']) || empty($input['ids']) || !isset($input['nivel'])) {
    echo json_encode(['success' => false, 'message' => 'IDs ou nível de prioridade não fornecidos.']);
    exit;
}

$idsManutencao = $input['ids'];
$nivel = (int)$input['nivel'];

// Garante que o nível seja apenas 1, 2 ou 3
if (!in_array($nivel, [1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Nível de prioridade inválido.']);
    exit;
}

// Cria os placeholders (?) para a cláusula IN
$placeholders = implode(',', array_fill(0, count($idsManutencao), '?'));
$types = str_repeat('i', count($idsManutencao)); // 'i' para cada ID

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE manutencoes SET nivel_ocorrencia = ? WHERE id_manutencao IN ($placeholders)");
    if (!$stmt) {
        throw new Exception('Erro ao preparar a query: ' . $conn->error);
    }

    // O primeiro tipo é 'i' para o nível, seguido pelos tipos dos IDs
    $stmt->bind_param("i" . $types, $nivel, ...$idsManutencao);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar a atualização: ' . $stmt->error);
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Nível de prioridade atualizado com sucesso.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>