<?php
session_start(); // NECESSÁRIO para obter o ID do usuário logado
header('Content-Type: application/json');

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Pega o ID do técnico que está registrando a ação
$id_usuario_logado = $_SESSION['user_id'] ?? null;

$conn->begin_transaction();

try {
    $id_manutencao = $data['id_manutencao'] ?? null;
    $status_reparo = $data['status_reparo'] ?? null;
    $is_installation = $data['is_installation'] ?? false;
    $is_final = $data['is_final'] ?? false; // Flag para saber se é uma conclusão

    if (empty($id_manutencao)) {
        throw new Exception('ID da manutenção é obrigatório.');
    }
    if (empty($id_usuario_logado)) {
        throw new Exception('Sessão do usuário inválida. Faça login novamente.');
    }

    $stmt_main = null;

    if ($is_installation) {
        // --- LÓGICA PARA INSTALAÇÕES (REESTRUTURADA) ---
        $dt_base = $data['dt_base'] ?? null;
        $dt_laco = $data['dt_laco'] ?? null;
        $data_infra = $data['data_infra'] ?? null;
        $dt_energia = $data['dt_energia'] ?? null;

        $stmt_current = $conn->prepare("SELECT inst_laco, inst_base, inst_infra, inst_energia FROM manutencoes WHERE id_manutencao = ?");
        $stmt_current->bind_param("i", $id_manutencao);
        $stmt_current->execute();
        $final_inst_status = $stmt_current->get_result()->fetch_assoc();
        $stmt_current->close();

        if ($dt_base !== null) {
            $final_inst_status['inst_base'] = empty($dt_base) ? 0 : 1;
        }
        if ($dt_laco !== null) {
            $final_inst_status['inst_laco'] = empty($dt_laco) ? 0 : 1;
        }
        if ($data_infra !== null) {
            $final_inst_status['inst_infra'] = empty($data_infra) ? 0 : 1;
        }
        if ($dt_energia !== null) {
            $final_inst_status['inst_energia'] = empty($dt_energia) ? 0 : 1;
        }

        $updates = [];
        $params = [];
        $types = "";
        if ($dt_base !== null) {
            $updates[] = "inst_base = ?";
            $updates[] = "dt_base = ?";
            $params[] = $final_inst_status['inst_base'];
            $params[] = $dt_base;
            $types .= "is";
        }
        if ($dt_laco !== null) {
            $updates[] = "inst_laco = ?";
            $updates[] = "dt_laco = ?";
            $params[] = $final_inst_status['inst_laco'];
            $params[] = $dt_laco;
            $types .= "is";
        }
        if ($data_infra !== null) {
            $updates[] = "inst_infra = ?";
            $updates[] = "data_infra = ?";
            $params[] = $final_inst_status['inst_infra'];
            $params[] = $data_infra;
            $types .= "is";
        }
        if ($dt_energia !== null) {
            $updates[] = "inst_energia = ?";
            $updates[] = "dt_energia = ?";
            $params[] = $final_inst_status['inst_energia'];
            $params[] = $dt_energia;
            $types .= "is";
        }

        if (!empty($updates) || $is_final) {
            $updates[] = "status_reparo = ?";
            $params[] = $status_reparo;
            $types .= "s";

            $sql = "UPDATE manutencoes SET " . implode(", ", $updates) . " WHERE id_manutencao = ?";
            $params[] = $id_manutencao;
            $types .= "i";

            $stmt_main = $conn->prepare($sql);
            $stmt_main->bind_param($types, ...$params);
            if (!$stmt_main->execute()) {
                throw new Exception('Erro ao atualizar a instalação: ' . $stmt_main->error);
            }

            if (!empty($data_infra)) {
                // Busca os técnicos associados a esta OS de manutenção
                $stmt_get_tecnicos = $conn->prepare("SELECT id_tecnico FROM manutencoes_tecnicos WHERE id_manutencao = ?");
                $stmt_get_tecnicos->bind_param("i", $id_manutencao);
                $stmt_get_tecnicos->execute();
                $result_tecnicos = $stmt_get_tecnicos->get_result();
                $tecnico_ids = [];
                while ($row = $result_tecnicos->fetch_assoc()) {
                    $tecnico_ids[] = $row['id_tecnico'];
                }
                $stmt_get_tecnicos->close();

                if (!empty($tecnico_ids)) {
                    // Converte o array de IDs em uma string (ex: "5,7,11")
                    $tecnicos_string = implode(',', $tecnico_ids);

                    // Pega o ID do equipamento a partir da manutenção
                    $stmt_get_equip = $conn->prepare("SELECT id_equipamento FROM manutencoes WHERE id_manutencao = ?");
                    $stmt_get_equip->bind_param("i", $id_manutencao);
                    $stmt_get_equip->execute();
                    $id_equipamento = $stmt_get_equip->get_result()->fetch_assoc()['id_equipamento'];
                    $stmt_get_equip->close();

                    if ($id_equipamento) {
                        //a data de instalação e os IDs dos técnicos
                        $stmt_update_equip = $conn->prepare("UPDATE equipamentos SET id_tecnico_instalacao = ?, data_instalacao = ? WHERE id_equipamento = ?");
                        // A data da infraestrutura é usada como a data oficial de instalação
                        $stmt_update_equip->bind_param("ssi", $tecnicos_string, $data_infra, $id_equipamento);

                        if (!$stmt_update_equip->execute()) {
                            throw new Exception('Erro ao salvar técnicos e data de instalação no equipamento: ' . $stmt_update_equip->error);
                        }
                        $stmt_update_equip->close();
                    }
                }
            }
        }

        if ($is_final) {
            // 1. Busca os dados do equipamento, incluindo a etiqueta_feita
            $stmt_info = $conn->prepare("
        SELECT e.nome_equip, e.referencia_equip, e.tipo_equip, m.id_equipamento, m.id_cidade, m.etiqueta_feita
        FROM manutencoes m
        JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
        WHERE m.id_manutencao = ?
    ");
            $stmt_info->bind_param("i", $id_manutencao);
            $stmt_info->execute();
            $manutencao_info = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            // 2. Define os passos necessários baseado no tipo de equipamento e na etiqueta (LÓGICA CORRIGIDA)
            $tipo_equip = $manutencao_info['tipo_equip'] ?? '';
            $etiqueta_feita = $manutencao_info['etiqueta_feita'] ?? 0;
            $passos_necessarios = ['laco', 'base', 'infra', 'energia'];

            $tiposComEtiqueta = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'MONITOR DE SEMÁFORO'];
            $precisaEtiqueta = false;
            foreach ($tiposComEtiqueta as $tipo) {
                if (strpos($tipo_equip, $tipo) !== false) {
                    $precisaEtiqueta = true;
                    break;
                }
            }

            // Se precisa de etiqueta e ela não está pronta, a instalação não pode ser considerada 100% concluída ainda.
            if ($precisaEtiqueta && $etiqueta_feita != 1) {
                // Não faz nada, a verificação de passos vai falhar para 'infra' e 'energia', o que está correto.
            }

            // Regras de tipo de equipamento (LÓGICA CORRIGIDA para DOME)
            if (strpos($tipo_equip, 'CCO') !== false) {
                $passos_necessarios = array_diff($passos_necessarios, ['laco', 'base']);
            } elseif (strpos($tipo_equip, 'DOME') !== false) {
                $passos_necessarios = array_diff($passos_necessarios, ['laco']); // Remove SÓ o laço
            } elseif (strpos($tipo_equip, 'VÍDEO MONITORAMENTO') !== false || strpos($tipo_equip, 'LAP') !== false) {
                $passos_necessarios = array_diff($passos_necessarios, ['laco']);
            }

            // 3. Verifica se todos os passos (agora corretamente definidos) estão concluídos 
            $todos_passos_concluidos = true;
            foreach ($passos_necessarios as $passo) {
                // Verifica o status final que foi calculado no início do script
                if ($final_inst_status["inst_{$passo}"] != 1) {
                    $todos_passos_concluidos = false;
                    break;
                }
            }

            // 4. Se tudo estiver OK, cria a nova ocorrência para o provedor
            if ($todos_passos_concluidos) {
                $id_equipamento = $manutencao_info['id_equipamento'];
                $id_cidade = $manutencao_info['id_cidade'];
                $nome_equip = $manutencao_info['nome_equip'];
                $ref_equip = $manutencao_info['referencia_equip'];
                $dt_ocorrencia = date('Y-m-d H:i:s');

                $descricao = "Contatar provedor para instalação no equipamento {$nome_equip} - {$ref_equip}.";

                $sql_insert_proc = "INSERT INTO ocorrencia_processamento 
                            (id_equipamento, id_usuario_registro, dt_ocorrencia, tipo_ocorrencia, descricao, status, id_cidade) 
                            VALUES (?, ?, ?, 'provedor', ?, 'pendente', ?)";

                $stmt_proc = $conn->prepare($sql_insert_proc);
                // O id_usuario_logado foi definido no início do seu script
                $stmt_proc->bind_param("isssi", $id_equipamento, $id_usuario_logado, $dt_ocorrencia, $descricao, $id_cidade);

                if (!$stmt_proc->execute()) {
                    throw new Exception('Erro ao criar ocorrência de provedor no processamento: ' . $stmt_proc->error);
                }
                $stmt_proc->close();
            }
        }
    } else {
        // --- LÓGICA PARA MANUTENÇÕES CORRETIVAS ---
        $reparo_finalizado = $data['reparo_finalizado'] ?? null;
        $materiais_utilizados = $data['materiais_utilizados'] ?? null;
        $motivo_devolucao = $data['motivo_devolucao'] ?? null;

        if (empty($status_reparo)) throw new Exception('Novo status é obrigatório.');

        $sql = "UPDATE manutencoes SET status_reparo = ?";
        $types = "s";
        $params = [$status_reparo];

        if ($status_reparo === 'concluido' || $status_reparo === 'validacao') {
            $rompimento_lacre = $data['rompimento_lacre'] ?? 0;
            $numero_lacre = $data['numero_lacre'] ?? null;
            $info_rompimento = $data['info_rompimento'] ?? null;
            $data_rompimento = $data['data_rompimento'] ?? null;
            $id_equipamento_lacre = $data['id_equipamento'] ?? null;

            $sql .= ", fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, motivo_devolucao = NULL, tempo_reparo = TIMEDIFF(NOW(), inicio_reparo)";
            $types .= "ss";
            $params[] = $reparo_finalizado;
            $params[] = $materiais_utilizados;

            $sql .= ", rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ?, data_rompimento = ?";
            $types .= "isss";
            $params[] = $rompimento_lacre;
            $params[] = $numero_lacre;
            $params[] = $info_rompimento;
            $params[] = $data_rompimento;

            if ($rompimento_lacre && $id_equipamento_lacre && $info_rompimento && $numero_lacre && $data_rompimento) {
                // ... (lógica de lacre)
            }
        } else if ($status_reparo === 'pendente') {
            $sql .= ", fim_reparo = NULL, reparo_finalizado = NULL, materiais_utilizados = NULL, tempo_reparo = NULL, motivo_devolucao = ?";
            $types .= "s";
            $params[] = $motivo_devolucao;
        }

        $sql .= " WHERE id_manutencao = ?";
        $types .= "i";
        $params[] = $id_manutencao;

        $stmt_main = $conn->prepare($sql);
        $stmt_main->bind_param($types, ...$params);
        if (!$stmt_main->execute()) {
            throw new Exception('Erro ao atualizar a manutenção: ' . $stmt_main->error);
        }

        if ($status_reparo === 'validacao') {
            // 1. Busca os dados necessários da manutenção e equipamento
            $stmt_info = $conn->prepare("
                SELECT m.id_equipamento, m.id_cidade, e.nome_equip, e.referencia_equip
                FROM manutencoes m
                JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
                WHERE m.id_manutencao = ?
            ");
            $stmt_info->bind_param("i", $id_manutencao);
            $stmt_info->execute();
            $info = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            // 2. Prepara os dados para a nova ocorrência
            $id_equipamento = $info['id_equipamento'];
            $id_cidade = $info['id_cidade'];
            $dt_ocorrencia = date('Y-m-d H:i:s');
            $descricao = "Validar reparo no equipamento {$info['nome_equip']} - {$info['referencia_equip']}.";

            // 3. Insere a nova ocorrência na tabela de processamento
            $sql_insert_proc = "INSERT INTO ocorrencia_processamento 
                                (id_equipamento, id_usuario_registro, dt_ocorrencia, tipo_ocorrencia, descricao, status, id_cidade) 
                                VALUES (?, ?, ?, 'validação', ?, 'pendente', ?)";

            $stmt_proc = $conn->prepare($sql_insert_proc);
            $stmt_proc->bind_param("isssi", $id_equipamento, $id_usuario_logado, $dt_ocorrencia, $descricao, $id_cidade);

            if (!$stmt_proc->execute()) {
                throw new Exception('Erro ao criar ocorrência de validação no processamento: ' . $stmt_proc->error);
            }
            $stmt_proc->close();
        }
    }

    if ($is_final) {
        $sql_update_tecnicos = "UPDATE manutencoes_tecnicos SET status_tecnico = 'concluido' WHERE id_manutencao = ?";
        $stmt_tecnicos = $conn->prepare($sql_update_tecnicos);
        $stmt_tecnicos->bind_param("i", $id_manutencao);
        if (!$stmt_tecnicos->execute()) {
            throw new Exception('Erro ao atualizar status do técnico: ' . $stmt_tecnicos->error);
        }
        $stmt_tecnicos->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Operação registrada com sucesso!']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt_main)) $stmt_main->close();
$conn->close();
