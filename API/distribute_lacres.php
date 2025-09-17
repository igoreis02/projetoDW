<?php
// API/distribute_lacres.php - VERSÃO CORRIGIDA FINAL

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
    // Busca dados do equipamento para criar a ocorrência
    $stmt_equip = $conn->prepare("SELECT id_cidade FROM equipamentos WHERE id_equipamento = ?");
    $stmt_equip->bind_param("i", $id_equipamento);
    $stmt_equip->execute();
    $result_equip = $stmt_equip->get_result();
    if ($result_equip->num_rows === 0) {
        throw new Exception("Equipamento não encontrado.");
    }
    $equip_data = $result_equip->fetch_assoc();
    $id_cidade = $equip_data['id_cidade'];
    $stmt_equip->close();
    
    // 1. Prepara a query para ATUALIZAR os registros de lacres
    $sql_lacre_update = "UPDATE controle_lacres SET
                            num_lacre_distribuido = ?,
                            lacre_distribuido = 1,
                            dt_fixacao = NULL,
                            acao = 'Distribuído',
                            id_usuario_distribuiu = ?
                        WHERE id_equipamento = ? AND local_lacre = ? AND (lacre_rompido = 1 OR lacre_distribuido = 1)";
    $stmt_update = $conn->prepare($sql_lacre_update);

    // Prepara uma segunda query para buscar o ID do registro que acabamos de atualizar
    $sql_get_id = "SELECT id_controle_lacres FROM controle_lacres WHERE id_equipamento = ? AND local_lacre = ? ORDER BY id_controle_lacres DESC LIMIT 1";
    $stmt_get_id = $conn->prepare($sql_get_id);
    
    $lacres_para_ocorrencia = [];
    $ids_lacres_distribuidos = []; // Array para guardar os IDs

    foreach ($lacres as $lacre) {
        $b_local_lacre = $lacre['local'];
        $b_num_lacre_novo = $lacre['numero'];

        // Executa o UPDATE
        $stmt_update->bind_param("siis", $b_num_lacre_novo, $id_usuario_distribuiu, $id_equipamento, $b_local_lacre);
        $stmt_update->execute();

        // Busca o ID do registro que foi atualizado
        $stmt_get_id->bind_param("is", $id_equipamento, $b_local_lacre);
        $stmt_get_id->execute();
        $result_id = $stmt_get_id->get_result();
        if($row_id = $result_id->fetch_assoc()) {
            $ids_lacres_distribuidos[] = $row_id['id_controle_lacres'];
        }

        // Monta o texto para a ocorrência
        $texto_item = $b_local_lacre;
        if (!empty($lacre['obs'])) {
            $texto_item .= " (" . $lacre['obs'] . ")";
        }
        $lacres_para_ocorrencia[] = $texto_item;
    }
    $ocorrencia_reparo_texto = "Afixar lacre: " . implode('; ', $lacres_para_ocorrencia);
    $stmt_update->close();
    $stmt_get_id->close();

    // Converte o array de IDs em uma string separada por vírgula
    $ids_lacres_string = implode(',', $ids_lacres_distribuidos);

    // 2. Prepara e insere a nova ocorrência de manutenção, incluindo os IDs dos lacres
    $sql_manutencao = "INSERT INTO manutencoes 
                        (id_equipamento, id_usuario, id_cidade, status_reparo, tipo_manutencao, nivel_ocorrencia, ocorrencia_reparo, inicio_reparo, id_controle_lacres_dist) 
                       VALUES (?, ?, ?, 'pendente', 'afixar', 2, ?, NOW(), ?)";
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