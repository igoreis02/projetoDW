<?php
header('Content-Type: application/json');


// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os dados da requisição POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$logradouro = $data['logradouro'] ?? null;
$bairro = $data['bairro'] ?? null;
$cep = $data['cep'] ?? null;
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

// Validação básica (sem o campo 'numero')
if (empty($logradouro) || empty($bairro) || empty($cep)) {
    echo json_encode(['success' => false, 'message' => 'Dados de endereço incompletos. Logradouro, bairro e CEP são obrigatórios.']);
    exit();
}

// Prepara a instrução SQL para inserção (sem 'numero' e 'complemento')
$sql = "INSERT INTO endereco (logradouro, bairro, cep, latitude, longitude) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// Vincula os parâmetros
// 'sssdd' -> s (string) para logradouro, bairro, cep; d (double) para latitude, longitude
$stmt->bind_param("sssdd", $logradouro, $bairro, $cep, $latitude, $longitude);

// Executa a instrução
if ($stmt->execute()) {
    $last_id = $conn->insert_id;
    echo json_encode(['success' => true, 'message' => 'Endereço cadastrado com sucesso!', 'id_endereco' => $last_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar endereço: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>