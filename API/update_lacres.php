<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$id_equipamento = $data['id_equipamento'] ?? null;
$lacres_novos = $data['lacres'] ?? [];
$id_tecnico = $_SESSION['user_id'];

if (empty($id_equipamento) || empty($lacres_novos)) {
    $response['message'] = 'Dados incompletos para atualização.';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();

try {
    // Passo 1: Marcar os lacres antigos como "Substituído" (lacre_afixado = 0)
    $sql_update = "UPDATE controle_lacres SET lacre_afixado = 0, acao = 'Substituído' WHERE id_equipamento = ? AND lacre_afixado = 1";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $id_equipamento);
    if (!$stmt_update->execute()) {
        throw new Exception("Erro ao remover lacres antigos: " . $stmt_update->error);
    }
    $stmt_update->close();

    // Passo 2: Inserir os novos lacres
    $sql_insert = "INSERT INTO controle_lacres 
                (id_equipamento, local_lacre, num_lacre, lacre_afixado, dt_fixacao, id_tecnico, acao) 
               VALUES (?, ?, ?, 1, NOW(), ?, 'Afixado')";
    $stmt_insert = $conn->prepare($sql_insert);

    foreach ($lacres_novos as $lacre) {
        $local = $lacre['local'];
        $numero = $lacre['numero'];
        $stmt_insert->bind_param("issi", $id_equipamento, $local, $numero, $id_tecnico);
        if (!$stmt_insert->execute()) {
            throw new Exception("Erro ao salvar novo lacre para '" . $local . "': " . $stmt_insert->error);
        }
    }
    $stmt_insert->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacres atualizados com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

$conn->close();
echo json_encode($response);
?>