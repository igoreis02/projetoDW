<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configurações do banco de dados
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
$reparo_finalizado = $data['reparo_finalizado'] ?? null; // Recebe a descrição do reparo

if (empty($id_manutencao) || empty($status_reparo)) {
    echo json_encode(['success' => false, 'message' => 'ID da manutenção e novo status são obrigatórios.']);
    exit();
}

$sql = "UPDATE manutencoes SET status_reparo = ?";
$types = "s";
$params = [$status_reparo];

// Lógica para calcular e salvar o tempo de reparo se o status for 'concluido'
if ($status_reparo === 'concluido') {
    // Primeiro, obtenha o inicio_reparo da manutenção
    $stmt_get_inicio = $conn->prepare("SELECT inicio_reparo FROM manutencoes WHERE id_manutencao = ?");
    if ($stmt_get_inicio === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta de inicio_reparo: ' . $conn->error]);
        exit();
    }
    $stmt_get_inicio->bind_param("i", $id_manutencao);
    $stmt_get_inicio->execute();
    $result_inicio = $stmt_get_inicio->get_result();
    $row_inicio = $result_inicio->fetch_assoc();
    $stmt_get_inicio->close();

    $inicio_reparo_str = $row_inicio['inicio_reparo'] ?? null;
    $tempo_reparo_calculado = null;

    if ($inicio_reparo_str) {
        $inicio_datetime = new DateTime($inicio_reparo_str);
        $fim_datetime = new DateTime(); // Data e hora atuais
        $interval = $inicio_datetime->diff($fim_datetime);

        $parts = [];
        if ($interval->y > 0) $parts[] = $interval->y . ' ano(s)';
        if ($interval->m > 0) $parts[] = $interval->m . ' mês(es)';
        if ($interval->d > 0) $parts[] = $interval->d . ' dia(s)';
        if ($interval->h > 0) $parts[] = $interval->h . ' hora(s)';
        if ($interval->i > 0) $parts[] = $interval->i . ' minuto(s)';
        // Se a diferença for muito pequena, mostrar segundos
        if ($interval->s > 0 && empty($parts)) $parts[] = $interval->s . ' segundo(s)';
        
        $tempo_reparo_calculado = empty($parts) ? 'Poucos segundos' : implode(', ', $parts);
    } else {
        $tempo_reparo_calculado = 'Não calculado (início não disponível)';
    }

    $sql .= ", fim_reparo = NOW(), reparo_finalizado = ?, tempo_reparo = ?";
    $types .= "ss"; // Adiciona tipos para reparo_finalizado e tempo_reparo
    $params[] = $reparo_finalizado; // Adiciona a descrição aos parâmetros
    $params[] = $tempo_reparo_calculado; // Adiciona o tempo de reparo calculado
} else if ($status_reparo === 'pendente') {
    // Se o status for 'pendente' (devolvido), remove a data de fim_reparo, descrição e tempo de reparo
    $sql .= ", fim_reparo = NULL, reparo_finalizado = NULL, tempo_reparo = NULL";
}

$sql .= " WHERE id_manutencao = ?";
$types .= "i";
$params[] = $id_manutencao;

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// O operador de desempacotamento (...) é usado para passar os elementos do array $params como argumentos individuais para bind_param
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
