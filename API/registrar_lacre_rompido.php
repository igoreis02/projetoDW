<?php


header('Content-Type: application/json');
session_start();
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}
$id_usuario_rompeu = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$id_equipamento = $data['id_equipamento'] ?? null;
$lacres = $data['lacres'] ?? [];

if (!$id_equipamento || empty($lacres)) {
    $response['message'] = 'Dados insuficientes para registrar o rompimento.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Prepara a query de atualização uma única vez
    $sql = "UPDATE controle_lacres SET 
                num_lacre_rompido = num_lacre,
                num_lacre = NULL,
                lacre_rompido = 1,
                lacre_afixado = 0,
                dt_rompimento = ?,
                obs_lacre = ?,
                acao = 'Rompido',
                id_usuario_rompeu = ?
            WHERE id_equipamento = ? AND local_lacre = ? AND num_lacre = ?";

    $stmt = $conn->prepare($sql);

    foreach ($lacres as $lacre) {
        $local = $lacre['local'];
        $numero = $lacre['numero'];
        $obs = !empty($lacre['obs']) ? $lacre['obs'] : null;
        $data_rompimento = $lacre['data_rompimento'] ?? null; 

        if (empty($data_rompimento)) {
            throw new Exception("Data de rompimento não fornecida para o lacre '{$local}'.");
        }

        $stmt->bind_param("ssiiss", $data_rompimento, $obs, $id_usuario_rompeu, $id_equipamento, $local, $numero);

        if (!$stmt->execute()) {
            throw new Exception("Falha ao atualizar o lacre '{$local}' com número '{$numero}'. Erro: " . $stmt->error);
        }
    }

    $stmt->close();
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Rompimento de lacre(s) registrado com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
$conn->close();
?>