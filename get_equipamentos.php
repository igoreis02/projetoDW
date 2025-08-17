<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os parâmetros da requisição
$city_id = $_GET['city_id'] ?? ''; // Agora esperamos o ID da cidade
$search_term = $_GET['search_term'] ?? '';

$equipamentos = [];
$sql = "SELECT id_equipamento, nome_equip, referencia_equip FROM equipamentos WHERE status = 'ativo'";
$params = [];
$types = "";

if (!empty($city_id)) {
    $sql .= " AND id_cidade = ?"; // Filtra por id_cidade
    $params[] = (int)$city_id; // Garante que seja um inteiro
    $types .= "i"; // 'i' para inteiro
}

  $sql .= " AND (nome_equip LIKE ? OR referencia_equip LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $types .= "ss"; // 's' para string

$sql .= " ORDER BY nome_equip ASC"; // Mantém a ordenação por nome_equip

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    // Usar call_user_func_array para bind_param com array dinâmico
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
    echo json_encode(['success' => false, 'message' => 'Nenhum equipamento encontrado para esta cidade ou termo de busca.']);
}

$stmt->close();
$conn->close();
?>
