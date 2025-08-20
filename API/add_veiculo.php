<?php
session_start();
header('Content-Type: application/json');

// Verifica se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

// Pega os dados JSON enviados pelo JavaScript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Valida se os campos necessários foram enviados
if (!isset($data['nome']) || !isset($data['placa']) || !isset($data['modelo'])) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit();
}

$nome = $data['nome'];
$placa = $data['placa'];
$modelo = $data['modelo'];

try {
    // Prepara a consulta SQL para inserir o novo veículo
    $stmt = $conn->prepare("INSERT INTO veiculos (nome, placa, modelo) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nome, $placa, $modelo);
    
    // Executa a consulta
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Veículo adicionado com sucesso!']);
    } else {
        // Captura o erro específico do banco de dados
        throw new Exception($stmt->error, $stmt->errno);
    }
    $stmt->close();

} catch (Exception $e) {
    // Verifica se o erro é de placa duplicada (código 1062)
    if ($e->getCode() == 1062) {
         echo json_encode(['success' => false, 'message' => 'Erro: A placa informada já está cadastrada.']);
    } else {
         // Para outros erros de banco de dados
         echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
}

$conn->close();
?>