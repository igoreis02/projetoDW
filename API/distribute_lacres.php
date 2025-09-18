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
$id_usuario_distribuiu = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$id_equipamento = $data['id_equipamento'] ?? null;
$lacres = $data['lacres'] ?? [];

if (!$id_equipamento || empty($lacres)) {
    $response['message'] = 'Dados insuficientes para a distribuição.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    // Busca dados do equipamento
    $stmt_equip = $conn->prepare("SELECT id_cidade FROM equipamentos WHERE id_equipamento = ?");
    $stmt_equip->bind_param("i", $id_equipamento);
    $stmt_equip->execute();
    $result_equip = $stmt_equip->get_result();
    if ($result_equip->num_rows === 0) throw new Exception("Equipamento não encontrado.");
    $equip_data = $result_equip->fetch_assoc();
    $id_cidade = $equip_data['id_cidade'];
    $stmt_equip->close();


    $lacres_para_ocorrencia = [];
    $ids_lacres_distribuidos = [];

    foreach ($lacres as $lacre) {
        $b_local_lacre = $lacre['local'];
        $b_num_lacre_novo = $lacre['numero'];

        // 1. Verifica se já existe um registro para este local que esteja rompido ou pendente
        $stmt_check = $conn->prepare("SELECT id_controle_lacres FROM controle_lacres WHERE id_equipamento = ? AND local_lacre = ? AND (lacre_rompido = 1 OR lacre_distribuido = 1)");
        $stmt_check->bind_param("is", $id_equipamento, $b_local_lacre);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $existing_lacre = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($existing_lacre) {
            // --- FAZ O UPDATE ---
            $id_lacre_existente = $existing_lacre['id_controle_lacres'];
            $sql_update = "UPDATE controle_lacres SET
                                num_lacre_distribuido = ?,
                                lacre_distribuido = 1,
                                dt_fixacao = NULL,
                                acao = 'Distribuído',
                                id_usuario_distribuiu = ?,
                                lacre_rompido = 0,      -- Zera o status de rompido, pois está sendo substituído
                                num_lacre_rompido = NULL  -- Limpa o número do lacre rompido
                           WHERE id_controle_lacres = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sii", $b_num_lacre_novo, $id_usuario_distribuiu, $id_lacre_existente);
            $stmt_update->execute();
            $stmt_update->close();
            $ids_lacres_distribuidos[] = $id_lacre_existente; // Guarda o ID do registro atualizado
        } else {
            // --- FAZ O INSERT ---
            $sql_insert = "INSERT INTO controle_lacres 
                            (id_equipamento, local_lacre, num_lacre_distribuido, lacre_distribuido, acao, id_usuario_distribuiu) 
                           VALUES (?, ?, ?, 1, 'Distribuído', ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("issi", $id_equipamento, $b_local_lacre, $b_num_lacre_novo, $id_usuario_distribuiu);
            $stmt_insert->execute();
            $ids_lacres_distribuidos[] = $conn->insert_id; // Guarda o ID do novo registro
            $stmt_insert->close();
        }

        // Monta o texto para a ocorrência de manutenção
        $texto_item = $b_local_lacre;
        if (!empty($lacre['obs'])) {
            $texto_item .= " (" . $lacre['obs'] . ")";
        }
        $lacres_para_ocorrencia[] = $texto_item;
    }

    $ocorrencia_reparo_texto = "Afixar lacre: " . implode('; ', $lacres_para_ocorrencia);
    $ids_lacres_string = implode(',', $ids_lacres_distribuidos);

    // 2. Prepara e insere a nova ocorrência de manutenção do tipo "afixar"
    $sql_manutencao = "INSERT INTO manutencoes 
                        (id_equipamento, id_usuario, id_cidade, status_reparo, tipo_manutencao, nivel_ocorrencia, ocorrencia_reparo, inicio_reparo, id_controle_lacres_dist) 
                       VALUES (?, ?, ?, 'pendente', 'afixar', 3, ?, NOW(), ?)";
    $stmt_manutencao = $conn->prepare($sql_manutencao);
    $stmt_manutencao->bind_param("iiiss", $id_equipamento, $id_usuario_distribuiu, $id_cidade, $ocorrencia_reparo_texto, $ids_lacres_string);
    $stmt_manutencao->execute();
    $stmt_manutencao->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacres distribuídos e ocorrência de afixação criada com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
}   

echo json_encode($response);
$conn->close();
?>