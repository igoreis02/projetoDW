<?php
session_start(); // Adicionado para obter o ID do usuário logado
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']);
    exit();
}

$action = $input['action'] ?? null;
// Alterado para um nome genérico 'id', pois a nova tabela tem um nome de coluna diferente
$id = $input['id'] ?? $input['id_manutencao'] ?? null;


if (empty($action) || empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Ação e ID são obrigatórios.']);
    exit();
}

// Garante que o usuário está logado para registrar quem concluiu a tarefa
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}
$id_usuario_concluiu = $_SESSION['user_id'];

$conn->begin_transaction();

try {
    // --- INÍCIO DA NOVA LÓGICA ---
    if ($action === 'concluir_ocorrencia_provedor') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        $inLoco = $input['inLoco'] ?? 0;
        $sem_intervencao = $input['sem_intervencao'] ?? 0;
        $tecnico_dw = $input['tecnico_dw'] ?? 0;

        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo/problema é obrigatória.');
        }

        $stmt_get_ocorrencia = $conn->prepare("SELECT * FROM ocorrencia_provedor WHERE id_ocorrencia_provedor = ?");
        $stmt_get_ocorrencia->bind_param('i', $id);
        $stmt_get_ocorrencia->execute();
        $result_ocorrencia = $stmt_get_ocorrencia->get_result();
        $ocorrencia_data = $result_ocorrencia->fetch_assoc();
        $stmt_get_ocorrencia->close();

        if (!$ocorrencia_data) {
            throw new Exception("Ocorrência de provedor não encontrada.");
        }

        $inicio_reparo_dt = new DateTime($ocorrencia_data['dt_inicio_reparo']);
        $fim_reparo_dt = new DateTime();
        $intervalo = $fim_reparo_dt->diff($inicio_reparo_dt);
        $tempo_reparo = $intervalo->format('%H:%I:%S');

        $sql_update = "UPDATE ocorrencia_provedor SET status = 'concluido', dt_fim_reparo = NOW(), id_usuario_concluiu = ?, des_reparo = ?, inLoco = ?, sem_intervencao = ?, tecnico_dw = ?, tempo_reparo = ? WHERE id_ocorrencia_provedor = ?";
        $stmt_update = $conn->prepare($sql_update);
        // CORREÇÃO: Usando a variável correta $id_usuario_concluiu em vez de $id_usuario_sessao
        $stmt_update->bind_param('isiiisi', $id_usuario_concluiu, $reparo_finalizado, $inLoco, $sem_intervencao, $tecnico_dw, $tempo_reparo, $id);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Falha ao concluir a ocorrência do provedor.');
        }
        $stmt_update->close();

        if ($tecnico_dw == 1) {
            $sql_insert_manutencao = "INSERT INTO manutencoes (id_equipamento, id_usuario, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, inicio_reparo) VALUES (?, ?, ?, 'pendente', 'corretiva', ?, NOW())";
            $stmt_insert = $conn->prepare($sql_insert_manutencao);
            $nova_ocorrencia =  $reparo_finalizado;
            // CORREÇÃO: Usando a variável correta $id_usuario_concluiu
            $stmt_insert->bind_param("iiis", $ocorrencia_data['id_equipamento'], $id_usuario_concluiu, $ocorrencia_data['id_cidade'], $nova_ocorrencia);
            if (!$stmt_insert->execute()) {
                throw new Exception('Falha ao criar a nova manutenção para o técnico DW.');
            }
            $stmt_insert->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência concluída e registrada com sucesso!']);

    } elseif ($action === 'update_status') {
        $new_status = $input['status'] ?? null;
        $origem = $input['origem'] ?? null; // Adicionado para saber qual tabela atualizar

        if (empty($new_status) || !in_array($new_status, ['pendente', 'cancelado'])) {
            throw new Exception('Status inválido fornecido.');
        }
        
        if (empty($origem)) {
            throw new Exception('Origem da ocorrência não especificada.');
        }

        if ($origem === 'ocorrencia_provedor') {
            $stmt = $conn->prepare("UPDATE ocorrencia_provedor SET status = ? WHERE id_ocorrencia_provedor = ?");
        } else { 
            $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
        }

        $stmt->bind_param('si', $new_status, $id);
        if (!$stmt->execute()) { throw new Exception('Falha ao atualizar o status.'); }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso para ' . $new_status]);
    } elseif ($action === 'concluir_provedor') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo finalizado é obrigatória.');
        }

        $stmt_check = $conn->prepare("SELECT tipo_manutencao, id_equipamento, inicio_reparo FROM manutencoes WHERE id_manutencao = ?");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        $manutencao_data = $result->fetch_assoc();
        $stmt_check->close();

        if (!$manutencao_data) {
            throw new Exception('Ocorrência não encontrada.');
        }

        $tipo_manutencao = $manutencao_data['tipo_manutencao'];
        $id_equipamento = $manutencao_data['id_equipamento'];
        
        if ($tipo_manutencao === 'instalação') {
            $stmt_manutencao = $conn->prepare(
                "UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ?, inst_prov = 1, data_provedor = NOW(), tempo_reparo = TIMEDIFF(NOW(), inicio_reparo) WHERE id_manutencao = ?"
            );
            $stmt_manutencao->bind_param('si', $reparo_finalizado, $id);
        } else {
            $stmt_manutencao = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ? WHERE id_manutencao = ?");
            $stmt_manutencao->bind_param('si', $reparo_finalizado, $id);
        }

        if (!$stmt_manutencao->execute()) {
            throw new Exception('Falha ao concluir a ocorrência do provedor.');
        }
        $stmt_manutencao->close();
        
        if ($tipo_manutencao === 'instalação' && !empty($id_equipamento)) {
            $stmt_equip = $conn->prepare("UPDATE equipamentos SET status = 'ativo' WHERE id_equipamento = ?");
            $stmt_equip->bind_param('i', $id_equipamento);
            $stmt_equip->execute();
            $stmt_equip->close();
        }
        
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
        
        // CORREÇÃO: Usando a variável correta $id em vez de $id_manutencao
        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ? WHERE id_manutencao = ?");
        $stmt->bind_param('ssissi', $reparo_finalizado, $materiais_utilizados, $rompimento_lacre, $numero_lacre, $info_rompimento, $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a manutenção principal.');
        }
        $stmt->close();
        
        // CORREÇÃO: Usando a variável correta $id
        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, 'concluido')");
        
        $veiculos_count = count($veiculos);
        $i = 0;
        foreach ($tecnicos as $id_tecnico) {
            $id_veiculo_associado = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
            // CORREÇÃO: Usando a variável correta $id
            $stmt->bind_param('iiiss', $id, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao inserir novo técnico/veículo para a conclusão.');
            }
            $i++;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Reparo concluído e registrado com sucesso.']);
    
    } elseif ($action === 'edit_ocorrencia') {
       $ocorrencia = $input['ocorrencia_reparo'] ?? null;
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        // CORREÇÃO CRÍTICA: Lendo a variável 'origem' que vem do Javascript
        $origem = $input['origem'] ?? null;

        if (empty($ocorrencia)) {
            throw new Exception('O texto da ocorrência não pode ser vazio.');
        }
        
        if ($origem === 'ocorrencia_provedor') {
             $sql = "UPDATE ocorrencia_provedor SET des_ocorrencia = ?, des_reparo = ? WHERE id_ocorrencia_provedor = ?";
             $stmt = $conn->prepare($sql);
             $stmt->bind_param('ssi', $ocorrencia, $reparo_finalizado, $id);
        } else {
            // Lógica para a tabela 'manutencoes'
            $params = [$ocorrencia];
            $types = 's';
            $sql = "UPDATE manutencoes SET ocorrencia_reparo = ?";

            if (isset($reparo_finalizado)) {
                $sql .= ", reparo_finalizado = ?";
                $types .= 's';
                $params[] = $reparo_finalizado;
            }

            $sql .= " WHERE id_manutencao = ?";
            $types .= 'i';
            $params[] = $id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) { throw new Exception('Falha ao atualizar a ocorrência: ' . $stmt->error);}
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência atualizada com sucesso.']);

    } elseif ($action === 'assign') {
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];

        // CORREÇÃO: Usando a variável correta $id
        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'em andamento' WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar o status da ocorrência.');
        }
        $stmt->close();

        // CORREÇÃO: Usando a variável correta $id
        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id);
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
                // CORREÇÃO: Usando a variável correta $id
                $stmt->bind_param('iiiss', $id, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
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
    // Definindo o código de status HTTP para erro, o que é uma boa prática
    http_response_code(400); 
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>