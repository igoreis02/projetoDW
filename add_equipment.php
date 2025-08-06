<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

// Recebe os dados do corpo da requisição JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação básica dos dados
if (
    !isset($data['tipo_equip']) || !isset($data['nome_equip']) ||
    !isset($data['status']) || !isset($data['id_cidade']) ||
    !isset($data['logradouro']) || !isset($data['bairro'])
) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

$conn->begin_transaction();

try {
    // 1. Inserir na tabela `endereco`
    $stmt_endereco = $conn->prepare("INSERT INTO endereco (logradouro, numero, bairro, cep, complemento, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $numero = $data['numero'] ?? null;
    $cep = $data['cep'] ?? null;
    $complemento = $data['complemento'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $stmt_endereco->bind_param("sssssdd", $data['logradouro'], $numero, $data['bairro'], $cep, $complemento, $latitude, $longitude);
    $stmt_endereco->execute();
    $id_endereco = $conn->insert_id;
    $stmt_endereco->close();

    // 2. Inserir na tabela `equipamentos`
    $stmt_equipamento = $conn->prepare("INSERT INTO equipamentos (tipo_equip, nome_equip, referencia_equip, status, id_cidade, id_endereco) VALUES (?, ?, ?, ?, ?, ?)");
    $referencia_equip = $data['referencia_equip'] ?? null;
    $stmt_equipamento->bind_param("ssssii", $data['tipo_equip'], $data['nome_equip'], $referencia_equip, $data['status'], $data['id_cidade'], $id_endereco);
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento adicionado com sucesso!']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao adicionar equipamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
}

$conn->close();
