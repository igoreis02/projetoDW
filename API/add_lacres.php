<?php
// Este código deve ser usado tanto para add_lacres.php quanto para update_lacres.php
header('Content-Type: application/json');
session_start();
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => 'Ocorreu um erro.'];
$data = json_decode(file_get_contents('php://input'), true);
$id_equipamento = $data['id_equipamento'] ?? null;
$lacres = $data['lacres'] ?? [];

// A verificação do id_tecnico foi removida
if (!$id_equipamento || empty($lacres)) {
    $response['message'] = 'Dados insuficientes para realizar a operação.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Para a operação de SUBSTITUIÇÃO, marca os lacres antigos como substituídos
    if (basename($_SERVER['PHP_SELF']) === 'update_lacres.php') {
        $stmt_substituir = $conn->prepare(
            "UPDATE controle_lacres SET acao = 'Substituído', lacre_afixado = 0 WHERE id_equipamento = ? AND lacre_afixado = 1"
        );
        $stmt_substituir->bind_param("i", $id_equipamento);
        $stmt_substituir->execute();
        $stmt_substituir->close();
    }

    // A coluna 'id_tecnico' foi removida da query
    $sql = "INSERT INTO controle_lacres 
                (id_equipamento, local_lacre, num_lacre, num_lacre_rompido, obs_lacre, lacre_rompido, lacre_afixado, dt_fixacao, acao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";
    $stmt_insert = $conn->prepare($sql);

    // O bind_param foi ajustado para remover o id_tecnico
    $stmt_insert->bind_param(
        "issssiis", 
        $b_id_equipamento, 
        $b_local_lacre, 
        $b_num_lacre, 
        $b_num_lacre_rompido,
        $b_obs,
        $b_lacre_rompido_val,
        $b_lacre_afixado_val,
        $b_acao
    );

    foreach ($lacres as $lacre) {
        $is_rompido = $lacre['rompido'] ?? false;
        
        $b_id_equipamento = $id_equipamento;
        $b_local_lacre = $lacre['local'];
        $b_obs = $lacre['obs'] ?? null;

        if ($is_rompido) {
            $b_num_lacre = null;
            $b_num_lacre_rompido = $lacre['numero'];
            $b_lacre_rompido_val = 1;
            $b_lacre_afixado_val = 0;
            $b_acao = 'Rompido na Aferição';
        } else {
            $b_num_lacre = $lacre['numero'];
            $b_num_lacre_rompido = null;
            $b_lacre_rompido_val = 0;
            $b_lacre_afixado_val = 1;
            $b_acao = 'Afixado';
        }
        
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
?>