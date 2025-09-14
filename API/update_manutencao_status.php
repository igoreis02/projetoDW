<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$conn->begin_transaction();

try {
    $id_manutencao = $data['id_manutencao'] ?? null;
    $status_reparo = $data['status_reparo'] ?? null;
    $is_installation = $data['is_installation'] ?? false;

    if (empty($id_manutencao)) {
        throw new Exception('ID da manutenção é obrigatório.');
    }

    $stmt_main = null;

    if ($is_installation) {
        // --- LÓGICA PARA INSTALAÇÕES ---
        // (Esta parte permanece a mesma)
        $dt_base = $data['dt_base'] ?? null;
        $dt_laco = $data['dt_laco'] ?? null;
        $data_infra = $data['data_infra'] ?? null;
        $dt_energia = $data['dt_energia'] ?? null;
        $tipo_equip = $data['tipo_equip'] ?? null;
        $id_cidade = $data['id_cidade'] ?? null;

        $stmt_check = $conn->prepare("SELECT inst_base, inst_laco, inst_infra, inst_energia FROM manutencoes WHERE id_manutencao = ?");
        $stmt_check->bind_param("i", $id_manutencao);
        $stmt_check->execute();
        $current_status = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        $updates = []; $params = []; $types = "";
        if ($dt_base) { $updates[] = "inst_base = 1"; $updates[] = "dt_base = ?"; $params[] = $dt_base; $types .= "s"; }
        if ($dt_laco) { $updates[] = "inst_laco = 1"; $updates[] = "dt_laco = ?"; $params[] = $dt_laco; $types .= "s"; }
        if ($data_infra) { $updates[] = "inst_infra = 1"; $updates[] = "data_infra = ?"; $params[] = $data_infra; $types .= "s"; }
        if ($dt_energia) { $updates[] = "inst_energia = 1"; $updates[] = "dt_energia = ?"; $params[] = $dt_energia; $types .= "s"; }
        
        if (!empty($updates)) {
            $completed_count = 0;
            $is_dome_or_cco = ($tipo_equip === 'DOME' || $tipo_equip === 'CCO');
            $total_steps = $is_dome_or_cco ? 3 : 4;
            $completed_count += ($current_status['inst_base'] || $dt_base) ? 1 : 0;
            $completed_count += ($current_status['inst_infra'] || $data_infra) ? 1 : 0;
            $completed_count += ($current_status['inst_energia'] || $dt_energia) ? 1 : 0;
            if (!$is_dome_or_cco) { $completed_count += ($current_status['inst_laco'] || $dt_laco) ? 1 : 0; }

            if ($completed_count >= $total_steps && !empty($id_cidade)) {
                $stmt_prov = $conn->prepare("SELECT id_provedor FROM provedor WHERE id_cidade = ? LIMIT 1");
                $stmt_prov->bind_param("i", $id_cidade);
                $stmt_prov->execute();
                $result_prov = $stmt_prov->get_result();
                if ($prov_row = $result_prov->fetch_assoc()) {
                    $updates[] = "id_provedor = ?"; $params[] = $prov_row['id_provedor']; $types .= "i";
                }
                $stmt_prov->close();
            }
            
            $updates[] = "status_reparo = 'pendente'";
            $sql = "UPDATE manutencoes SET " . implode(", ", $updates) . " WHERE id_manutencao = ?";
            $params[] = $id_manutencao; $types .= "i";
            
            $stmt_main = $conn->prepare($sql);
            $stmt_main->bind_param($types, ...$params);
        }

    } else {
        // --- LÓGICA PARA MANUTENÇÕES CORRETIVAS (ATUALIZADA) ---
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
            
            // LÓGICA ADICIONAL PARA ATUALIZAR A TABELA 'controle_lacres'
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
    
    // Executa a query principal (seja instalação ou manutenção)
    if ($stmt_main && !$stmt_main->execute()) {
        throw new Exception('Erro ao atualizar a manutenção: ' . $stmt_main->error);
    }
    
    // Atualiza o status dos técnicos associados
    $sql_update_tecnicos = "UPDATE manutencoes_tecnicos SET status_tecnico = 'concluido' WHERE id_manutencao = ?";
    $stmt_tecnicos = $conn->prepare($sql_update_tecnicos);
    $stmt_tecnicos->bind_param("i", $id_manutencao);
    if(!$stmt_tecnicos->execute()){
         throw new Exception('Erro ao atualizar status do técnico: ' . $stmt_tecnicos->error);
    }
    $stmt_tecnicos->close();
    
    // Se tudo deu certo, confirma a transação
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Operação registrada com sucesso!']);

} catch (Exception $e) {
    // Se algo deu errado, reverte a transação
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($stmt_main)) $stmt_main->close();
$conn->close();
?>