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

$id_usuario_logado = $_SESSION['user_id'];
$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$status_reparo = $data['status_reparo'] ?? 'pendente';
$tipo_manutencao = $data['tipo_manutencao'] ?? 'corretiva';
$reparo_finalizado = $data['reparo_finalizado'] ?? null;
$observacao_instalacao = $data['observacao_instalacao'] ?? null;
$id_provedor = $data['id_provedor'] ?? null;
$id_manutencao_existente = $data['id_manutencao_existente'] ?? null;

if (empty($id_equipamento) || empty($id_cidade) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

try {
    $conn->begin_transaction();
    $id_retorno = null;

    if ($id_manutencao_existente && $tipo_manutencao === 'corretiva') {
        // FLUXO DE ATUALIZAÇÃO PARA CONCATENAR PROBLEMAS 
        $ocorrencia_nova_concatenada = $data['ocorrencia_concatenada'] ?? $ocorrencia_reparo;
        $sql = "UPDATE manutencoes SET ocorrencia_reparo = ? WHERE id_manutencao = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $ocorrencia_nova_concatenada, $id_manutencao_existente);
        $id_retorno = $id_manutencao_existente;

    } else {
        // FLUXO DE INSERÇÃO
        if ($tipo_manutencao === 'preditiva' && ($data['realizado_por'] ?? null) === 'processamento') {
            
            if ($status_reparo === 'concluido') {
                if (empty($reparo_finalizado)) { throw new Exception('A descrição do reparo é obrigatória para concluir.'); }
                $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, reparo_finalizado, fim_reparo, tempo_reparo) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP(), '00:00:00')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $reparo_finalizado);
            } else { // 'pendente'
                $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiisss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);
            }
        } else if ($tipo_manutencao === 'instalação') {
            
            $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);
        
        } else { 
            $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiisss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);
        }
    }

    if ($stmt->execute()) {
        if (!$id_manutencao_existente) {
            $id_retorno = $conn->insert_id;
        }

        $conn->commit();
        $message = $id_manutencao_existente ? 'Problema adicionado com sucesso!' : 'Cadastrada com sucesso!';
        
        echo json_encode(['success' => true, 'message' => $message, 'id_manutencao' => $id_retorno]);
    } else {
        throw new Exception($stmt->error);
    }

    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar manutenção: ' . $e->getMessage()]);
}

$conn->close();
?>