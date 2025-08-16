<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$city_id = $_GET['city_id'] ?? null;
$flow_type = $_GET['flow_type'] ?? null;

if (empty($city_id) || empty($flow_type)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros city_id e flow_type são obrigatórios.']);
    exit();
}

$pending_items = [];

$sql = "SELECT
            m.id_manutencao,
            m.inicio_reparo,
            e.nome_equip,
            e.referencia_equip,
            m.ocorrencia_reparo,
            m.tipo_manutencao,
            m.status_reparo,
            m.motivo_devolucao,
            c.nome AS cidade_nome
        FROM
            manutencoes m
        JOIN
            equipamentos e ON m.id_equipamento = e.id_equipamento
        JOIN
            cidades c ON m.id_cidade = c.id_cidade
        WHERE
            m.id_cidade = ? AND m.status_reparo = 'pendente'";

$params = [(int)$city_id];
$types = "i";

if ($flow_type === 'maintenance') {
    $sql .= " AND m.tipo_manutencao IN ('corretiva', 'preditiva', 'preventiva')";
} elseif ($flow_type === 'installation') {
    $sql .= " AND m.tipo_manutencao = 'instalação'";
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de fluxo inválido.']);
    exit();
}

$sql .= " ORDER BY m.inicio_reparo DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pending_items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $pending_items]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência pendente encontrada para esta cidade e tipo de fluxo.']);
}

$stmt->close();
$conn->close();
?>