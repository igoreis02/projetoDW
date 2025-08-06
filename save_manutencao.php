<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Configurações do banco de dados
// ATENÇÃO: Substitua 'localhost', 'root', '' e 'gerenciamento_manutencoes' pelos seus dados reais
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

$id_equipamento = $data['equipment_id'] ?? null;
$id_cidade = $data['city_id'] ?? null;
$ocorrencia_reparo = $data['problem_description'] ?? null;
$status_reparo = $data['status_reparo'] ?? 'pendente'; // Obtém do POST, com 'pendente' como padrão
$tipo_manutencao = $data['tipo_manutencao'] ?? 'corretiva'; // Obtém do POST, com 'corretiva' como padrão

// Validação básica dos dados
if (empty($id_equipamento) || empty($id_cidade) || empty($ocorrencia_reparo)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para registrar a manutenção.']);
    exit();
}

// Prepara a instrução SQL para inserção
// id_tecnico e id_provedor são DEFAULT NULL, inicio_reparo é CURRENT_TIMESTAMP()
$sql = "INSERT INTO manutencoes (id_equipamento, id_cidade, status_reparo, tipo_manutencao, ocorrencia_reparo) VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// Vincula os parâmetros
// 'iisss' -> i (integer) para id_equipamento, i (integer) para id_cidade, s (string) para status, s (string) para tipo, s (string) para ocorrencia
$stmt->bind_param("iisss", $id_equipamento, $id_cidade, $status_reparo, $tipo_manutencao, $ocorrencia_reparo);

// Executa a instrução
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Manutenção registrada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao registrar manutenção: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
