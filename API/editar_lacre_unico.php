<?php
// API/editar_lacre_unico.php - VERSÃO FINAL ATUALIZADA

header('Content-Type: application/json');
session_start();
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

// Coleta todos os dados do formulário, incluindo o novo campo
$id_controle_lacres = $_POST['id_controle_lacres'] ?? null;
$numero_lacre = $_POST['numero_lacre'] ?? null;
$obs_lacre = $_POST['obs_lacre'] ?? '';
$dt_fixacao = !empty($_POST['dt_fixacao']) ? $_POST['dt_fixacao'] : null;
$dt_rompimento = !empty($_POST['dt_rompimento']) ? $_POST['dt_rompimento'] : null;
$dt_reporta_psie = !empty($_POST['dt_reporta_psie']) ? $_POST['dt_reporta_psie'] : null; // Novo campo

if (!$id_controle_lacres || !$numero_lacre) {
    $response['message'] = 'Dados insuficientes para a edição.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();
try {
    $stmt_check = $conn->prepare("SELECT lacre_afixado, lacre_rompido, lacre_distribuido FROM controle_lacres WHERE id_controle_lacres = ?");
    $stmt_check->bind_param("i", $id_controle_lacres);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) throw new Exception("Lacre não encontrado.");
    $lacre_status = $result_check->fetch_assoc();
    $stmt_check->close();

    // Monta a query de UPDATE de acordo com o status
    if ($lacre_status['lacre_afixado'] == 1) {
        $stmt = $conn->prepare(
            "UPDATE controle_lacres SET num_lacre = ?, obs_lacre = ?, dt_fixacao = ? WHERE id_controle_lacres = ?"
        );
        $stmt->bind_param("sssi", $numero_lacre, $obs_lacre, $dt_fixacao, $id_controle_lacres);
    } elseif ($lacre_status['lacre_rompido'] == 1) {
        // Query ATUALIZADA para incluir dt_reporta_psie
        $stmt = $conn->prepare(
            "UPDATE controle_lacres SET num_lacre_rompido = ?, obs_lacre = ?, dt_rompimento = ?, dt_reporta_psie = ? WHERE id_controle_lacres = ?"
        );
        $stmt->bind_param("ssssi", $numero_lacre, $obs_lacre, $dt_rompimento, $dt_reporta_psie, $id_controle_lacres);
    } elseif ($lacre_status['lacre_distribuido'] == 1) {
        $stmt = $conn->prepare(
            "UPDATE controle_lacres SET num_lacre_distribuido = ?, obs_lacre = ? WHERE id_controle_lacres = ?"
        );
        $stmt->bind_param("ssi", $numero_lacre, $obs_lacre, $id_controle_lacres);
    } else {
        throw new Exception("Este tipo de lacre não pode ser editado por esta função.");
    }

    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacre atualizado com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>