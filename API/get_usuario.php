<?php
// Define os cabeçalhos para permitir requisições de outras origens e retornar JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => '', 'users' => []];

// Obtém o termo de pesquisa da requisição GET, se existir.
// Se não existir, a pesquisa será vazia, e todos os usuários serão retornados.
$searchTerm = $_GET['search'] ?? '';

try {
    // Prepara a consulta SQL para buscar usuários por nome ou e-mail.
    // O operador LIKE com % permite a pesquisa por parte da string.
    $sql = "SELECT id_usuario, nome, email, telefone, tipo_usuario, status_usuario FROM usuario WHERE nome LIKE ? OR email LIKE ? ORDER BY nome ASC";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception("Erro ao preparar a consulta: " . $conn->error);
    }

    // Cria os parâmetros de pesquisa com o caractere curinga '%'
    $param = "%" . $searchTerm . "%";
    $stmt->bind_param("ss", $param, $param);

    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $response['success'] = true;
    $response['users'] = $users;
    
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
} finally {
    $conn->close();
}

echo json_encode($response);
?>
