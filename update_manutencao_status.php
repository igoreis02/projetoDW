<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id_manutencao = $data['id_manutencao'] ?? null;
$status_reparo = $data['status_reparo'] ?? null;
$reparo_finalizado = $data['reparo_finalizado'] ?? null;
$materiais_utilizados = $data['materiais_utilizados'] ?? null;
$motivo_devolucao = $data['motivo_devolucao'] ?? null;

// NOVOS: Recebe os dados de rompimento de lacre
$rompimento_lacre = $data['rompimento_lacre'] ?? null;
$numero_lacre = $data['numero_lacre'] ?? null;
$info_rompimento = $data['info_rompimento'] ?? null;
$data_rompimento = $data['data_rompimento'] ?? null;

if (empty($id_manutencao) || empty($status_reparo)) {
    echo json_encode(['success' => false, 'message' => 'ID da manutenção e novo status são obrigatórios.']);
    exit();
}

$sql = "UPDATE manutencoes SET status_reparo = ?";
$types = "s";
$params = [$status_reparo];

if ($status_reparo === 'concluido') {
    $sql .= ", fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, motivo_devolucao = NULL, tempo_reparo = TIMEDIFF(NOW(), inicio_reparo)";
    $types .= "ss";
    $params[] = $reparo_finalizado;
    $params[] = $materiais_utilizados;

    // NOVO: Adiciona os campos de rompimento de lacre à query
    if ($rompimento_lacre) {
        $sql .= ", rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ?, data_rompimento = ?";
        $types .= "isss";
        $params[] = $rompimento_lacre;
        $params[] = $numero_lacre;
        $params[] = $info_rompimento;
        $params[] = $data_rompimento;
    } else {
        $sql .= ", rompimento_lacre = 0, numero_lacre = NULL, info_rompimento = NULL, data_rompimento = NULL";
    }

} else if ($status_reparo === 'pendente') {
    $sql .= ", fim_reparo = NULL, reparo_finalizado = NULL, materiais_utilizados = NULL, tempo_reparo = NULL, motivo_devolucao = ?, rompimento_lacre = NULL, numero_lacre = NULL, info_rompimento = NULL, data_rompimento = NULL";
    $types .= "s";
    $params[] = $motivo_devolucao;
}

$sql .= " WHERE id_manutencao = ?";
$types .= "i";
$params[] = $id_manutencao;

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Status da manutenção atualizado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita ou manutenção não encontrada.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o status da manutenção: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>