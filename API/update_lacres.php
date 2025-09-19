<?php
// /API/add_lacres.php E /API/update_lacres.php - VERSÃO CORRIGIDA

header('Content-Type: application/json');
session_start();
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}
$id_usuario_logado = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$id_equipamento = $data['id_equipamento'] ?? null;
$lacres = $data['lacres'] ?? [];

if (!$id_equipamento || empty($lacres)) {
    $response['message'] = 'Dados insuficientes para realizar a operação.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Se for uma SUBSTITUIÇÃO, marca os lacres antigos como "Substituído"
    if (basename($_SERVER['PHP_SELF']) === 'update_lacres.php') {
        $stmt_substituir = $conn->prepare(
            "UPDATE controle_lacres SET acao = 'Substituído', lacre_afixado = 0 WHERE id_equipamento = ? AND lacre_afixado = 1"
        );
        $stmt_substituir->bind_param("i", $id_equipamento);
        $stmt_substituir->execute();
        $stmt_substituir->close();
    }

    $sql = "INSERT INTO controle_lacres 
                (id_equipamento, local_lacre, num_lacre, num_lacre_rompido, obs_lacre, lacre_rompido, lacre_afixado, dt_fixacao, dt_rompimento, dt_reporta_psie, acao, id_usuario_afixou) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql);

    foreach ($lacres as $lacre) {
        $is_rompido = $lacre['rompido'] ?? false;

        $b_id_equipamento = $id_equipamento;
        $b_local_lacre = $lacre['local'];
        $b_obs = $lacre['obs'] ?? null;
        $b_dt_fixacao = !empty($lacre['dt_fixacao']) ? $lacre['dt_fixacao'] : null; 
        $b_dt_rompimento = !empty($lacre['dt_rompimento']) ? $lacre['dt_rompimento'] : null; 
        $b_dt_reporta_psie = !empty($lacre['dt_reporta_psie']) ? $lacre['dt_reporta_psie'] : null; 


        $b_id_usuario_afixou = $id_usuario_logado; // Salva o ID do usuário que está fazendo a ação

        if ($is_rompido) {
            $b_num_lacre = null;
            $b_num_lacre_rompido = $lacre['numero'];
            $b_lacre_rompido_val = 1;
            $b_lacre_afixado_val = 0;
            $b_acao = 'Rompido';
            $b_dt_fixacao = null; // Se está rompido, não tem data de fixação
        } else {
            $b_num_lacre = $lacre['numero'];
            $b_num_lacre_rompido = null;
            $b_lacre_rompido_val = 0;
            $b_lacre_afixado_val = 1;
            $b_acao = 'Afixado';
        }


        $stmt_insert->bind_param(
            "issssiisssss", 
            $id_equipamento,
            $b_local_lacre,
            $b_num_lacre,
            $b_num_lacre_rompido,
            $b_obs,
            $b_lacre_rompido_val,
            $b_lacre_afixado_val,
            $b_dt_fixacao,
            $b_dt_rompimento,
            $b_dt_reporta_psie, 
            $b_acao,
            $b_id_usuario_afixou
        );
        $stmt_insert->execute();
    }

    $stmt_insert->close();
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacres salvos com sucesso!';
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
