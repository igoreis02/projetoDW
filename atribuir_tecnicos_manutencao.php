<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['idsManutencao']) || empty($input['idsTecnicos']) || empty($input['idsVeiculos']) || empty($input['dataInicio']) || empty($input['dataFim'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

$idsManutencao = $input['idsManutencao'];
$idsTecnicos = $input['idsTecnicos'];
$idsVeiculos = $input['idsVeiculos'];
$dataInicio = $input['dataInicio'];
$dataFim = $input['dataFim'];

$conn->begin_transaction();

try {
    // 1. Atualizar o status_reparo na tabela manutencoes para 'em andamento'
    $status_em_andamento = 'em andamento';
    $stmt_update_status = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
    if (!$stmt_update_status) {
        throw new Exception('Erro ao preparar a declaração de atualização de status: ' . $conn->error);
    }
    $stmt_update_status->bind_param("si", $status_em_andamento, $id_manutencao_update);

    foreach ($idsManutencao as $id_manutencao_update) {
        $stmt_update_status->execute();
    }
    $stmt_update_status->close();

    // 2. Preparar as instruções para UPDATE e INSERT
    $status_tecnico_pendente = 'pendente';
    $stmt_update_atribuicao = $conn->prepare("UPDATE manutencoes_tecnicos SET id_tecnico = ?, id_veiculo = ?, inicio_reparoTec = ?, fim_reparoT = ?, status_tecnico = ? WHERE id_manutencao = ?");
    $stmt_update_atribuicao->bind_param("iisssi", $id_tecnico_update, $id_veiculo_update, $dataInicio, $dataFim, $status_tecnico_pendente, $id_manutencao_update_atribuicao);

    $stmt_insert_atribuicao = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt_insert_atribuicao) {
        throw new Exception('Erro ao preparar a declaração de inserção: ' . $conn->error);
    }
    $stmt_insert_atribuicao->bind_param("iiisss", $id_manutencao_insert, $id_tecnico_insert, $id_veiculo_insert, $dataInicio, $dataFim, $status_tecnico_pendente);

    // 3. Realizar o UPSERT (UPDATE ou INSERT)
    foreach ($idsManutencao as $id_manutencao) {
        foreach ($idsTecnicos as $id_tecnico) {
            foreach ($idsVeiculos as $id_veiculo) {
                // Verificar se já existe um registro para a manutenção
                $stmt_check = $conn->prepare("SELECT id_manutencao FROM manutencoes_tecnicos WHERE id_manutencao = ?");
                $stmt_check->bind_param("i", $id_manutencao);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    // Se o registro existe, fazemos um UPDATE
                    $id_manutencao_update_atribuicao = $id_manutencao;
                    $id_tecnico_update = $id_tecnico;
                    $id_veiculo_update = $id_veiculo;
                    $stmt_update_atribuicao->execute();
                } else {
                    // Se o registro não existe, fazemos um INSERT
                    $id_manutencao_insert = $id_manutencao;
                    $id_tecnico_insert = $id_tecnico;
                    $id_veiculo_insert = $id_veiculo;
                    $stmt_insert_atribuicao->execute();
                }
                $stmt_check->close();
            }
        }
    }

    $stmt_update_atribuicao->close();
    $stmt_insert_atribuicao->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Manutenção atribuída com sucesso.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>