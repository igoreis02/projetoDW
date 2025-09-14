<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$provedores = [];
try {
    // MODIFICADO: Adicionamos o campo id_cidade à consulta
    $sql = "SELECT id_provedor, nome_prov, id_cidade FROM provedor ORDER BY nome_prov ASC";
    $result = $conn->query($sql);

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $provedores[] = $row;
            }
            echo json_encode(['success' => true, 'provedores' => $provedores]);
        } else {
            echo json_encode(['success' => true, 'provedores' => []]); // Retorna sucesso com array vazio
        }
    } else {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar provedores: ' . $e->getMessage()]);
}

$conn->close();
?>