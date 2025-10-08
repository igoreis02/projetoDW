<?php
session_start(); // Adicionado para obter o ID do usuário logado
header('Content-Type: application/json');


require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']);
    exit();
}

$action = $input['action'] ?? null;

$id = $input['id'] ?? $input['id_manutencao'] ?? $input['id_ocorrencia_processamento'] ?? null;



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
        $provedor = $input['provedor'] ?? 0; // Captura o novo campo 'provedor'

        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo/problema é obrigatória.');
        }
        // Primeiro, buscam os dados da ocorrência para o caso de precisar criar uma nova para o técnico DW
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

        // Agora, atualiza a ocorrência na tabela 'ocorrencia_provedor'
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
        throw new Exception('Falha ao criar a nova manutenção para o técnico DW.');
    } elseif ($action === 'concluir_etiqueta') {
        $id_ocorrencia_proc = $input['id_ocorrencia_processamento'] ?? null;
        $id_equipamento = $input['id_equipamento'] ?? null;
        $dt_fabricacao = $input['dt_fabricacao'] ?? null;
        $id_manutencao_original = $input['id_manutencao'] ?? null;

        if (empty($id_ocorrencia_proc) || empty($id_equipamento) || empty($dt_fabricacao)) {
            throw new Exception('Dados insuficientes para concluir a fabricação da etiqueta.');
        }

        // 1. Atualiza a ocorrência de processamento para 'concluído'
        $stmt_proc = $conn->prepare("UPDATE ocorrencia_processamento SET status = 'concluido', dt_resolucao = NOW(), reparo = 'Etiqueta fabricada' WHERE id_ocorrencia_processamento = ?");
        $stmt_proc->bind_param('i', $id_ocorrencia_proc);
        if (!$stmt_proc->execute()) {
            throw new Exception('Falha ao concluir a ocorrência de processamento.');
        }
        $stmt_proc->close();

        // 2. Atualiza a data de fabricação na tabela de equipamentos
        $stmt_equip = $conn->prepare("UPDATE equipamentos SET dt_fabricacao = ? WHERE id_equipamento = ?");
        $stmt_equip->bind_param('si', $dt_fabricacao, $id_equipamento);
        if (!$stmt_equip->execute()) {
            throw new Exception('Falha ao salvar a data de fabricação no equipamento.');
        }
        $stmt_equip->close();
        
        // 3. Atualiza a manutenção original, marcando a etiqueta como feita
        if ($id_manutencao_original) {
            // Comando SQL simplificado para atualizar APENAS a coluna da etiqueta
            $stmt_manut = $conn->prepare("UPDATE manutencoes SET etiqueta_feita = 1 WHERE id_manutencao = ?");
            $stmt_manut->bind_param('i', $id_manutencao_original);
            if (!$stmt_manut->execute()) {
                throw new Exception('Falha ao atualizar a manutenção principal com o status da etiqueta.');
            }
            $stmt_manut->close();
        }

        echo json_encode(['success' => true, 'message' => 'Etiqueta concluída com sucesso.']);
    } elseif ($action === 'validar_reparo') {
        // Busca o tipo da manutenção e os IDs dos lacres distribuídos
        $stmt_info = $conn->prepare("SELECT tipo_manutencao, id_controle_lacres_dist FROM manutencoes WHERE id_manutencao = ?");
        $stmt_info->bind_param('i', $id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $manutencao_info = $result_info->fetch_assoc();
        $stmt_info->close();

        if (!$manutencao_info) {
            throw new Exception('Manutenção não encontrada.');
        }

        if ($manutencao_info['tipo_manutencao'] === 'afixar' && !empty($manutencao_info['id_controle_lacres_dist'])) {

            // Pega o ID do usuário logado (que está validando) para usar como ID de quem afixou.
            $id_usuario_afixou = $_SESSION['user_id'];

            // Prepara a query para atualizar a tabela de lacres
            $sql_update_lacres = "UPDATE controle_lacres SET
                                num_lacre = num_lacre_distribuido,
                                lacre_rompido = 0,
                                lacre_afixado = 1,
                                lacre_distribuido = 0,
                                dt_reporta_psie = NULL,
                                dt_fixacao = CURDATE(),
                                acao = 'Afixado',
                                id_usuario_afixou = ?,
                                obs_lacre = NULL
                            WHERE id_controle_lacres = ?";

            $stmt_lacres = $conn->prepare($sql_update_lacres);

            // Converte a string de IDs em um array
            $ids_lacres_a_afixar = explode(',', $manutencao_info['id_controle_lacres_dist']);

            // Executa a atualização para cada ID de lacre encontrado
            foreach ($ids_lacres_a_afixar as $id_lacre) {
                $id_lacre_int = (int)$id_lacre;
                $stmt_lacres->bind_param("ii", $id_usuario_afixou, $id_lacre_int);
                if (!$stmt_lacres->execute()) {
                    throw new Exception('Falha ao atualizar o status de um dos lacres afixados.');
                }
            }
            $stmt_lacres->close();
        }

        $stmt_update = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW() WHERE id_manutencao = ?");
        $stmt_update->bind_param('i', $id);

        if (!$stmt_update->execute()) {
            throw new Exception('Falha ao validar a manutenção.');
        }
        $stmt_update->close();

        echo json_encode(['success' => true, 'message' => 'Reparo validado com sucesso.']);
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

        // Busca a data de ocorrência para calcular o tempo de reparo
        $stmt_get_date = $conn->prepare("SELECT dt_ocorrencia FROM ocorrencia_processamento WHERE id_ocorrencia_processamento = ?");
        $stmt_get_date->bind_param('i', $id);
        $stmt_get_date->execute();
        $ocorrencia_data = $stmt_get_date->get_result()->fetch_assoc();
        $stmt_get_date->close();

        if (!$ocorrencia_data) {
            throw new Exception('Ocorrência de processamento não encontrada.');
        }

        // Atualiza a ocorrência de processamento
        $sql = "UPDATE ocorrencia_processamento SET 
                    status = 'concluido', 
                    dt_resolucao = NOW(), 
                    reparo = ?,
                    id_usuario_concluiu = ?,
                    tempo_reparo = TIMEDIFF(NOW(), ?) 
                WHERE id_ocorrencia_processamento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisi', $reparo_finalizado, $id_usuario_concluiu, $ocorrencia_data['dt_ocorrencia'], $id);


        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a ocorrência de processamento.');
        }
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Ocorrência concluída com sucesso.']);
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
            $stmt->bind_param('si', $new_status, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar status do provedor.');
            }
            $stmt->close();
        } elseif ($origem === 'ocorrencia_processamento') {
            // 1. Atualiza o status na tabela de processamento
            $stmt = $conn->prepare("UPDATE ocorrencia_processamento SET status = ? WHERE id_ocorrencia_processamento = ?");
            $stmt->bind_param('si', $new_status, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar status do processamento.');
            }
            $stmt->close();
        } else { // Fallback para a tabela 'manutencoes'
            // 1. Atualiza o status na tabela 'manutencoes' 
            $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
            $stmt->bind_param('si', $new_status, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao atualizar o status da manutenção.');
            }
            $stmt->close();

            // 2. Verifica se esta manutenção está ligada a uma ocorrência semafórica para sincronizar o status.
            $stmt_get_semaforica = $conn->prepare("SELECT id_ocorrencia_semaforica FROM manutencoes WHERE id_manutencao = ?");
            $stmt_get_semaforica->bind_param('i', $id);
            $stmt_get_semaforica->execute();
            $result_semaforica = $stmt_get_semaforica->get_result();
            if ($result_semaforica->num_rows > 0) {
                $row = $result_semaforica->fetch_assoc();
                $id_semaforica = $row['id_ocorrencia_semaforica'];
                // Se existir um ID de semafórica vinculado, atualiza a tabela original também.
                if ($id_semaforica) {
                    $stmt_update_semaforica = $conn->prepare("UPDATE ocorrencia_semaforica SET status = ? WHERE id = ?");
                    $stmt_update_semaforica->bind_param('si', $new_status, $id_semaforica);
                    $stmt_update_semaforica->execute(); // Executa a atualização
                    $stmt_update_semaforica->close();
                }
            }
            $stmt_get_semaforica->close();
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


        // 1. Busca o tipo da manutenção e os IDs dos lacres distribuídos ANTES de atualizar
        $stmt_info = $conn->prepare("SELECT tipo_manutencao, id_controle_lacres_dist FROM manutencoes WHERE id_manutencao = ?");
        $stmt_info->bind_param('i', $id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $manutencao_info = $result_info->fetch_assoc();
        $stmt_info->close();

        if (!$manutencao_info) {
            throw new Exception('Manutenção não encontrada.');
        }

        // 2. Se for do tipo "afixar" e tiver lacres vinculados, atualiza a tabela de lacres
        if ($manutencao_info['tipo_manutencao'] === 'afixar' && !empty($manutencao_info['id_controle_lacres_dist'])) {

            $id_usuario_afixou = $tecnicos[0];

            $sql_update_lacres = "UPDATE controle_lacres SET
                                num_lacre = num_lacre_distribuido,
                                lacre_rompido = 0,
                                lacre_afixado = 1,
                                lacre_distribuido = 0,
                                dt_reporta_psie = NULL,
                                dt_fixacao = ?,
                                acao = 'Afixado',
                                id_usuario_afixou = ?,
                                obs_lacre = NULL
                            WHERE id_controle_lacres = ?";
            $stmt_lacres = $conn->prepare($sql_update_lacres);

            $ids_lacres_a_afixar = explode(',', $manutencao_info['id_controle_lacres_dist']);

            foreach ($ids_lacres_a_afixar as $id_lacre) {
                $id_lacre_int = (int)$id_lacre;
                // Usa a data de conclusão do reparo informada no formulário
                $stmt_lacres->bind_param("sii", $fim_reparo, $id_usuario_afixou, $id_lacre_int);
                if (!$stmt_lacres->execute()) {
                    throw new Exception('Falha ao atualizar o status de um dos lacres afixados.');
                }
            }
            $stmt_lacres->close();
        }

        // 1. Atualiza a tabela 'manutencoes' 
        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ? WHERE id_manutencao = ?");
        $stmt->bind_param('ssissi', $reparo_finalizado, $materiais_utilizados, $rompimento_lacre, $numero_lacre, $info_rompimento, $id);

        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a manutenção principal.');
        }
        $stmt->close();

        // 2. Verifica se é uma ocorrência semafórica para sincronizar a conclusão.
        $stmt_get_semaforica = $conn->prepare("SELECT id_ocorrencia_semaforica FROM manutencoes WHERE id_manutencao = ?");
        $stmt_get_semaforica->bind_param('i', $id);
        $stmt_get_semaforica->execute();
        $result_semaforica = $stmt_get_semaforica->get_result();
        if ($result_semaforica->num_rows > 0) {
            $row = $result_semaforica->fetch_assoc();
            $id_semaforica = $row['id_ocorrencia_semaforica'];
            // Se houver um ID, atualiza a tabela original com status 'concluido' e a descrição do reparo.
            if ($id_semaforica) {
                $stmt_update_semaforica = $conn->prepare("UPDATE ocorrencia_semaforica SET status = 'concluido', descricao_reparo = ? WHERE id = ?");
                $stmt_update_semaforica->bind_param('si', $reparo_finalizado, $id_semaforica);
                $stmt_update_semaforica->execute();
                $stmt_update_semaforica->close();
            }
        }
        $stmt_get_semaforica->close();

        // 3. Limpa e reinsere técnicos/veículos 
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

            $sql = "UPDATE ocorrencia_processamento SET descricao = ?, reparo = ? WHERE id_ocorrencia_processamento = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssi', $ocorrencia, $reparo_finalizado, $id);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao editar a ocorrência de processamento.');
            }
            $stmt->close();
        } else {
            // 1. Atualiza a tabela 'manutencoes' como sempre fez (lógica original)
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

            //    Verifica se a manutenção editada está ligada a uma ocorrência semafórica.
            $stmt_get_semaforica = $conn->prepare("SELECT id_ocorrencia_semaforica FROM manutencoes WHERE id_manutencao = ?");
            $stmt_get_semaforica->bind_param('i', $id);
            $stmt_get_semaforica->execute();
            $result_semaforica = $stmt_get_semaforica->get_result();

            if ($result_semaforica->num_rows > 0) {
                $row = $result_semaforica->fetch_assoc();
                $id_semaforica = $row['id_ocorrencia_semaforica'];
                // Se houver um ID, atualiza a descrição do problema na tabela original.
                if ($id_semaforica) {
                    $stmt_update_semaforica = $conn->prepare("UPDATE ocorrencia_semaforica SET descricao_problema = ? WHERE id = ?");
                    $stmt_update_semaforica->bind_param('si', $ocorrencia, $id_semaforica);
                    $stmt_update_semaforica->execute();
                    $stmt_update_semaforica->close();
                }
            }
            $stmt_get_semaforica->close();
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
    } elseif ($action === 'concluir_instalacao') {
        $id_manutencao = $id; // $id já contém o id_manutencao
        $is_final = $input['is_final'] ?? false;
        $status_reparo = $input['status_reparo'] ?? 'em andamento';

        // Dados da instalação
        $dt_base = $input['dt_base'] ?? null;
        $dt_laco = $input['dt_laco'] ?? null;
        $data_infra = $input['data_infra'] ?? null;
        $dt_energia = $input['dt_energia'] ?? null;
        $data_provedor = $input['data_provedor'] ?? null;

        // Monta a query de UPDATE
        $updates = ["status_reparo = ?"];
        $params = [$status_reparo];
        $types = "s";

        if ($dt_base !== null) {
            $updates[] = "inst_base = ?";
            $updates[] = "dt_base = ?";
            $params[] = empty($dt_base) ? 0 : 1;
            $params[] = $dt_base;
            $types .= "is";
        }
        if ($dt_laco !== null) {
            $updates[] = "inst_laco = ?";
            $updates[] = "dt_laco = ?";
            $params[] = empty($dt_laco) ? 0 : 1;
            $params[] = $dt_laco;
            $types .= "is";
        }
        if ($data_infra !== null) {
            $updates[] = "inst_infra = ?";
            $updates[] = "data_infra = ?";
            $params[] = empty($data_infra) ? 0 : 1;
            $params[] = $data_infra;
            $types .= "is";
        }
        if ($dt_energia !== null) {
            $updates[] = "inst_energia = ?";
            $updates[] = "dt_energia = ?";
            $params[] = empty($dt_energia) ? 0 : 1;
            $params[] = $dt_energia;
            $types .= "is";
        }
        if ($data_provedor !== null) {
            $updates[] = "inst_prov = ?";
            $updates[] = "data_provedor = ?";
            $params[] = empty($data_provedor) ? 0 : 1;
            $params[] = $data_provedor;
            $types .= "is";
        }

        // Se a conclusão for final e a data de infraestrutura estiver presente, atualiza o equipamento
        if ($is_final && !empty($data_infra)) {
            $stmt_get_equip = $conn->prepare("SELECT id_equipamento FROM manutencoes WHERE id_manutencao = ?");
            $stmt_get_equip->bind_param("i", $id_manutencao);
            $stmt_get_equip->execute();
            $equip_info = $stmt_get_equip->get_result()->fetch_assoc();
            $stmt_get_equip->close();

            if ($equip_info) {
                $id_equipamento = $equip_info['id_equipamento'];
                $stmt_update_equip = $conn->prepare("UPDATE equipamentos SET data_instalacao = ? WHERE id_equipamento = ?");
                $stmt_update_equip->bind_param("si", $data_infra, $id_equipamento);
                $stmt_update_equip->execute();
                $stmt_update_equip->close();
            }
        }

        $sql = "UPDATE manutencoes SET " . implode(", ", $updates) . " WHERE id_manutencao = ?";
        $params[] = $id_manutencao;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar a instalação.');
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Instalação atualizada com sucesso.']);
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
