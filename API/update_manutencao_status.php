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
        // --- LÓGICA PARA INSTALAÇÕES ---
        $dt_base = $data['dt_base'] ?? null;
        $dt_laco = $data['dt_laco'] ?? null;
        $data_infra = $data['data_infra'] ?? null;
        $dt_energia = $data['dt_energia'] ?? null;
        
        // Constrói a query de UPDATE dinamicamente
        $updates = []; $params = []; $types = "";
        if ($dt_base !== null) { $updates[] = "inst_base = ?"; $updates[] = "dt_base = ?"; $params[] = empty($dt_base) ? 0 : 1; $params[] = $dt_base; $types .= "is"; }
        if ($dt_laco !== null) { $updates[] = "inst_laco = ?"; $updates[] = "dt_laco = ?"; $params[] = empty($dt_laco) ? 0 : 1; $params[] = $dt_laco; $types .= "is"; }
        if ($data_infra !== null) { $updates[] = "inst_infra = ?"; $updates[] = "data_infra = ?"; $params[] = empty($data_infra) ? 0 : 1; $params[] = $data_infra; $types .= "is"; }
        if ($dt_energia !== null) { $updates[] = "inst_energia = ?"; $updates[] = "dt_energia = ?"; $params[] = empty($dt_energia) ? 0 : 1; $params[] = $dt_energia; $types .= "is"; }
        
        if (!empty($updates) || $is_final) {
            $updates[] = "status_reparo = ?"; 
            $params[] = $status_reparo; 
            $types .= "s";
            
            $sql = "UPDATE manutencoes SET " . implode(", ", $updates) . " WHERE id_manutencao = ?";
            $params[] = $id_manutencao; $types .= "i";
            
            $stmt_main = $conn->prepare($sql);
            $stmt_main->bind_param($types, ...$params);
            if (!$stmt_main->execute()) {
                throw new Exception('Erro ao atualizar a instalação: ' . $stmt_main->error);
            }
        }

        

        // --- CRIA OCORRÊNCIA DE PROCESSAMENTO e atualiza data instalação equipamento ---
        if ($is_final) {

            if (!empty($data_infra)) {
                // Primeiro, pega o id_equipamento a partir da manutenção
                $stmt_get_equip_id = $conn->prepare("SELECT id_equipamento FROM manutencoes WHERE id_manutencao = ?");
                $stmt_get_equip_id->bind_param("i", $id_manutencao);
                $stmt_get_equip_id->execute();
                $result_equip = $stmt_get_equip_id->get_result();
                if ($equip_row = $result_equip->fetch_assoc()) {
                    $id_equipamento_instalado = $equip_row['id_equipamento'];

                    // Agora, atualiza a tabela de equipamentos
                    $sql_update_equip = "UPDATE equipamentos SET data_instalacao = ? WHERE id_equipamento = ?";
                    $stmt_update_equip = $conn->prepare($sql_update_equip);
                    $stmt_update_equip->bind_param("si", $data_infra, $id_equipamento_instalado);
                    if (!$stmt_update_equip->execute()) {
                        throw new Exception('Erro ao atualizar a data de instalação do equipamento: ' . $stmt_update_equip->error);
                    }
                    $stmt_update_equip->close();
                }
                $stmt_get_equip_id->close();
            }
            $tipo_equip = $data['tipo_equip'] ?? '';
            $passos_necessarios = ['laco', 'base', 'infra', 'energia'];
            if (strpos($tipo_equip, 'CCO') !== false) {
                $passos_necessarios = ['infra', 'energia'];
            } else if (strpos($tipo_equip, 'DOME') !== false || strpos($tipo_equip, 'VÍDEO MONITORAMENTO') !== false || strpos($tipo_equip, 'LAP') !== false) {
                $passos_necessarios = ['base', 'infra', 'energia'];
            }
            
            $all_filled = true;
            foreach ($passos_necessarios as $passo) {
                $key = ($passo === 'infra') ? 'data_infra' : "dt_{$passo}";
                if (empty($data[$key])) {
                    $all_filled = false;
                    break;
                }
            }

            if ($all_filled) {
                // Pega o ID do equipamento a partir do ID da manutenção
                $stmt_equip = $conn->prepare("SELECT id_equipamento, id_cidade FROM manutencoes WHERE id_manutencao = ?");
                $stmt_equip->bind_param("i", $id_manutencao);
                $stmt_equip->execute();
                $manutencao_info = $stmt_equip->get_result()->fetch_assoc();
                $id_equipamento = $manutencao_info['id_equipamento'];
                $id_cidade = $manutencao_info['id_cidade'];
                $stmt_equip->close();

                $dt_ocorrencia = date('Y-m-d H:i:s');
                $descricao = 'contato com o provedor para instalação';
                
                $sql_insert_proc = "INSERT INTO ocorrencia_processamento 
                                    (id_equipamento, id_usuario_registro, dt_ocorrencia, tipo_ocorrencia, descricao, status, id_cidade) 
                                    VALUES (?, ?, ?, 'instalação', ?, 'pendente', ?)";  
                
                $stmt_proc = $conn->prepare($sql_insert_proc);
                $stmt_proc->bind_param("isssi", $id_equipamento, $id_usuario_logado, $dt_ocorrencia, $descricao, $id_cidade);
                
                if (!$stmt_proc->execute()) {
                    throw new Exception('Erro ao criar ocorrência no processamento: ' . $stmt_proc->error);
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
            $id_equipamento = $data['id_equipamento'] ?? null;
            
            $sql .= ", fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, motivo_devolucao = NULL, tempo_reparo = TIMEDIFF(NOW(), inicio_reparo)";
            $types .= "ss";
            $params[] = $reparo_finalizado; $params[] = $materiais_utilizados;

            $sql .= ", rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ?, data_rompimento = ?";
            $types .= "isss";
            $params[] = $rompimento_lacre; $params[] = $numero_lacre; $params[] = $info_rompimento; $params[] = $data_rompimento;
            
            if ($rompimento_lacre && $id_equipamento && $info_rompimento && $numero_lacre && $data_rompimento) {
                $sql_lacre = "UPDATE controle_lacres SET 
                                lacre_rompido = 1,
                                dt_rompimento = ?,
                                num_lacre_rompido = num_lacre,
                                num_lacre = NULL,
                                lacre_afixado = 0
                              WHERE id_equipamento = ? AND local_lacre = ? AND num_lacre = ? AND lacre_afixado = 1";
                
                $stmt_lacre = $conn->prepare($sql_lacre);
                $stmt_lacre->bind_param("siss", $data_rompimento, $id_equipamento, $info_rompimento, $numero_lacre);
                if (!$stmt_lacre->execute()) {
                    throw new Exception("Erro ao atualizar o controle de lacres: " . $stmt_lacre->error);
                }
                $stmt_lacre->close();
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
    }
    if ($is_final) {
        $sql_update_tecnicos = "UPDATE manutencoes_tecnicos SET status_tecnico = 'concluido' WHERE id_manutencao = ?";
        $stmt_tecnicos = $conn->prepare($sql_update_tecnicos);
        $stmt_tecnicos->bind_param("i", $id_manutencao);
        if(!$stmt_tecnicos->execute()){
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
?>