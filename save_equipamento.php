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

$nome_equip = $data['nome_equip'] ?? null;
$referencia_equip = $data['referencia_equip'] ?? null;
$id_cidade = $data['id_cidade'] ?? null;
$id_endereco = $data['id_endereco'] ?? null;

// Validação básica
if (empty($nome_equip) || empty($referencia_equip) || empty($id_cidade) || empty($id_endereco)) {
    echo json_encode(['success' => false, 'message' => 'Dados de equipamento incompletos.']);
    exit();
}

// Prepara a instrução SQL para inserção
$sql = "INSERT INTO equipamentos (nome_equip, referencia_equip, id_cidade, id_endereco) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// Vincula os parâmetros
// 'ssii' -> s (string) para nome_equip, referencia_equip; i (integer) para id_cidade, id_endereco
$stmt->bind_param("ssii", $nome_equip, $referencia_equip, $id_cidade, $id_endereco);

// Executa a instrução
if ($stmt->execute()) {
    $last_id = $conn->insert_id; // Obtém o ID do equipamento recém-inserido
    echo json_encode(['success' => true, 'message' => 'Equipamento cadastrado com sucesso!', 'id_equipamento' => $last_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar equipamento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
