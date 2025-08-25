<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (
    !isset($data['tipo_equip']) || !isset($data['nome_equip']) ||
    !isset($data['status']) || !isset($data['id_cidade']) ||
    !isset($data['logradouro']) || !isset($data['bairro']) ||
    !isset($data['id_provedor'])
) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

$conn->begin_transaction();

try {
    $stmt_endereco = $conn->prepare("INSERT INTO endereco (logradouro, bairro, cep, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
    $cep = $data['cep'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $stmt_endereco->bind_param("sssdd", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude);
    $stmt_endereco->execute();
    $id_endereco = $conn->insert_id;
    $stmt_endereco->close();

    $stmt_equipamento = $conn->prepare("INSERT INTO equipamentos (tipo_equip, nome_equip, referencia_equip, status, qtd_faixa, km, sentido, id_cidade, id_endereco, id_provedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $referencia_equip = $data['referencia_equip'] ?? null;
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $km = $data['km'] ?? null;
    $sentido = $data['sentido'] ?? null;
    $id_provedor = (int)$data['id_provedor'];
    $id_cidade = (int)$data['id_cidade'];
    
    $stmt_equipamento->bind_param("ssssisssii", 
        $data['tipo_equip'], 
        $data['nome_equip'], 
        $referencia_equip, 
        $data['status'], 
        $qtd_faixa,
        $km,
        $sentido,
        $id_cidade, 
        $id_endereco, 
        $id_provedor
    );
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento adicionado com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    if ($e->getCode() == 1062) {
        if (strpos($e->getMessage(), 'referencia_equip') !== false) {
            echo json_encode(['success' => false, 'message' => 'Erro: A Referência informada já está em uso por outro equipamento.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro: Já existe um registro com um dos valores informados.']);
        }
    } else {
        error_log("Erro ao adicionar equipamento: " . $e->getMessage()); 
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
    }
}

$conn->close();
?>