<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$user_id = $_GET['user_id'] ?? null;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário é obrigatório.']);
    exit();
}

$manutencoes = [];
// A consulta agora busca manutenções que estão 'em andamento' e atribuídas ao técnico via manutencoes_tecnicos
$sql = "SELECT
            m.id_manutencao,
            m.inicio_reparo,
            e.nome_equip,
            e.referencia_equip,
            m.ocorrencia_reparo,
            m.tipo_manutencao,
            m.status_reparo,
            c.nome AS cidade_nome,
            a.latitude,  -- Latitude do endereço do equipamento
            a.longitude  -- Longitude do endereço do equipamento
        FROM
            manutencoes m
        JOIN
            manutencoes_tecnicos mt ON m.id_manutencao = mt.id_manutencao
        JOIN
            equipamentos e ON m.id_equipamento = e.id_equipamento
        JOIN
            cidades c ON m.id_cidade = c.id_cidade
        LEFT JOIN
            endereco a ON e.id_endereco = a.id_endereco -- Junta com a tabela de endereço
        WHERE
            mt.id_tecnico = ? AND m.status_reparo = 'em andamento'
        ORDER BY m.inicio_reparo DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $manutencoes[] = $row;
    }
    echo json_encode(['success' => true, 'manutencoes' => $manutencoes]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma manutenção em andamento atribuída a você.']);
}

$stmt->close();
$conn->close();
?>
