<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT id_provedor, nome_prov, cidade_prov FROM provedor";

if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $sql .= " WHERE nome_prov LIKE ? OR cidade_prov LIKE ?";
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