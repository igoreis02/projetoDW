<?php
// Define os cabeçalhos para permitir requisições de outras origens e retornar JSON
header('Content-Type: application/json');

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];

// Verifica se a requisição é do tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe o corpo da requisição POST como JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Valida se os dados essenciais foram recebidos
    if (isset($data['nome']) && isset($data['email']) && isset($data['tipo_usuario'])) {
        $nome = $data['nome'];
        $email = $data['email'];
        $telefone = $data['telefone'] ?? null;
        $tipo_usuario = $data['tipo_usuario'];
        $status_usuario = 'ativo'; // Novo usuário começa como 'ativo'

        // Define uma senha padrão e a "hashea" para segurança
        // ATENÇÃO: Esta é uma senha padrão. É altamente recomendado que o sistema
        // tenha uma funcionalidade de "primeiro login" para o usuário alterar a senha.
        $defaultPassword = '123';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

        try {
            // Prepara a consulta SQL para inserção de um novo usuário
            $sql = "INSERT INTO usuario (nome, email, telefone, tipo_usuario, status_usuario, senha) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                throw new Exception("Erro ao preparar a consulta: " . $conn->error);
            }

            // 'ssssss' indica o tipo dos parâmetros: 6 strings
            $stmt->bind_param("ssssss", $nome, $email, $telefone, $tipo_usuario, $status_usuario, $hashedPassword);

            // Executa a consulta
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Usuário adicionado com sucesso!';
            } else {
                throw new Exception("Erro ao adicionar usuário: " . $stmt->error);
            }

            $stmt->close();
        } catch (Exception $e) {
            // Captura qualquer exceção e retorna uma mensagem de erro
            $response['message'] = $e->getMessage();
        }
    } else {
        $response['message'] = 'Dados incompletos para adicionar o usuário.';
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

// Fecha a conexão com o banco de dados
$conn->close();

// Retorna a resposta em formato JSON
echo json_encode($response);
?>
