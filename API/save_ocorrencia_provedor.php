<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Dados essenciais
$id_usuario_logado = $_SESSION['user_id'];
$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$id_provedor = $data['id_provedor'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$tecnico_in_loco = $data['tecnico_in_loco'] ?? false;

// Validação
if (empty($id_equipamento) || empty($id_cidade) || empty($id_provedor) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para registrar ocorrência de provedor.']);
    exit();
}

$conn->begin_transaction();

try {
    if ($tecnico_in_loco) {
        // Cenário 1: Técnico "Sim" -> Ocorrência fica pendente
        $sql = "INSERT INTO ocorrencia_provedor (id_equipamento, id_usuario_iniciou, id_provedor, id_cidade, des_ocorrencia, inLoco, status) VALUES (?, ?, ?, ?, ?, 1, 'pendente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiis", $id_equipamento, $id_usuario_logado, $id_provedor, $id_cidade, $ocorrencia_reparo);
    } else {
        // Cenário 2: Técnico "Não" -> Ocorrência já é concluída
        $reparo_realizado = $data['reparo_finalizado'] ?? null;
        if (empty($reparo_realizado)) {
            throw new Exception("A descrição do reparo é obrigatória quando não há técnico em loco.");
        }
        $tempo_reparo = "00:00:00"; // Define como instantâneo

        $sql = "INSERT INTO ocorrencia_provedor (id_equipamento, id_usuario_iniciou, id_usuario_concluiu, id_provedor, id_cidade, dt_fim_reparo, tempo_reparo, des_ocorrencia, des_reparo, inLoco, sem_intervencao, tecnico_dw, status) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, 0, 0, 0, 'concluido')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiisss", $id_equipamento, $id_usuario_logado, $id_usuario_logado, $id_provedor, $id_cidade, $tempo_reparo, $ocorrencia_reparo, $reparo_realizado);
    }

    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Ocorrência de provedor registrada com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar ocorrência: ' . $e->getMessage()]);
}

$conn->close();
?>