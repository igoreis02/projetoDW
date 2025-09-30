<?php
header('Content-Type: application/json');


// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os dados da requisição POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$nome_equip = $data['nome_equip'] ?? null;
$referencia_equip = $data['referencia_equip'] ?? null;
$id_cidade = $data['id_cidade'] ?? null;
$id_endereco = $data['id_endereco'] ?? null;
$tipo_equip = $data['tipo_equip'] ?? null;
$qtd_faixa = $data['qtd_faixa'] ?? null;
$sentido = $data['sentido'] ?? null; 
$km = $data['km'] ?? null; 
$id_provedor = $data['id_provedor'] ?? null; 
// Validação básica
if (empty($nome_equip) || empty($referencia_equip) || empty($id_cidade) || empty($id_endereco) || empty($tipo_equip)) {
    echo json_encode(['success' => false, 'message' => 'Dados de equipamento incompletos.']);
    exit();
}

if (empty($qtd_faixa)) {
    $qtd_faixa = null;
}

$status = 'inativo'; // Define o status padrão como 'inativo' para novas instalações

$sql = "INSERT INTO equipamentos (nome_equip, referencia_equip, id_cidade, id_endereco, id_provedor, tipo_equip, qtd_faixa, sentido, Km, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ssiiisisss", $nome_equip, $referencia_equip, $id_cidade, $id_endereco, $id_provedor, $tipo_equip, $qtd_faixa, $sentido, $km, $status);

if ($stmt->execute()) {
    $last_id = $conn->insert_id;
    echo json_encode(['success' => true, 'message' => 'Equipamento cadastrado com sucesso!', 'id_equipamento' => $last_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar equipamento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>