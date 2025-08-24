<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$id_equipamento = $_GET['equipment_id'] ?? null;

if (empty($id_equipamento)) {
    echo json_encode(['found' => false, 'message' => 'ID do equipamento não fornecido.']);
    exit();
}

try {
    $stmt = $conn->prepare(
        "SELECT id_manutencao, ocorrencia_reparo FROM manutencoes WHERE id_equipamento = ? AND status_reparo = 'pendente' LIMIT 1"
    );
    $stmt->bind_param("i", $id_equipamento);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $maintenance = $result->fetch_assoc();
        echo json_encode([
            'found' => true,
            'id_manutencao' => $maintenance['id_manutencao'],
            'ocorrencia_existente' => $maintenance['ocorrencia_reparo']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['found' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}

$conn->close();
?>