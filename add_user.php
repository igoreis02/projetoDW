<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configurações do banco de dados (diretamente no script)
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

$response = ['success' => false, 'message' => ''];

// Pega o corpo da requisição JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verifica se os dados necessários foram recebidos
if (isset($data['nome']) && isset($data['email']) && isset($data['telefone']) && isset($data['tipo_usuario']) && isset($data['senha']) && isset($data['status_usuario'])) {
    $nome = $data['nome'];
    $email = $data['email']; // E-mail agora é gerado no frontend
    $telefone = $data['telefone'];
    $tipo_usuario = $data['tipo_usuario'];
    $senha = $data['senha']; // Senha padrão '12345'
    $status_usuario = $data['status_usuario']; // Status padrão 'ativo'

    // A flag senha_alterada deve ser FALSE (0) para a senha padrão
    $senha_alterada = 0; 

    try {
        // Verifica se o e-mail já existe (ainda importante para garantir unicidade)
        $stmt_check = $conn->prepare("SELECT id_usuario FROM Usuario WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $response['message'] = 'E-mail gerado já cadastrado. Por favor, tente um nome diferente ou ajuste manualmente se necessário.';
            echo json_encode($response);
            $stmt_check->close();
            $conn->close();
            exit();
        }
        $stmt_check->close();

        // Insere o novo usuário na tabela Usuario
        $stmt = $conn->prepare("INSERT INTO Usuario (nome, email, senha, telefone, tipo_usuario, status_usuario, senha_alterada) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt === false) {
            throw new Exception("Erro ao preparar a consulta: " . $conn->error);
        }

        // 'ssssssi' -> s: string, i: integer (para senha_alterada)
        $stmt->bind_param("ssssssi", $nome, $email, $senha, $telefone, $tipo_usuario, $status_usuario, $senha_alterada); 

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Usuário adicionado com sucesso!';
        } else {
            throw new Exception("Erro ao inserir usuário: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = 'Erro no servidor: ' . $e->getMessage();
        error_log("Erro em add_user.php: " . $e->getMessage());
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Dados incompletos para o cadastro do usuário.';
}

echo json_encode($response);
?>
