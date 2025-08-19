<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
// **ALTERADO: Query com JOIN para buscar o nome da cidade**
$sql = "SELECT p.id_provedor, p.nome_prov, p.id_cidade, c.nome AS cidade_prov 
        FROM provedor p
        LEFT JOIN cidades c ON p.id_cidade = c.id_cidade";

if (!empty($search)) {
    $search_param = "%" . $search . "%";
    // **ALTERADO: Busca por nome do provedor ou nome da cidade**
    $sql .= " WHERE p.nome_prov LIKE ? OR c.nome LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_param, $search_param);
} else {
    $stmt = $conn->prepare($sql);
}

try {
    $stmt->execute();
    $result = $stmt->get_result();
    $providers = [];
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }

    echo json_encode(['success' => true, 'providers' => $providers]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar provedores: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>