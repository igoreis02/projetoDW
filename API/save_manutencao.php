<?php
// /API/save_manutencao.php

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
$id_manutencao_existente = $data['id_manutencao_existente'] ?? null;

if (empty($id_equipamento) && $tipo_manutencao !== 'instalação') {
    // Equipamento é obrigatório para tudo, exceto novas instalações que ainda não têm ID
    echo json_encode(['success' => false, 'message' => 'ID do equipamento é obrigatório.']);
    exit();
}
if (empty($id_cidade) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Cidade e descrição da ocorrência são obrigatórios.']);
    exit();
}

try {
    $conn->begin_transaction();
    $id_retorno = null;
    $stmt = null;

    if ($id_manutencao_existente && $tipo_manutencao === 'corretiva') {
        // FLUXO DE ATUALIZAÇÃO PARA CONCATENAR PROBLEMAS (Matriz Técnica)
        $sql = "UPDATE manutencoes SET ocorrencia_reparo = CONCAT(ocorrencia_reparo, '; ', ?) WHERE id_manutencao = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $ocorrencia_reparo, $id_manutencao_existente);
        $id_retorno = $id_manutencao_existente;
    } else {
        // FLUXO DE INSERÇÃO PARA NOVAS OCORRÊNCIAS
        if ($tipo_manutencao === 'instalação') {
            $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);
        } else { 
            // Lógica para 'corretiva' (Matriz Técnica) e outros tipos que podem ser adicionados no futuro
            $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, nivel_ocorrencia) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // Definindo um nível de prioridade padrão (2) para novas ocorrências corretivas
            $nivel_ocorrencia = '2'; 
            $stmt->bind_param("iiisssi", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $nivel_ocorrencia);
        }
    }

    if ($stmt && $stmt->execute()) {
        if (!$id_manutencao_existente) {
            $id_retorno = $conn->insert_id;
        }
        $conn->commit();
        $message = $id_manutencao_existente ? 'Problema adicionado à ocorrência existente com sucesso!' : 'Ocorrência cadastrada com sucesso!';
        echo json_encode(['success' => true, 'message' => $message, 'id_manutencao' => $id_retorno]);
    } else {
        throw new Exception($stmt ? $stmt->error : "Falha na preparação da consulta.");
    }

    if ($stmt) $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar manutenção: ' . $e->getMessage()]);
}

$conn->close();
?>