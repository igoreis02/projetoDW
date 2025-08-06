<?php
header('Content-Type: application/json'); // Define o cabeçalho para JSON
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configurações do banco de dados
// ATENÇÃO: Substitua 'localhost', 'root', '' e 'gerenciamento_manutencoes' pelos seus dados reais
$servername = "localhost";
$username = "root"; // Substitua pelo seu usuário do banco de dados
$password = "";     // Substitua pela sua senha do banco de dados
$dbname = "gerenciamento_manutencoes"; // Substitua pelo nome do seu banco de dados

// Cria conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Obtém os dados JSON da requisição (enviados pelo JavaScript)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$email = $data['email'] ?? '';
$newPassword = $data['newPassword'] ?? '';

// Validação básica dos inputs
if (empty($email) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'E-mail e nova senha são obrigatórios.']);
    exit();
}

// Em um ambiente real, você faria hash da senha antes de armazená-la
// Ex: $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
// Para este exemplo, estamos armazenando a senha diretamente.

// Atualiza a senha e a flag senha_alterada no banco de dados
// Usamos prepared statements para prevenir injeção de SQL
$stmt = $conn->prepare("UPDATE Usuario SET senha = ?, senha_alterada = TRUE WHERE email = ?");
$stmt->bind_param("ss", $newPassword, $email); // 'ss' para duas strings
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Não foi possível alterar a senha. Verifique o e-mail.']);
}

$stmt->close();
$conn->close();
?>
