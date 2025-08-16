<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

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