<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Falha na conexão: " . $conn->connect_error]);
    exit();
}

// Recebe os dados do corpo da requisição JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação básica dos dados
if (!isset($data['id_equipamento']) || !isset($data['id_endereco']) || !isset($data['tipo_equip']) ||
    !isset($data['nome_equip']) || !isset($data['status']) || !isset($data['id_cidade']) ||
    !isset($data['logradouro']) || !isset($data['bairro'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para atualização.']);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Atualizar a tabela `endereco`
    $stmt_endereco = $conn->prepare("UPDATE endereco SET logradouro = ?, numero = ?, bairro = ?, cep = ?, complemento = ?, latitude = ?, longitude = ? WHERE id_endereco = ?");
    $numero = $data['numero'] ?? null;
    $cep = $data['cep'] ?? null;
    $complemento = $data['complemento'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $stmt_endereco->bind_param("sssssddi", $data['logradouro'], $numero, $data['bairro'], $cep, $complemento, $latitude, $longitude, $data['id_endereco']);
    $stmt_endereco->execute();
    $stmt_endereco->close();

    // 2. Atualizar a tabela `equipamentos`
    $stmt_equipamento = $conn->prepare("UPDATE equipamentos SET tipo_equip = ?, nome_equip = ?, referencia_equip = ?, status = ?, id_cidade = ? WHERE id_equipamento = ?");
    $referencia_equip = $data['referencia_equip'] ?? null;
    $stmt_equipamento->bind_param("ssssii", $data['tipo_equip'], $data['nome_equip'], $referencia_equip, $data['status'], $data['id_cidade'], $data['id_equipamento']);
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento atualizado com sucesso!']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao atualizar equipamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
}

$conn->close();
