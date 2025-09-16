<?php
// /API/save_ocorrencia_processamento.php

session_start();
header('Content-Type: application/json');
require_once '../conexao_bd.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$id_usuario_logado = $_SESSION['user_id'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Dados recebidos do JavaScript
$city_id = $data['city_id'] ?? null;
$equipment_id = $data['equipment_id'] ?? null;
$problem_description = $data['problem_description'] ?? null;
$reparo_finalizado_desc = $data['reparo_finalizado'] ?? null;
$reparo_concluido = $data['reparo_concluido'] ?? false; // true para 'Sim', false para 'Não'

// Validação dos dados essenciais
if (empty($city_id) || empty($equipment_id) || empty($problem_description)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para registrar a ocorrência.']);
    exit();
}

// Determina o status e a data de resolução com base na resposta do usuário
$status = $reparo_concluido ? 'concluido' : 'pendente';
$dt_resolucao = $reparo_concluido ? date('Y-m-d H:i:s') : null;
$id_usuario_concluiu = $reparo_concluido ? $id_usuario_logado : null; // Define o ID de quem concluiu
$tipo_ocorrencia = 'preditiva';
$tempo_reparo = $reparo_concluido ? '00:00:00' : null; // Inicializa como zero se concluído, null se pendente

try {
   $sql = "INSERT INTO ocorrencia_processamento 
                (id_equipamento, id_usuario_registro, id_usuario_concluiu, tipo_ocorrencia, descricao, status, dt_resolucao, reparo, tempo_reparo, id_cidade) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("iiissssssi", 
        $equipment_id, 
        $id_usuario_logado, 
        $id_usuario_concluiu,
        $tipo_ocorrencia, 
        $problem_description, 
        $status, 
        $dt_resolucao, 
        $reparo_finalizado_desc, 
        $tempo_reparo, 
        $city_id
    );
    
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Controle de ocorrência registrado com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar controle de ocorrência: ' . $e->getMessage()]);
}

$conn->close();
?>