<?php
session_start(); // Inicia a sessão para armazenar dados do usuário

header('Content-Type: application/json'); // Define o cabeçalho para JSON


// Configurações do banco de dados
require_once 'conexao_bd.php';

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

$stmt = $conn->prepare("SELECT id_usuario, nome, email, senha, senha_alterada, tipo_usuario, status_usuario FROM Usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    //  Verificação de Status ---
    // Agora, a primeira coisa que fazemos após encontrar o usuário é verificar seu status.
    if ($user['status_usuario'] !== 'ativo') {
        // Se o usuário não estiver ativo, retorna a mensagem específica e encerra.
        echo json_encode(['success' => false, 'message' => 'Usuário inativo. Por favor, entre em contato com o administrador.']);
        exit();
    }
    
    // Se o usuário estiver ativo, o código continua para verificar a senha.
    if ($senha === $user['senha']) {
        // Define variáveis de sessão para o usuário logado
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
        $_SESSION['redefinir_senha_obrigatoria'] = false;

        // Verifica se a senha é a padrão ou se precisa ser alterada
        if ($user['senha'] === '12345' || $user['senha_alterada'] == 0) {
            $_SESSION['redefinir_senha_obrigatoria'] = true;
            echo json_encode([
                'success' => true,
                'message' => 'Sua senha é padrão ou precisa ser alterada. Redirecionando para alteração de senha.',
                'needsPasswordChange' => true,
                'userType' => $user['tipo_usuario'],
                'redirectUrl' => 'menu.php'
            ]);
        } else if ($user['tipo_usuario'] === 'tecnico') {
            // Se for técnico e a senha já foi alterada, redireciona para a página do técnico
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso! Redirecionando para as manutenções.',
                'needsPasswordChange' => false,
                'redirectUrl' => 'manutencao_tecnico.php'
            ]);
        } else {
            // Login bem-sucedido para outros tipos de usuário
            echo json_encode([
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'needsPasswordChange' => false,
                'redirectUrl' => 'menu.php'
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