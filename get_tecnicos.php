<?php
header('Content-Type: application/json');

require_once 'conexao_bd.php';

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