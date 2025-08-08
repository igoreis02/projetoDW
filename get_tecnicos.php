<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
    exit();
}

// A consulta SQL agora usa o nome da tabela 'usuario' (minúsculo) e o alias 'id_tecnico'
$sql = "SELECT id_usuario AS id_tecnico, nome FROM usuario WHERE tipo_usuario = 'tecnico' ORDER BY nome ASC";
$result = $conn->query($sql);

$tecnicos = [];
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