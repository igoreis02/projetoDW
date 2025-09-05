<?php
session_start();
// Adicionado para garantir que a resposta seja sempre JSON, mesmo com avisos do PHP
ini_set('display_errors', 0); 
error_reporting(0);
header('Content-Type: application/json');

require_once 'conexao_bd.php';

// Verifica se o usuário está autenticado na sessão
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Coleta e validação dos dados recebidos do JavaScript
$id_usuario_logado = $_SESSION['user_id'];
$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$id_provedor = $data['id_provedor'] ?? null;
$des_ocorrencia = $data['problem_description'] ?? null;
$tecnico_in_loco = $data['tecnico_in_loco'] ?? false; 
$tipo_ocorrencia = $data['tipo_ocorrencia'] ?? 'manutencao'; 

if (empty($id_equipamento) || empty($id_cidade) || empty($id_provedor) || empty($des_ocorrencia)) {
    echo json_encode(['success' => false, 'message' => 'Dados essenciais estão faltando para registrar a ocorrência.']);
    exit();
}

$conn->begin_transaction();

try {
    // Cenário 1: Técnico In Loco "Sim" -> Ocorrência fica com status 'pendente'
    if ($tecnico_in_loco) {
        $sql = "INSERT INTO ocorrencia_provedor 
                    (id_equipamento, id_usuario_iniciou, id_provedor, id_cidade, des_ocorrencia, tipo_ocorrencia, inLoco, provedor, status) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 0, 'pendente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiss", $id_equipamento, $id_usuario_logado, $id_provedor, $id_cidade, $des_ocorrencia, $tipo_ocorrencia);
    } 
    // Cenário 2: Técnico In Loco "Não" -> Ocorrência já é criada como 'concluida'
    else {
        $des_reparo = $data['reparo_finalizado'] ?? null;
        if (empty($des_reparo)) {
            throw new Exception("A descrição do reparo é obrigatória quando não há necessidade de técnico em loco.");
        }
        $tempo_reparo = "00:00:00"; 

        $sql = "INSERT INTO ocorrencia_provedor 
                    (id_equipamento, id_usuario_iniciou, id_usuario_concluiu, id_provedor, id_cidade, dt_fim_reparo, tempo_reparo, des_ocorrencia, tipo_ocorrencia, des_reparo, provedor, status) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 1, 'concluido')";
        $stmt = $conn->prepare($sql);

        $stmt->bind_param("iiiisssss", $id_equipamento, $id_usuario_logado, $id_usuario_logado, $id_provedor, $id_cidade, $tempo_reparo, $des_ocorrencia, $tipo_ocorrencia, $des_reparo);
    }

    if (!$stmt) {
        throw new Exception("Erro ao preparar a query: " . $conn->error);
    }

    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Falha ao inserir o registro: " . $stmt->error);
    }

    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Ocorrência de provedor registrada com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>