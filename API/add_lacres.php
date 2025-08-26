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
$lacres = $data['lacres'] ?? [];
$id_tecnico = $_SESSION['user_id'];

if (empty($id_equipamento) || empty($lacres)) {
    $response['message'] = 'Dados incompletos.';
    echo json_encode($response);
    exit();
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO controle_lacres 
                (id_equipamento, local_lacre, num_lacre, lacre_afixado, dt_fixacao, id_tecnico, acao) 
            VALUES (?, ?, ?, 1, NOW(), ?, 'Afixado')";
            
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro ao preparar a query: " . $conn->error);
    }

    foreach ($lacres as $lacre) {
        $local = $lacre['local'];
        $numero = $lacre['numero'];
        
        
        $stmt->bind_param("issi", $id_equipamento, $local, $numero, $id_tecnico);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao salvar o lacre para '" . $local . "': " . $stmt->error);
        }
    }

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Lacres salvos com sucesso!';

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

if ($stmt) {
    $stmt->close();
}
$conn->close();
echo json_encode($response);
?>