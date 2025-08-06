<?php
session_start(); // Inicia a sessão para armazenar dados do usuário

header('Content-Type: application/json'); // Define o cabeçalho para JSON
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// Obtém os dados JSON da requisição (enviados pelo JavaScript)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$email = $data['email'] ?? '';
$senha = $data['password'] ?? '';

// Validação básica dos inputs
if (empty($email) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'E-mail e senha são obrigatórios.']);
    exit();
}

// Prepara a consulta SQL para buscar o usuário pelo e-mail
// Usamos prepared statements para prevenir injeção de SQL
$stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, senha_alterada, tipo_usuario FROM Usuario WHERE email = ?");
$stmt->bind_param("s", $email); // 's' indica que o parâmetro é uma string
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verifica a senha.
    // Em um ambiente de produção, você DEVE usar password_verify() para senhas com hash.
    // Ex: if (password_verify($senha, $user['senha'])) { ... }
    if ($senha === $user['senha']) { // Comparação direta para este exemplo
        // Define variáveis de sessão para o usuário logado
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
        $_SESSION['redefinir_senha_obrigatoria'] = false; // Assume que a senha não precisa ser redefinida por padrão

        // Verifica se a senha é a padrão '12345' ou se a flag 'senha_alterada' é FALSE
        if ($user['senha'] === '12345' || $user['senha_alterada'] == 0) {
            $_SESSION['redefinir_senha_obrigatoria'] = true; // Sinaliza que a redefinição é obrigatória
            echo json_encode([
                'success' => true,
                'message' => 'Sua senha é padrão ou precisa ser alterada. Redirecionando para alteração de senha.',
                'needsPasswordChange' => true,
                'redirectUrl' => 'menu.php' // Redireciona para menu.php que irá exibir o modal
            ]);
        } else if ($user['tipo_usuario'] === 'tecnico') {
            // Se for técnico e a senha já foi alterada, redireciona para a página do técnico
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso! Redirecionando para as manutenções.',
                'needsPasswordChange' => false,
                'redirectUrl' => 'manutencao_tecnico.php' // Redireciona para a página do técnico
            ]);
        } else {
            // Login bem-sucedido para outros tipos de usuário, senha já redefinida
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'needsPasswordChange' => false,
                'redirectUrl' => 'menu.php' // Redireciona para o menu principal
            ]);
        }
    } else {
        // Senha incorreta
        echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
    }
} else {
    // E-mail não encontrado
    echo json_encode(['success' => false, 'message' => 'E-mail não encontrado.']);
}

$stmt->close();
$conn->close();
?>
