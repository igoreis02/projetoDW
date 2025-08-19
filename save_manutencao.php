<?php
session_start(); 

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id_usuario_logado = $_SESSION['user_id'];
$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$status_reparo = $data['status_reparo'] ?? 'pendente'; 
$tipo_manutencao = $data['tipo_manutencao'] ?? 'corretiva'; 
$reparo_finalizado = $data['reparo_finalizado'] ?? null;
$observacao_instalacao = $data['observacao_instalacao'] ?? null;
$tecnico_in_loco = $data['tecnico_in_loco'] ?? null;
// **NOVO: Recebe o ID do provedor**
$id_provedor = $data['id_provedor'] ?? null;

if (empty($id_equipamento) || empty($id_cidade) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

if ($tipo_manutencao === 'preditiva') {
    if ($data['realizado_por'] === 'provedor' && $tecnico_in_loco === true) {
        $status_reparo = 'pendente';
        $reparo_finalizado = null; 
        
        // **ALTERADO: Adiciona id_provedor**
        $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, id_provedor, status_reparo, tipo_manutencao, ocorrencia_reparo, reparo_finalizado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $id_provedor, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $reparo_finalizado);
    } else {
        $status_reparo = 'concluido';
        if (empty($reparo_finalizado)) {
            echo json_encode(['success' => false, 'message' => 'A descrição do reparo é obrigatória.']);
            exit();
        }
        $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, id_provedor, status_reparo, tipo_manutencao, ocorrencia_reparo, reparo_finalizado, fim_reparo, tempo_reparo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), '00:00:00')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $id_provedor, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $reparo_finalizado);
    }
} else if ($tipo_manutencao === 'instalação') {
    $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);
} else { // Corretiva
    $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cadastrada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar manutenção: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>