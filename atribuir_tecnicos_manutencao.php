<?php
header('Content-Type: application/json');

require_once 'conexao_bd.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validação dos dados recebidos
if (
    !isset($input['idsManutencao']) || !is_array($input['idsManutencao']) || empty($input['idsManutencao']) ||
    !isset($input['idsTecnicos']) || !is_array($input['idsTecnicos']) || empty($input['idsTecnicos']) ||
    !isset($input['idsVeiculos']) || !is_array($input['idsVeiculos']) || empty($input['idsVeiculos']) ||
    !isset($input['dataInicio']) || !isset($input['dataFim'])
) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos ou inválidos.']);
    $conn->close();
    exit();
}

$idsManutencao = $input['idsManutencao'];
$idsTecnicos = $input['idsTecnicos'];
$idsVeiculos = $input['idsVeiculos'];
$dataInicio = $input['dataInicio'];
$dataFim = $input['dataFim'];
$status_tecnico_pendente = 'pendente';

$conn->begin_transaction();

try {
    // 1. Prepara a query para atualizar o status de cada manutenção principal
    $stmt_update_manutencao = $conn->prepare("UPDATE manutencoes SET status_reparo = 'em andamento' WHERE id_manutencao = ?");
    if (!$stmt_update_manutencao) {
        throw new Exception('Erro ao preparar a declaração de atualização de status: ' . $conn->error);
    }

    // 2. Prepara a query para deletar as atribuições antigas, para evitar duplicatas
    $stmt_delete_atribuicoes = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
    if (!$stmt_delete_atribuicoes) {
        throw new Exception('Erro ao preparar a declaração de exclusão: ' . $conn->error);
    }
    
    // 3. Prepara uma única query para inserir as novas atribuições
    $stmt_insert_atribuicao = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt_insert_atribuicao) {
        throw new Exception('Erro ao preparar a declaração de inserção: ' . $conn->error);
    }

    $veiculo_count = count($idsVeiculos);

    // Itera sobre cada manutenção selecionada
    foreach ($idsManutencao as $id_manutencao) {
        // Atualiza a manutenção principal com as datas
        $stmt_update_manutencao->bind_param("i", $id_manutencao);
        $stmt_update_manutencao->execute();

        // Deleta as atribuições antigas para evitar duplicatas e conflitos
        $stmt_delete_atribuicoes->bind_param("i", $id_manutencao);
        $stmt_delete_atribuicoes->execute();

        // Insere as novas atribuições de técnicos e veículos
        $i = 0; // contador para os veículos
        foreach ($idsTecnicos as $id_tecnico) {
            $id_veiculo = $idsVeiculos[$i % $veiculo_count]; // Atribui um veículo da lista de forma circular
            
            // Insere uma linha para cada combinação de técnico e veículo
            $stmt_insert_atribuicao->bind_param("iiisss", $id_manutencao, $id_tecnico, $id_veiculo, $dataInicio, $dataFim, $status_tecnico_pendente);
            $stmt_insert_atribuicao->execute();
            
            $i++;
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Manutenção(ões) atribuída(s) com sucesso.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atribuir a manutenção: ' . $e->getMessage()]);
}

// Fecha as declarações preparadas
if (isset($stmt_update_manutencao)) {
    $stmt_update_manutencao->close();
}
if (isset($stmt_delete_atribuicoes)) {
    $stmt_delete_atribuicoes->close();
}
if (isset($stmt_insert_atribuicao)) {
    $stmt_insert_atribuicao->close();
}
$conn->close();
?>