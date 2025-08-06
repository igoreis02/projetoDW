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
if (isset($data['id']) && isset($data['nome']) && isset($data['email']) && isset($data['telefone']) && isset($data['tipo_usuario']) && isset($data['status_usuario'])) {
    $id_usuario = $data['id'];
    $nome = $data['nome'];
    $email = $data['email'];
    $telefone = $data['telefone'];
    $tipo_usuario = $data['tipo_usuario'];
    $status_usuario = $data['status_usuario'];

    try {
        // Prepara a consulta SQL para atualizar o usuário
        $stmt = $conn->prepare("UPDATE Usuario SET nome = ?, email = ?, telefone = ?, tipo_usuario = ?, status_usuario = ? WHERE id_usuario = ?");
        
        if ($stmt === false) {
            throw new Exception("Erro ao preparar a consulta: " . $conn->error);
        }

        // 'sssssi' -> s: string, i: integer (para id_usuario)
        $stmt->bind_param("sssssi", $nome, $email, $telefone, $tipo_usuario, $status_usuario, $id_usuario); 

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Usuário atualizado com sucesso!';
            } else {
                $response['message'] = 'Nenhuma alteração foi feita ou usuário não encontrado.';
            }
        } else {
            throw new Exception("Erro ao executar a atualização: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        $response['message'] = 'Erro no servidor: ' . $e->getMessage();
        error_log("Erro em update_user.php: " . $e->getMessage());
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
} else {
    $response['message'] = 'Dados incompletos para a atualização do usuário.';
}

echo json_encode($response);
?>
