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
$equipment_types = $data['equipment_types'] ?? [];

// Captura dados adicionais para a nova lógica de etiqueta
$nome_equip = $data['equipment_name'] ?? 'Nome não informado';
$referencia_equip = $data['equipment_ref'] ?? 'Referência não informada';

if (empty($id_equipamento) && $tipo_manutencao !== 'instalação') {
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

    // FLUXO DE INSERÇÃO PARA NOVAS OCORRÊNCIAS
    if ($tipo_manutencao === 'instalação') {
        $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, observacao_instalacao) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiissss", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $observacao_instalacao);
    } else {
        // Lógica para 'corretiva' (Matriz Técnica) e outros tipos
        $sql = "INSERT INTO manutencoes (id_usuario, id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, nivel_ocorrencia) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $nivel_ocorrencia = '2';
        $stmt->bind_param("iiisssi", $id_usuario_logado, $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo, $nivel_ocorrencia);
    }

    if ($stmt && $stmt->execute()) {
        if (!$id_manutencao_existente) {
            $id_retorno = $conn->insert_id;
        }

        // Se for uma instalação, cria a ocorrência de processamento
        if ($tipo_manutencao === 'instalação') {

            // 1. Defina os tipos de equipamento que devem gerar uma ocorrência para etiqueta
            $tipos_para_etiqueta = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'MONITOR DE SEMÁFORO'];

            // 2. Verifique se algum dos tipos de equipamento instalados está na lista acima
            $intersecao = array_intersect($tipos_para_etiqueta, $equipment_types);

            // 3. Se houver correspondência (!empty), cria a ocorrência de processamento
            if (!empty($intersecao)) {
                if (empty($id_equipamento)) {
                    throw new Exception('ID do equipamento não foi fornecido para criar a ocorrência de etiqueta.');
                }

                $dt_ocorrencia = date('Y-m-d H:i:s');
                $tipo_ocorrencia_proc = 'Aguardando etiqueta';
                $descricao_proc = "Fazer etiqueta para o equipamento ({$nome_equip} - {$referencia_equip})";
                $status_proc = 'pendente';

                $sql_proc = "INSERT INTO ocorrencia_processamento 
                            (id_equipamento, id_usuario_registro, dt_ocorrencia, tipo_ocorrencia, descricao, status, id_cidade) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt_proc = $conn->prepare($sql_proc);
                $stmt_proc->bind_param("iissssi", $id_equipamento, $id_usuario_logado, $dt_ocorrencia, $tipo_ocorrencia_proc, $descricao_proc, $status_proc, $id_cidade);

                if (!$stmt_proc->execute()) {
                    throw new Exception('Falha ao criar a ocorrência de processamento para a etiqueta: ' . $stmt_proc->error);
                }
                $stmt_proc->close();
            }
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
