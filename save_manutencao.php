<?php
session_start(); // Inicia a sessão para obter o ID do usuário

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os dados da requisição POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

//  Pega o ID do usuário logado a partir da sessão
$id_usuario_logado = $_SESSION['user_id'];

$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$status_reparo = $data['status_reparo'] ?? 'pendente'; 
$tipo_manutencao = $data['tipo_manutencao'] ?? 'corretiva'; 
$reparo_finalizado = $data['reparo_finalizado'] ?? null;
$observacao_instalacao = $data['observacao_instalacao'] ?? null;

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
    $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, reparo_finalizado, fim_reparo, tempo_reparo) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), '00:00:00')";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL (preditiva): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iiisssss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $reparo_finalizado);

} else if ($tipo_manutencao === 'instalação') {
    $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL (instalação): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);

} else {
    $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL (geral): ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("iiisss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);
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