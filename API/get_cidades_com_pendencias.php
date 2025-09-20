<?php
header('Content-Type: application/json');


require_once 'conexao_bd.php';

// Pega o tipo de fluxo da requisição GET
$flow_type = $_GET['flow_type'] ?? null;

if (empty($flow_type)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetro flow_type é obrigatório.']);
    exit();
}

// A cláusula DISTINCT garante que cada cidade apareça apenas uma vez
$sql = "SELECT DISTINCT
            c.id_cidade,
            c.nome
        FROM
            cidades c
        JOIN
            manutencoes m ON c.id_cidade = m.id_cidade
        WHERE
            m.status_reparo = 'pendente'";

// Adiciona o filtro com base no tipo de fluxo
if ($flow_type === 'maintenance') {
    $sql .= " AND m.tipo_manutencao IN ('corretiva', 'preditiva', 'preventiva')";
} elseif ($flow_type === 'installation') {
    $sql .= " AND m.tipo_manutencao = 'instalação'";
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de fluxo inválido.']);
    exit();
}

$sql .= " ORDER BY c.nome ASC";

$result = $conn->query($sql);

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Erro na consulta SQL: ' . $conn->error]);
    exit();
}

$cidades = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cidades[] = $row;
    }
    echo json_encode(['success' => true, 'cidades' => $cidades]);
} else {
    // Retorna sucesso com um array vazio se nenhuma cidade com pendências for encontrada
    echo json_encode(['success' => true, 'cidades' => []]);
}

$conn->close();
?>