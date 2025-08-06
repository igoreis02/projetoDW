<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes"; // Nome do seu banco de dados

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
$numero = $data['numero'] ?? null;
$bairro = $data['bairro'] ?? null;
$cep = $data['cep'] ?? null;
$complemento = $data['complemento'] ?? null;
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

// Validação básica
if (empty($logradouro) || empty($numero) || empty($bairro) || empty($cep)) {
    echo json_encode(['success' => false, 'message' => 'Dados de endereço incompletos.']);
    exit();
}

// Prepara a instrução SQL para inserção
$sql = "INSERT INTO endereco (logradouro, numero, bairro, cep, complemento, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// Vincula os parâmetros
// 'sssssdd' -> s (string) para logradouro, numero, bairro, cep, complemento; d (double) para latitude, longitude
$stmt->bind_param("sssssdd", $logradouro, $numero, $bairro, $cep, $complemento, $latitude, $longitude);

// Executa a instrução
if ($stmt->execute()) {
    $last_id = $conn->insert_id; // Obtém o ID do endereço recém-inserido
    echo json_encode(['success' => true, 'message' => 'Endereço cadastrado com sucesso!', 'id_endereco' => $last_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar endereço: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
