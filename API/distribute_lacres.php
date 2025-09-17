<?php
// API/distribute_lacres.php
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

    // 1. Prepara a query para inserir os registros de lacres distribuídos
    $sql_lacre = "INSERT INTO controle_lacres 
                    (id_equipamento, local_lacre, num_lacre, lacre_distribuido, dt_fixacao, acao, id_usuario_distribuiu) 
                  VALUES (?, ?, ?, 1, CURDATE(), 'Distribuído', ?)";
    $stmt_lacre = $conn->prepare($sql_lacre);

    $ocorrencia_reparo_texto = "Afixar lacres: ";
    $lacres_para_ocorrencia = [];

    foreach ($lacres as $lacre) {
        $b_id_equipamento = $id_equipamento;
        $b_local_lacre = $lacre['local'];
        $b_num_lacre = $lacre['numero'];

        $stmt_lacre->bind_param("issi", $b_id_equipamento, $b_local_lacre, $b_num_lacre, $id_usuario_distribuiu);
        $stmt_lacre->execute();

        // Monta o texto para a ocorrência
        $lacres_para_ocorrencia[] = "{$b_local_lacre} ({$b_num_lacre})";
    }
    $ocorrencia_reparo_texto .= implode(', ', $lacres_para_ocorrencia);
    $stmt_lacre->close();

    // 2. Prepara e insere a nova ocorrência de manutenção
    $sql_manutencao = "INSERT INTO manutencoes 
                        (id_equipamento, id_usuario, id_cidade, status_reparo, tipo_manutencao, nivel_ocorrencia, ocorrencia_reparo, inicio_reparo) 
                       VALUES (?, ?, ?, 'pendente', 'corretiva', 2, ?, NOW())";
    $stmt_manutencao = $conn->prepare($sql_manutencao);
    $stmt_manutencao->bind_param("iiis", $id_equipamento, $id_usuario_distribuiu, $id_cidade, $ocorrencia_reparo_texto);
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