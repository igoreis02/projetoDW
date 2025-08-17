<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Configurações do banco de dados
require_once 'conexao_bd.php';

// Obtém os dados da requisição POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$nome_equip = $data['nome_equip'] ?? null;
$referencia_equip = $data['referencia_equip'] ?? null;
$id_cidade = $data['id_cidade'] ?? null;
$id_endereco = $data['id_endereco'] ?? null;
$tipo_equip = $data['tipo_equip'] ?? null; // NOVO CAMPO
$qtd_faixa = $data['qtd_faixa'] ?? null;   // NOVO CAMPO

// Validação básica
if (empty($nome_equip) || empty($referencia_equip) || empty($id_cidade) || empty($id_endereco) || empty($tipo_equip)) {
    echo json_encode(['success' => false, 'message' => 'Dados de equipamento incompletos.']);
    exit();
}

// Se a quantidade de faixas não for enviada (tipos que não a exigem), define como NULL
if (empty($qtd_faixa)) {
    $qtd_faixa = null;
}

$status = 'instalacao'; // Define o status padrão como 'instalacao'
// Prepara a instrução SQL para inserção
$sql = "INSERT INTO equipamentos (nome_equip, referencia_equip, id_cidade, id_endereco, tipo_equip, qtd_faixa, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a instrução SQL: ' . $conn->error]);
    exit();
}

// Vincula os parâmetros
// 'ssiisi' -> s(string), s(string), i(integer), i(integer), s(string), i(integer), s(string)
$stmt->bind_param("ssiisis", $nome_equip, $referencia_equip, $id_cidade, $id_endereco, $tipo_equip, $qtd_faixa, $status);

// Executa a instrução
if ($stmt->execute()) {
    $last_id = $conn->insert_id; // Obtém o ID do equipamento recém-inserido
    echo json_encode(['success' => true, 'message' => 'Equipamento cadastrado com sucesso!', 'id_equipamento' => $last_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar equipamento: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>