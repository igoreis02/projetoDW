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
    // Apenas a coluna `status_reparo` é atualizada, conforme sua instrução.
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

    // 2. Inserir os dados de atribuição na tabela manutencoes_tecnicos
    // A consulta usa as colunas `inicio_reparoTec` e `fim_reparoTec`.
    $stmt_insert_atribuicao = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_insert_atribuicao) {
        throw new Exception('Erro ao preparar a declaração de inserção: ' . $conn->error);
    }
    $stmt_insert_atribuicao->bind_param("iiiss", $id_manutencao_insert, $id_tecnico_insert, $id_veiculo_insert, $dataInicio, $dataFim);

    foreach ($idsManutencao as $id_manutencao_insert) {
        foreach ($idsTecnicos as $id_tecnico_insert) {
            foreach ($idsVeiculos as $id_veiculo_insert) {
                $stmt_insert_atribuicao->execute();
            }
        }
    }
    $stmt_insert_atribuicao->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Manutenção atribuída com sucesso.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>