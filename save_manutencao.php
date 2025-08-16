<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

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

// Obtém os dados da requisição POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$status_reparo = $data['status_reparo'] ?? 'pendente'; 
$tipo_manutencao = $data['tipo_manutencao'] ?? 'corretiva'; 
$reparo_finalizado = $data['reparo_finalizado'] ?? null;
$observacao_instalacao = $data['observacao_instalacao'] ?? null; // NOVO CAMPO

// Validação básica
if (empty($id_equipamento) || empty($id_cidade) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para registrar a manutenção.']);
    exit();
}

// Validação específica para o Controle de Ocorrência
if ($tipo_manutencao === 'preditiva' && empty($reparo_finalizado)) {
    echo json_encode(['success' => false, 'message' => 'A descrição do reparo é obrigatória para o Controle de Ocorrência.']);
    exit();
}

// Prepara a instrução SQL com base no tipo de manutenção
if ($tipo_manutencao === 'preditiva' && $status_reparo === 'concluido') {
    $sql = "INSERT INTO manutencoes (id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, reparo_finalizado, fim_reparo, tempo_reparo) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), '00:00:00')";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { /* ... error handling ... */ }
    $stmt->bind_param("iissss", $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $reparo_finalizado);

} else if ($tipo_manutencao === 'instalação') { // LÓGICA ESPECÍFICA PARA INSTALAÇÃO
    $sql = "INSERT INTO manutencoes (id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL (instalação): ' . $conn->error]);
        exit();
    }
    // 'iissss' -> int, int, string, string, string, string
    $stmt->bind_param("iissss", $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);

} else {
    // Fluxo padrão para os outros tipos de manutenção (ex: corretiva)
    $sql = "INSERT INTO manutencoes (id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) { /* ... error handling ... */ }
    $stmt->bind_param("iisss", $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);
}

// Executa a instrução
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Manutenção registrada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar manutenção: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>