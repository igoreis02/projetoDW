<?php
// /API/update_lacres.php - VERSÃO CORRIGIDA PARA EDITAR

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
$lacres_enviados = $data['lacres'] ?? [];

if (!$id_equipamento || empty($lacres_enviados)) {
    $response['message'] = 'Dados insuficientes para realizar a operação.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Prepara os statements que vamos usar
    $stmt_update = $conn->prepare(
        "UPDATE controle_lacres SET 
            num_lacre = ?, num_lacre_rompido = ?, obs_lacre = ?, lacre_rompido = ?, 
            lacre_afixado = ?, dt_fixacao = ?, dt_rompimento = ?, dt_reporta_psie = ?, 
            acao = ?, id_usuario_afixou = ? 
         WHERE id_controle_lacres = ? AND id_equipamento = ?"
    );

    $stmt_insert = $conn->prepare(
        "INSERT INTO controle_lacres 
            (id_equipamento, local_lacre, num_lacre, num_lacre_rompido, obs_lacre, lacre_rompido, lacre_afixado, dt_fixacao, dt_rompimento, dt_reporta_psie, acao, id_usuario_afixou) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($lacres_enviados as $lacre) {
        $id_controle_lacre = $lacre['id'] ?? null;
        $is_rompido = $lacre['rompido'] ?? false;

        // Dados comuns
        $b_obs = $lacre['obs'] ?? null;
        $b_dt_fixacao = !empty($lacre['dt_fixacao']) ? $lacre['dt_fixacao'] : null;
        $b_dt_rompimento = !empty($lacre['dt_rompimento']) ? $lacre['dt_rompimento'] : null;
        $b_dt_reporta_psie = !empty($lacre['dt_reporta_psie']) ? $lacre['dt_reporta_psie'] : null;

        if ($is_rompido) {
            $b_num_lacre = null;
            $b_num_lacre_rompido = $lacre['numero'];
            $b_lacre_rompido_val = 1;
            $b_lacre_afixado_val = 0;
            $b_acao = 'Rompido';
            $b_dt_fixacao = null;
        } else {
            $b_num_lacre = $lacre['numero'];
            $b_num_lacre_rompido = null;
            $b_lacre_rompido_val = 0;
            $b_lacre_afixado_val = 1;
            $b_acao = 'Afixado';
        }

        if ($id_controle_lacre) {
            // Se tem ID, é um UPDATE
            $stmt_update->bind_param(
                "sssiisssssii",
                $b_num_lacre, $b_num_lacre_rompido, $b_obs, $b_lacre_rompido_val,
                $b_lacre_afixado_val, $b_dt_fixacao, $b_dt_rompimento, $b_dt_reporta_psie,
                $b_acao, $id_usuario_logado,
                $id_controle_lacre, $id_equipamento
            );
            $stmt_update->execute();
        } else {
            // Se não tem ID, é um INSERT
             $stmt_insert->bind_param(
                "issssiisssss",
                $id_equipamento, $lacre['local'], $b_num_lacre, $b_num_lacre_rompido,
                $b_obs, $b_lacre_rompido_val, $b_lacre_afixado_val, $b_dt_fixacao,
                $b_dt_rompimento, $b_dt_reporta_psie, $b_acao, $id_usuario_logado
            );
            $stmt_insert->execute();
        }
    }

    $stmt_update->close();
    $stmt_insert->close();
    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacres atualizados com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>