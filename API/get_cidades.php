<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

// Configurações do banco de dados
// ATENÇÃO: Substitua 'localhost', 'root', '' e 'gerenciamento_manutencoes' pelos seus dados reais
require_once 'conexao_bd.php';

$cidades = [];
// Adicionado `cod_cidade` e `sigla_cidade` à consulta SQL
$sql = "SELECT id_cidade, nome, cod_cidade, sigla_cidade FROM cidades ORDER BY nome ASC"; 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cidades[] = $row;
    }
    echo json_encode(['success' => true, 'cidades' => $cidades]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma cidade encontrada.']);
}

$conn->close();
?>