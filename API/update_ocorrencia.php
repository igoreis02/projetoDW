<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// --- Configurações do Banco de Dados ---
require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']);
    exit();
}

$action = $input['action'] ?? null;
$id_manutencao = $input['id_manutencao'] ?? null;

if (empty($action) || empty($id_manutencao)) {
    echo json_encode(['success' => false, 'message' => 'Ação e ID da manutenção são obrigatórios.']);
    exit();
}

$conn->begin_transaction();

try {
    if ($action === 'update_status') {
        $new_status = $input['status'] ?? null;
        if (empty($new_status) || !in_array($new_status, ['pendente', 'cancelado'])) {
            throw new Exception('Status inválido fornecido.');
        }

        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
        $stmt->bind_param('si', $new_status, $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar o status da manutenção.');
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso para ' . $new_status]);

    } elseif ($action === 'concluir_provedor') { 
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo finalizado é obrigatória.');
        }

        $stmt = $conn->prepare(
            "UPDATE manutencoes 
             SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ? 
             WHERE id_manutencao = ?"
        );
        $stmt->bind_param('si', $reparo_finalizado, $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a ocorrência do provedor.');
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência do provedor concluída com sucesso.']);

    } elseif ($action === 'concluir_reparo') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];
        $materiais_utilizados = $input['materiais_utilizados'] ?? null;
        $rompimento_lacre = $input['rompimento_lacre'] ? 1 : 0;
        $numero_lacre = $input['numero_lacre'] ?? null;
        $info_rompimento = $input['info_rompimento'] ?? null;

        if (empty($reparo_finalizado) || empty($inicio_reparo) || empty($fim_reparo) || empty($tecnicos) || empty($veiculos) || empty($materiais_utilizados)) {
            throw new Exception('Todos os campos são obrigatórios para concluir o reparo.');
        }

        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ? WHERE id_manutencao = ?");
        $stmt->bind_param('ssissi', $reparo_finalizado, $materiais_utilizados, $rompimento_lacre, $numero_lacre, $info_rompimento, $id_manutencao);
        
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a manutenção principal.');
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, 'concluido')");
        
        $veiculos_count = count($veiculos);
        $i = 0;
        foreach ($tecnicos as $id_tecnico) {
            $id_veiculo_associado = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
            
            $stmt->bind_param('iiiss', $id_manutencao, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao inserir novo técnico/veículo para a conclusão.');
            }
            $i++;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Reparo concluído e registrado com sucesso.']);
    
    // <<< ESTE BLOCO FOI ATUALIZADO PARA SUPORTAR A EDIÇÃO DO REPARO FINALIZADO
    } elseif ($action === 'edit_ocorrencia') {
        $ocorrencia = $input['ocorrencia_reparo'] ?? null;
        $reparo_finalizado = $input['reparo_finalizado'] ?? null; // Pega o novo campo

        if (empty($ocorrencia)) {
            throw new Exception('O texto da ocorrência não pode ser vazio.');
        }

        // Prepara a query e os parâmetros dinamicamente
        $params = [$ocorrencia];
        $types = 's';
        $sql = "UPDATE manutencoes SET ocorrencia_reparo = ?";

        // Se o reparo finalizado foi enviado (não é nulo), adiciona à query
        if (isset($reparo_finalizado)) {
            $sql .= ", reparo_finalizado = ?";
            $types .= 's';
            $params[] = $reparo_finalizado;
        }

        $sql .= " WHERE id_manutencao = ?";
        $types .= 'i';
        $params[] = $id_manutencao;

        $stmt = $conn->prepare($sql);
        // Usa o operador ... para passar os parâmetros do array
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar a ocorrência.');
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência atualizada com sucesso.']);
        // FIM DO BLOCO ATUALIZADO

    } elseif ($action === 'assign') {
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];

        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'em andamento' WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar o status da ocorrência.');
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        if (!empty($tecnicos)) {
            $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, 'pendente')");
            
            $veiculos_count = count($veiculos);
            $i = 0;
            foreach ($tecnicos as $id_tecnico) {
                $id_veiculo_associado = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
                
                $stmt->bind_param('iiiss', $id_manutencao, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
                if (!$stmt->execute()) {
                    throw new Exception('Falha ao inserir novo técnico/veículo.');
                }
                $i++;
            }
            $stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência atribuída com sucesso.']);
    } else {
        throw new Exception('Ação desconhecida.');
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>