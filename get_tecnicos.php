<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$tecnicos = [];
// Seleciona usuários que são do tipo 'tecnico' e os ordena por nome
$sql = "SELECT id_usuario, nome FROM Usuario WHERE tipo_usuario = 'tecnico' ORDER BY nome ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tecnicos[] = $row;
    }
    echo json_encode(['success' => true, 'tecnicos' => $tecnicos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhum técnico encontrado.']);
}

$conn->close();
?>
