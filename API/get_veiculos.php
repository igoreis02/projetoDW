<?php
header('Content-Type: application/json');

require_once 'conexao_bd.php';

$sql = "SELECT id_veiculo, nome, placa, modelo FROM veiculos";
$result = $conn->query($sql);

$veiculos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $veiculos[] = $row;
    }
}

echo json_encode($veiculos);

$conn->close();
?>
