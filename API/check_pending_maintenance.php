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
        "SELECT id_manutencao, ocorrencia_reparo, tipo_manutencao 
         FROM manutencoes 
         WHERE id_equipamento = ? AND status_reparo = 'pendente'"
    );
    $stmt->bind_param("i", $id_equipamento);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $all_maintenances = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'found' => true,
            'maintenances' => $all_maintenances
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