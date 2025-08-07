<?php
// Define os cabeçalhos para permitir requisições de outras origens e retornar JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodifica o JSON recebido
    $data = json_decode(file_get_contents("php://input"), true);

    // Validação básica dos dados
    if (!isset($data['id']) || !isset($data['nome']) || !isset($data['email']) || !isset($data['tipo_usuario']) || !isset($data['status_usuario'])) {
        $response['message'] = 'Dados incompletos para a atualização.';
        echo json_encode($response);
        exit;
    }

    $userId = $data['id'];
    $nome = $data['nome'];
    $email = $data['email'];
    $telefone = $data['telefone'] ?? null;
    $tipoUsuario = $data['tipo_usuario'];
    $statusUsuario = $data['status_usuario'];
    $senha = $data['senha'] ?? null; // Senha é opcional

    try {
        // Inicia a consulta SQL base para a atualização
        $sql = "UPDATE Usuario SET nome = ?, email = ?, telefone = ?, tipo_usuario = ?, status_usuario = ?";
        $params = ["sssss", $nome, $email, $telefone, $tipoUsuario, $statusUsuario];

        // Se uma nova senha for fornecida, adicione-a à consulta
        if (!empty($senha)) {
            // Hash da senha para segurança
            $senhaHashed = password_hash($senha, PASSWORD_DEFAULT);
            $sql .= ", senha = ?";
            $params[0] .= "s"; // Adiciona o tipo 'string' para a senha
            $params[] = $senhaHashed;
        }

        // Finaliza a consulta com a cláusula WHERE
        $sql .= " WHERE id_usuario = ?";
        $params[0] .= "i"; // Adiciona o tipo 'integer' para o id
        $params[] = $userId;

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception("Erro ao preparar a consulta: " . $conn->error);
        }

        // Faz o binding dos parâmetros dinamicamente
        $stmt->bind_param(...$params);

        if ($stmt->execute()) {
            // Verifica se alguma linha foi realmente afetada
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Usuário atualizado com sucesso!';
            } else {
                $response['message'] = 'Nenhuma alteração foi realizada. Os dados fornecidos são os mesmos.';
            }
        } else {
            $response['message'] = 'Erro ao executar a atualização: ' . $stmt->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = 'Erro: ' . $e->getMessage();
    } finally {
        $conn->close();
        echo json_encode($response);
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
    echo json_encode($response);
}
?>
