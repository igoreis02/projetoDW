<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$id_equipamento = $_GET['id_equipamento'] ?? null;

if (empty($id_equipamento)) {
    echo json_encode(['success' => false, 'message' => 'ID do equipamento é obrigatório.']);
    exit();
}

$response = ['success' => false, 'lacres' => []];

try {
    // Busca apenas os lacres que estão atualmente afixados no equipamento
    $sql = "SELECT 
                local_lacre, 
                num_lacre 
            FROM controle_lacres 
            WHERE id_equipamento = ? AND lacre_afixado = 1 AND num_lacre IS NOT NULL
            ORDER BY local_lacre ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_equipamento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['lacres'][] = $row;
    }
    
    $stmt->close();
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>