<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os parâmetros da requisição
$city_id = $_GET['city_id'] ?? ''; 
$search_term = $_GET['search_term'] ?? '';

$equipamentos = [];
// **ALTERADO: Adicionado id_provedor à consulta**
$sql = "SELECT id_equipamento, nome_equip, referencia_equip, id_provedor FROM equipamentos WHERE status = 'ativo'";
$params = [];
$types = "";

if (!empty($city_id)) {
    $sql .= " AND id_cidade = ?";
    $params[] = (int)$city_id;
    $types .= "i";
}

if (!empty($search_term)) { // Corrigido para verificar se o search_term não está vazio
    $sql .= " AND (nome_equip LIKE ? OR referencia_equip LIKE ?)";
    $searchTermParam = "%" . $search_term . "%";
    $params[] = $searchTermParam;
    $params[] = $searchTermParam;
    $types .= "ss";
}

$sql .= " ORDER BY nome_equip ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $equipamentos[] = $row;
    }
    echo json_encode(['success' => true, 'equipamentos' => $equipamentos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhum equipamento encontrado.']);
}

$stmt->close();
$conn->close();
?>