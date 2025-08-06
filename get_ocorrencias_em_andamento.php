<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

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
$flow_type = $_GET['flow_type'] ?? null; // 'maintenance' ou 'installation'

if (empty($city_id) || empty($flow_type)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros city_id e flow_type são obrigatórios.']);
    exit();
}

$ocorrencias = [];
$sql = "SELECT
            m.id_manutencao,
            m.inicio_reparo,
            e.nome_equip,
            e.referencia_equip,
            m.ocorrencia_reparo,
            GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') AS tecnicos_nomes, -- Usa GROUP_CONCAT para múltiplos técnicos
            m.tipo_manutencao,
            m.status_reparo,
            c.nome AS cidade_nome
        FROM
            manutencoes m
        JOIN
            equipamentos e ON m.id_equipamento = e.id_equipamento
        JOIN
            cidades c ON m.id_cidade = c.id_cidade
        LEFT JOIN
            manutencoes_tecnicos mt ON m.id_manutencao = mt.id_manutencao -- Junta com a tabela de junção
        LEFT JOIN
            usuario u ON mt.id_tecnico = u.id_usuario -- Junta com a tabela de usuários para obter o nome do técnico
        WHERE
            m.id_cidade = ?";

$params = [(int)$city_id];
$types = "i";

if ($flow_type === 'maintenance') {
    // Para manutenções, queremos 'corretiva', 'preditiva', 'preventiva' com status 'em andamento'
    $sql .= " AND m.tipo_manutencao IN ('corretiva', 'preditiva', 'preventiva') AND m.status_reparo = 'em andamento'";
} elseif ($flow_type === 'installation') {
    // Para instalações, queremos 'instalação' com status 'pendente' ou 'em andamento'
    $sql .= " AND m.tipo_manutencao = 'instalação' AND (m.status_reparo = 'pendente' OR m.status_reparo = 'em andamento')";
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de fluxo inválido.']);
    exit();
}

$sql .= " GROUP BY m.id_manutencao ORDER BY m.inicio_reparo DESC"; // Agrupa por manutenção para GROUP_CONCAT

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
        $ocorrencias[] = $row;
    }
    echo json_encode(['success' => true, 'ocorrencias' => $ocorrencias]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma ocorrência encontrada para esta cidade e tipo de fluxo.']);
}

$stmt->close();
$conn->close();
?>
