<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$id_usuario_logado = $_SESSION['user_id'];
require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['id_cidade']) || !isset($data['descricao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

$conn->begin_transaction();

try {
    // ETAPA 1: Salvar a ocorrência semafórica (código existente)
    $sql_ocorrencia = "INSERT INTO ocorrencia_semaforica (id_cidade, tipo, endereco, referencia, qtd, unidade, descricao_problema, geolocalizacao, observacao, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_ocorrencia = $conn->prepare($sql_ocorrencia);
    $tipo = !empty($data['tipo']) ? $data['tipo'] : null;
    $referencia = !empty($data['referencia']) ? $data['referencia'] : null;
    $geolocalizacao = !empty($data['geo']) ? $data['geo'] : null;
    $observacao = !empty($data['observacao']) ? $data['observacao'] : null;
    $stmt_ocorrencia->bind_param("isssissss", $data['id_cidade'], $tipo, $data['endereco'], $referencia, $data['qtd'], $data['unidade'], $data['descricao'], $geolocalizacao, $observacao);
    $stmt_ocorrencia->execute();
    $id_nova_ocorrencia = $conn->insert_id;
    $stmt_ocorrencia->close();

    // ETAPA 2: Criar a manutenção vinculada (código existente)
    $status_reparo = isset($data['assignmentDetails']) ? 'em andamento' : 'pendente'; // Status muda se houver atribuição
    $sql_manutencao = "INSERT INTO manutencoes (id_cidade, id_usuario, ocorrencia_reparo, tipo_manutencao, status_reparo, id_ocorrencia_semaforica, inicio_reparo) VALUES (?, ?, ?, 'corretiva', ?, ?, NOW())";
    $stmt_manutencao = $conn->prepare($sql_manutencao);
    $problem_description_manutencao = $data['descricao'];
    $stmt_manutencao->bind_param("iissi", $data['id_cidade'], $id_usuario_logado, $problem_description_manutencao, $status_reparo, $id_nova_ocorrencia);
    $stmt_manutencao->execute();
    $id_nova_manutencao = $conn->insert_id; // Pega o ID da manutenção recém-criada
    $stmt_manutencao->close();

    // ETAPA 3: Salvar a atribuição de técnicos, se houver (NOVA LÓGICA)
    if (isset($data['assignmentDetails']) && !empty($data['assignmentDetails']['tecnicos'])) {
        $details = $data['assignmentDetails'];
        $sql_assign = "INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, 'pendente')";
        $stmt_assign = $conn->prepare($sql_assign);

        $veiculos = $details['veiculos'];
        $veiculos_count = count($veiculos);
        $i = 0;

        foreach ($details['tecnicos'] as $id_tecnico) {
            $id_veiculo = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
            $stmt_assign->bind_param('iiiss', $id_nova_manutencao, $id_tecnico, $id_veiculo, $details['inicio_reparo'], $details['fim_reparo']);
            $stmt_assign->execute();
            $i++;
        }
        $stmt_assign->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'id_ocorrencia' => $id_nova_ocorrencia, 'message' => 'Ocorrência registrada com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro na transação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Falha ao registrar a ocorrência. Tente novamente.']);
}

$conn->close();
?>