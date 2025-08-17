<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id_manutencao = $data['id_manutencao'] ?? null;
$status_reparo = $data['status_reparo'] ?? null;
$is_installation = $data['is_installation'] ?? false;

if (empty($id_manutencao)) {
    echo json_encode(['success' => false, 'message' => 'ID da manutenção é obrigatório.']);
    exit();
}

// --- LÓGICA SEPARADA PARA INSTALAÇÕES ---
if ($is_installation) {
    // 1. Pega os dados de data
    $dt_base = $data['dt_base'] ?? null;
    $dt_laco = $data['dt_laco'] ?? null;
    $data_infra = $data['data_infra'] ?? null;
    $dt_energia = $data['dt_energia'] ?? null;

    // 2. Busca o estado atual dos flags 'inst_'
    $stmt_check = $conn->prepare("SELECT inst_base, inst_laco, inst_infra, inst_energia FROM manutencoes WHERE id_manutencao = ?");
    $stmt_check->bind_param("i", $id_manutencao);
    $stmt_check->execute();
    $current_status = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $updates = [];
    $params = [];
    $types = "";
    $completed_count = 0;

    // 3. Monta a query de UPDATE dinamicamente
    if ($dt_base) { $updates[] = "inst_base = 1"; $updates[] = "dt_base = ?"; $params[] = $dt_base; $types .= "s"; }
    if ($dt_laco) { $updates[] = "inst_laco = 1"; $updates[] = "dt_laco = ?"; $params[] = $dt_laco; $types .= "s"; }
    if ($data_infra) { $updates[] = "inst_infra = 1"; $updates[] = "data_infra = ?"; $params[] = $data_infra; $types .= "s"; }
    if ($dt_energia) { $updates[] = "inst_energia = 1"; $updates[] = "dt_energia = ?"; $params[] = $dt_energia; $types .= "s"; }

    if (empty($updates)) {
        echo json_encode(['success' => true, 'message' => 'Nenhuma nova etapa de instalação foi concluída.']);
        exit();
    }
    
    // 4. Calcula o novo status geral da manutenção
    $completed_count += ($current_status['inst_base'] || $dt_base) ? 1 : 0;
    $completed_count += ($current_status['inst_laco'] || $dt_laco) ? 1 : 0;
    $completed_count += ($current_status['inst_infra'] || $data_infra) ? 1 : 0;
    $completed_count += ($current_status['inst_energia'] || $dt_energia) ? 1 : 0;
    
    $novo_status_geral = ($completed_count == 4) ? 'pendente' : 'pendente';
    $updates[] = "status_reparo = ?";
    $params[] = $novo_status_geral;
    $types .= "s";

    $sql = "UPDATE manutencoes SET " . implode(", ", $updates) . " WHERE id_manutencao = ?";
    $params[] = $id_manutencao;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

} else { // --- LÓGICA ANTIGA PARA MANUTENÇÕES CORRETIVAS ---
    $reparo_finalizado = $data['reparo_finalizado'] ?? null;
    $materiais_utilizados = $data['materiais_utilizados'] ?? null;
    $motivo_devolucao = $data['motivo_devolucao'] ?? null;
    $rompimento_lacre = $data['rompimento_lacre'] ?? null;
    $numero_lacre = $data['numero_lacre'] ?? null;
    $info_rompimento = $data['info_rompimento'] ?? null;
    $data_rompimento = $data['data_rompimento'] ?? null;

    if (empty($status_reparo)) {
        echo json_encode(['success' => false, 'message' => 'Novo status é obrigatório.']);
        exit();
    }

    $sql = "UPDATE manutencoes SET status_reparo = ?";
    $types = "s";
    $params = [$status_reparo];

    if ($status_reparo === 'concluido') {
        $sql .= ", fim_reparo = NOW(), reparo_finalizado = ?, materiais_utilizados = ?, motivo_devolucao = NULL, tempo_reparo = TIMEDIFF(NOW(), inicio_reparo)";
        $types .= "ss";
        $params[] = $reparo_finalizado;
        $params[] = $materiais_utilizados;
        if ($rompimento_lacre) {
            $sql .= ", rompimento_lacre = ?, numero_lacre = ?, info_rompimento = ?, data_rompimento = ?";
            $types .= "isss";
            $params[] = $rompimento_lacre; $params[] = $numero_lacre; $params[] = $info_rompimento; $params[] = $data_rompimento;
        } else {
            $sql .= ", rompimento_lacre = 0, numero_lacre = NULL, info_rompimento = NULL, data_rompimento = NULL";
        }
    } else if ($status_reparo === 'pendente') {
        $sql .= ", fim_reparo = NULL, reparo_finalizado = NULL, materiais_utilizados = NULL, tempo_reparo = NULL, motivo_devolucao = ?, rompimento_lacre = NULL, numero_lacre = NULL, info_rompimento = NULL, data_rompimento = NULL";
        $types .= "s";
        $params[] = $motivo_devolucao;
    }

    $sql .= " WHERE id_manutencao = ?";
    $types .= "i";
    $params[] = $id_manutencao;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
}

// Executa a query principal (seja de instalação ou corretiva)
if ($stmt->execute()) {
    // Independentemente do tipo, remove a OS da lista do técnico
    $sql_update_tecnicos = "UPDATE manutencoes_tecnicos SET status_tecnico = 'concluido' WHERE id_manutencao = ?";
    $stmt_tecnicos = $conn->prepare($sql_update_tecnicos);
    $stmt_tecnicos->bind_param("i", $id_manutencao);
    $stmt_tecnicos->execute();
    $stmt_tecnicos->close();

    echo json_encode(['success' => true, 'message' => 'Operação registrada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar a manutenção: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>