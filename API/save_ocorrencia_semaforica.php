<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

-
$id_usuario_logado = $_SESSION['user_id'];

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação dos dados continua a mesma
if (!isset($data['id_cidade']) || !is_numeric($data['id_cidade']) ||
    !isset($data['endereco']) || empty($data['endereco']) ||
    !isset($data['qtd']) || !is_numeric($data['qtd']) ||
    !isset($data['unidade']) || empty($data['unidade']) ||
    !isset($data['descricao']) || empty($data['descricao'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos. Por favor, preencha os campos obrigatórios.']);
    exit();
}

$conn->begin_transaction();

try {
    
    $sql_ocorrencia = "INSERT INTO ocorrencia_semaforica 
        (id_cidade, tipo, endereco, referencia, qtd, unidade, descricao_problema, geolocalizacao, observacao, data) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt_ocorrencia = $conn->prepare($sql_ocorrencia);

    $tipo = !empty($data['tipo']) ? $data['tipo'] : null;
    $referencia = !empty($data['referencia']) ? $data['referencia'] : null;
    $geolocalizacao = !empty($data['geo']) ? $data['geo'] : null;
    $observacao = !empty($data['observacao']) ? $data['observacao'] : null;

    $stmt_ocorrencia->bind_param(
        "isssissss",
        $data['id_cidade'],
        $tipo,
        $data['endereco'],
        $referencia,
        $data['qtd'],
        $data['unidade'],
        $data['descricao'],
        $geolocalizacao,
        $observacao
    );

    $stmt_ocorrencia->execute();
    $id_nova_ocorrencia = $conn->insert_id;
    $stmt_ocorrencia->close();

   
    $sql_manutencao = "INSERT INTO manutencoes 
        (id_cidade, id_usuario, ocorrencia_reparo, tipo_manutencao, status_reparo, id_ocorrencia_semaforica, inicio_reparo) 
        VALUES (?, ?, ?, 'corretiva', 'pendente', ?, NOW())";
    
    $stmt_manutencao = $conn->prepare($sql_manutencao);
    
    $problem_description_manutencao = $data['descricao'];

   
    $stmt_manutencao->bind_param(
        "iisi", 
        $data['id_cidade'],
        $id_usuario_logado,
        $problem_description_manutencao,
        $id_nova_ocorrencia
    );

    $stmt_manutencao->execute();
    $stmt_manutencao->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Ocorrência e manutenção corretiva registradas com sucesso!']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro na transação: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Falha ao registrar a ocorrência. Tente novamente.']);
}

$conn->close();
?>