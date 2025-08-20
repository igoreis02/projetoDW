<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

$city_id = $_GET['city_id'] ?? '';
$search_term = $_GET['search_term'] ?? '';

$equipamentos = [];
// **ALTERADO: Query com JOIN para buscar o nome do provedor**
$sql = "SELECT 
            e.id_equipamento, 
            e.nome_equip, 
            e.referencia_equip, 
            e.id_provedor,
            p.nome_prov 
        FROM equipamentos e
        LEFT JOIN provedor p ON e.id_provedor = p.id_provedor
        WHERE e.status = 'ativo'";
$params = [];
$types = "";

if (!empty($city_id)) {
    $sql .= " AND e.id_cidade = ?";
    $params[] = (int)$city_id;
    $types .= "i";
}

if (!empty($search_term)) {
    $sql .= " AND (e.nome_equip LIKE ? OR e.referencia_equip LIKE ?)";
    $searchTermParam = "%" . $search_term . "%";
    $params[] = $searchTermParam;
    $params[] = $searchTermParam;
    $types .= "ss";
}

$sql .= " ORDER BY e.nome_equip ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $equipamentos[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'equipamentos' => $equipamentos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta.']);
}

$conn->close();
?>