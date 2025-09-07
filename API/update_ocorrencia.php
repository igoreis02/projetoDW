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



$acoes_sem_id_principal = ['edit_ocorrencia_batch', 'update_status_batch'];
if (empty($action) || (!in_array($action, $acoes_sem_id_principal) && empty($id))) {
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
    if ($action === 'concluir_ocorrencia_provedor') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        $inLoco = $input['inLoco'] ?? 0;
        $sem_intervencao = $input['sem_intervencao'] ?? 0;
        $tecnico_dw = $input['tecnico_dw'] ?? 0;
        // --- IMPLEMENTAÇÃO ADICIONADA ---
        $provedor = $input['provedor'] ?? 0; // Captura o novo campo 'provedor'

        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo/problema é obrigatória.');
        }

        // LÓGICA SIMPLIFICADA: Conforme a nova regra, a conclusão SEMPRE afeta 'ocorrencia_provedor'
        // Primeiro, buscamos os dados da ocorrência para o caso de precisar criar uma nova para o técnico DW
        $stmt_get = $conn->prepare("SELECT id_equipamento, id_cidade, des_ocorrencia FROM ocorrencia_provedor WHERE id_ocorrencia_provedor = ?");
        $stmt_get->bind_param('i', $id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $ocorrencia_data = $result->fetch_assoc();
        $stmt_get->close();

        if (!$ocorrencia_data) {
            // Se não encontrar na tabela nova, tentamos na antiga para não quebrar a funcionalidade de técnico DW
            $stmt_get_old = $conn->prepare("SELECT id_equipamento, id_cidade, ocorrencia_reparo as des_ocorrencia FROM manutencoes WHERE id_manutencao = ?");
            $stmt_get_old->bind_param('i', $id);
            $stmt_get_old->execute();
            $result_old = $stmt_get_old->get_result();
            $ocorrencia_data = $result_old->fetch_assoc();
            $stmt_get_old->close();
            if (!$ocorrencia_data)
                throw new Exception("Ocorrência não encontrada em nenhuma das tabelas.");
        }

        // --- IMPLEMENTAÇÃO ADICIONADA NA QUERY E NO BIND ---
        // Agora, atualizamos a ocorrência na tabela 'ocorrencia_provedor'
        $sql_update = "UPDATE ocorrencia_provedor SET 
                    status = 'concluido', 
                    dt_fim_reparo = NOW(), 
                    id_usuario_concluiu = ?, 
                    des_reparo = ?, 
                    inLoco = ?, 
                    sem_intervencao = ?, 
                    tecnico_dw = ?, 
                    provedor = ?, -- Campo adicionado
                    tempo_reparo = TIMEDIFF(NOW(), dt_inicio_reparo) 
                   WHERE id_ocorrencia_provedor = ?";
        $stmt_update = $conn->prepare($sql_update);
        // Adicionado um 'i' para o novo campo $provedor (integer)
        $stmt_update->bind_param('isiiiis', $id_usuario_concluiu, $reparo_finalizado, $inLoco, $sem_intervencao, $tecnico_dw, $provedor, $id);

        if (!$stmt_update->execute()) {
            throw new Exception('Falha ao concluir a ocorrência.');
        }
        // Se a atualização afetou 0 linhas, pode ser um registro antigo. Vamos atualizá-lo também.
        if ($stmt_update->affected_rows === 0) {
            $sql_update_old = "UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ? WHERE id_manutencao = ?";
            $stmt_update_old = $conn->prepare($sql_update_old);
            $stmt_update_old->bind_param('si', $reparo_finalizado, $id);
            $stmt_update_old->execute();
            $stmt_update_old->close();
        }
        $stmt_update->close();

        // A lógica para criar uma nova manutenção para o técnico DW permanece
        if ($tecnico_dw == 1 && $ocorrencia_data) {
            $sql_insert_manutencao = "INSERT INTO manutencoes (id_equipamento, id_usuario, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo, inicio_reparo) VALUES (?, ?, ?, 'pendente', 'corretiva', ?, NOW())";
            $stmt_insert = $conn->prepare($sql_insert_manutencao);
            $nova_ocorrencia = $reparo_finalizado;
            $stmt_insert->bind_param("iiis", $ocorrencia_data['id_equipamento'], $id_usuario_concluiu, $ocorrencia_data['id_cidade'], $nova_ocorrencia);
            if (!$stmt_insert->execute()) {
                throw new Exception('Falha ao criar a nova manutenção para o técnico DW.');
            }
            $stmt_insert->close();
        }

        echo json_encode(['success' => true, 'message' => 'Ocorrência concluída e registrada com sucesso!']);
    } elseif ($action === 'validar_reparo') {
        // 1. Mudar status da manutenção para 'concluido'
        $stmt_update = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido' WHERE id_manutencao = ?");
        $stmt_update->bind_param('i', $id);
        if (!$stmt_update->execute()) {
            throw new Exception('Falha ao validar a manutenção.');
        }
        $stmt_update->close();

        // 2. Obter dados da manutenção para criar a ocorrência de processamento
        $stmt_get = $conn->prepare("SELECT id_usuario, tipo_manutencao, ocorrencia_reparo FROM manutencoes WHERE id_manutencao = ?");
        $stmt_get->bind_param('i', $id);
        $stmt_get->execute();
        $manut_data = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if ($manut_data) {
            // 3. Inserir novo registro na tabela de ocorrência de processamento
            $stmt_insert = $conn->prepare("INSERT INTO ocorrencia_processamento (id_manutencao, id_usuario_registro, tipo_ocorrencia, descricao, status, dt_resolucao) VALUES (?, ?, ?, ?, 'concluido', NOW())");
            // Usamos o tipo da manutenção original (corretiva/preditiva) como tipo_ocorrencia
            $stmt_insert->bind_param('isss', $id, $id_usuario_logado, $manut_data['tipo_manutencao'], $manut_data['ocorrencia_reparo']);
            if (!$stmt_insert->execute()) {
                throw new Exception('Falha ao criar o registro de processamento.');
            }
            $stmt_insert->close();
        } else {
            throw new Exception('Manutenção original não encontrada para criar a ocorrência.');
        }

        echo json_encode(['success' => true, 'message' => 'Reparo validado e ocorrência criada com sucesso.']);

        // <-- MUDANÇA AQUI: Adicionada a nova ação 'retornar_manutencao' -->
    } elseif ($action === 'retornar_manutencao') {
        $nova_ocorrencia = $input['nova_ocorrencia'] ?? null;
        if (empty($nova_ocorrencia)) {
            throw new Exception('O motivo do retorno é obrigatório.');
        }

        // Atualiza a manutenção: status para 'pendente', nova descrição, e limpa os campos de conclusão.
        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'pendente', ocorrencia_reparo = ?, fim_reparo = NULL, reparo_finalizado = NULL, tempo_reparo = NULL WHERE id_manutencao = ?");
        $stmt->bind_param('si', $nova_ocorrencia, $id);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao retornar a ocorrência.');
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Ocorrência retornada para "Pendente" com sucesso.']);
    } elseif ($action === 'concluir_ocorrencia_processamento') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição da solução é obrigatória.');
        }

        // 1. Atualiza a tabela de ocorrência de processamento
        $stmt = $conn->prepare("UPDATE ocorrencia_processamento SET status = 'concluido', dt_resolucao = NOW() WHERE id_ocorrencia_processamento = ?");
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a ocorrência de processamento.');
        }
        $stmt->close();

        // 2. Busca o ID da manutenção e a data de início para calcular o tempo de reparo
        // <-- CORREÇÃO: Especificando a tabela (m.id_manutencao e m.inicio_reparo) para evitar ambiguidade
        $stmt_get_manut_id = $conn->prepare("SELECT m.id_manutencao, m.inicio_reparo FROM ocorrencia_processamento op JOIN manutencoes m ON op.id_manutencao = m.id_manutencao WHERE op.id_ocorrencia_processamento = ?");
        $stmt_get_manut_id->bind_param('i', $id);
        $stmt_get_manut_id->execute();
        $manut_data = $stmt_get_manut_id->get_result()->fetch_assoc();
        $stmt_get_manut_id->close();

        if ($manut_data) {
            $id_manutencao = $manut_data['id_manutencao'];
            $inicio_reparo = $manut_data['inicio_reparo'];

            $sql_update_manut = "UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ?, tempo_reparo = TIMEDIFF(NOW(), ?) WHERE id_manutencao = ?";
            $stmt_update_manut = $conn->prepare($sql_update_manut);
            $stmt_update_manut->bind_param('ssi', $reparo_finalizado, $inicio_reparo, $id_manutencao);
            $stmt_update_manut->execute();
            $stmt_update_manut->close();
        }

        echo json_encode(['success' => true, 'message' => 'Ocorrência de processamento concluída com sucesso.']);
    } elseif ($action === 'update_status_batch') {
        $ids = $input['ids'] ?? [];
        $new_status = $input['status'] ?? null;

        if (empty($ids) || !is_array($ids) || empty($new_status)) {
            throw new Exception('IDs e novo status são obrigatórios para a atualização em lote.');
        }

        // Garante que apenas status permitidos sejam usados
        if (!in_array($new_status, ['pendente', 'cancelado'])) {
            throw new Exception('Status inválido fornecido para o lote.');
        }

        // Cria a lista de placeholders para a cláusula IN (?, ?, ?)
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao IN ($placeholders)");

        // Cria o array de tipos de parâmetros (ex: 'sii' para status, id, id)
        $types = 's' . str_repeat('i', count($ids));

        // Combina o status com os IDs para o bind_param
        $params = array_merge([$new_status], $ids);

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar o status em lote.');
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso para ' . count($ids) . ' ocorrências.']);

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
        } elseif ($origem === 'ocorrencia_processamento') {
            // 1. Atualiza o status na tabela de processamento
            $stmt_proc = $conn->prepare("UPDATE ocorrencia_processamento SET status = ? WHERE id_ocorrencia_processamento = ?");
            $stmt_proc->bind_param('si', $new_status, $id);
            if (!$stmt_proc->execute()) {
                throw new Exception('Falha ao atualizar status do processamento.');
            }
            $stmt_proc->close();

            // 2. Busca o ID da manutenção para sincronizar
            $stmt_get_manut_id = $conn->prepare("SELECT id_manutencao FROM ocorrencia_processamento WHERE id_ocorrencia_processamento = ?");
            $stmt_get_manut_id->bind_param('i', $id);
            $stmt_get_manut_id->execute();
            $manut_id_result = $stmt_get_manut_id->get_result()->fetch_assoc();
            $stmt_get_manut_id->close();

            // 3. Atualiza o status na tabela de manutenções
            if ($manut_id_result) {
                $id_manutencao = $manut_id_result['id_manutencao'];
                $stmt_manut = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
                $stmt_manut->bind_param('si', $new_status, $id_manutencao);
                $stmt_manut->execute();
                $stmt_manut->close();
            }
        } else { // Fallback para a tabela 'manutencoes'
            $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
            $stmt->bind_param('si', $new_status, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar o status.');
            }
            $stmt->close();
        }

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
        $origem = $input['origem'] ?? null;
        if (empty($ocorrencia)) {
            throw new Exception('O texto da ocorrência não pode ser vazio.');
        }
        if ($origem === 'ocorrencia_provedor') {
            $sql = "UPDATE ocorrencia_provedor SET des_ocorrencia = ?, des_reparo = ? WHERE id_ocorrencia_provedor = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $ocorrencia, $reparo_finalizado, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar a ocorrência do provedor: ' . $stmt->error);
            }
            $stmt->close();
        } elseif ($origem === 'ocorrencia_processamento') {
            $stmt_proc = $conn->prepare("UPDATE ocorrencia_processamento SET descricao = ? WHERE id_ocorrencia_processamento = ?");
            $stmt_proc->bind_param('si', $ocorrencia, $id);
            if (!$stmt_proc->execute()) {
                throw new Exception('Falha ao editar a ocorrência de processamento.');
            }
            $stmt_proc->close();
            $stmt_get_manut_id = $conn->prepare("SELECT id_manutencao FROM ocorrencia_processamento WHERE id_ocorrencia_processamento = ?");
            $stmt_get_manut_id->bind_param('i', $id);
            $stmt_get_manut_id->execute();
            $manut_id_result = $stmt_get_manut_id->get_result()->fetch_assoc();
            $stmt_get_manut_id->close();
            if ($manut_id_result) {
                $id_manutencao = $manut_id_result['id_manutencao'];
                $stmt_manut = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ?, reparo_finalizado = ? WHERE id_manutencao = ?");
                $stmt_manut->bind_param('ssi', $ocorrencia, $reparo_finalizado, $id_manutencao);
                if (!$stmt_manut->execute()) {
                    throw new Exception('Falha ao sincronizar edição com a manutenção.');
                }
                $stmt_manut->close();
            }
        } else {
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
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar a ocorrência: ' . $stmt->error);
            }
            $stmt->close();
        }

        echo json_encode(['success' => true, 'message' => 'Ocorrência atualizada com sucesso.']);

    } elseif ($action === 'edit_ocorrencia_batch') {
        $updates = $input['updates'] ?? null;

        if (empty($updates) || !is_array($updates)) {
            throw new Exception('Dados para atualização em lote são inválidos.');
        }

        // Prepara a query uma vez para ser reutilizada no loop
        $stmt = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ? WHERE id_manutencao = ?");

        foreach ($updates as $update_item) {
            $id_manutencao = $update_item['id_manutencao'] ?? null;
            $ocorrencia_reparo = $update_item['ocorrencia_reparo'] ?? null;

            if (empty($id_manutencao) || empty($ocorrencia_reparo)) {
                // Se algum item for inválido, interrompe a transação
                throw new Exception('Item inválido no lote de atualização. ID e ocorrência são obrigatórios.');
            }

            $stmt->bind_param('si', $ocorrencia_reparo, $id_manutencao);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar a ocorrência ID: ' . $id_manutencao);
            }
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Ocorrências atualizadas com sucesso.']);

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
