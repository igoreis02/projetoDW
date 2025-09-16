<?php
header('Content-Type: application/json');
session_start();
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro.'];
$data = json_decode(file_get_contents('php://input'), true);

$id_equipamento = $data['id_equipamento'] ?? null;
$data_rompimento = $data['data_rompimento'] ?? null;
$lacres = $data['lacres'] ?? [];

if (!$id_equipamento || empty($lacres) || !$data_rompimento) {
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
                acao = 'Rompido'
            WHERE id_equipamento = ? AND local_lacre = ? AND num_lacre = ?";

    $stmt = $conn->prepare($sql);

    foreach ($lacres as $lacre) {
        $local = $lacre['local'];
        $numero = $lacre['numero'];
        $obs = !empty($lacre['obs']) ? $lacre['obs'] : null;

        // Vincula os parâmetros para cada lacre e executa
        $stmt->bind_param("ssiss", $data_rompimento, $obs, $id_equipamento, $local, $numero);

        if (!$stmt->execute()) {
            // Se um falhar, joga uma exceção para cancelar tudo
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
    http_response_code(500); // Informa que foi um erro de servidor
}

echo json_encode($response);
$conn->close();
?>