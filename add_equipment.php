<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

// Recebe os dados do corpo da requisição JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação básica dos dados
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
    // 1. Inserir na tabela `endereco`
    $stmt_endereco = $conn->prepare("INSERT INTO endereco (logradouro, bairro, cep, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
    $cep = $data['cep'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $stmt_endereco->bind_param("sssdd", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude);
    $stmt_endereco->execute();
    $id_endereco = $conn->insert_id;
    $stmt_endereco->close();

    // 2. Inserir na tabela `equipamentos`
    // CORREÇÃO: Adicionado o oitavo '?' para corresponder às 8 colunas
    $stmt_equipamento = $conn->prepare("INSERT INTO equipamentos (tipo_equip, nome_equip, referencia_equip, status, qtd_faixa, id_cidade, id_endereco, id_provedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $referencia_equip = $data['referencia_equip'] ?? null;
    // Garante que qtd_faixa seja null se não for enviado, para evitar problemas com o bind_param
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $id_provedor = (int)$data['id_provedor'];
    $id_cidade = (int)$data['id_cidade'];
    
    // O bind_param precisa da quantidade correta de tipos e variáveis
    $stmt_equipamento->bind_param("ssssiiii", 
        $data['tipo_equip'], 
        $data['nome_equip'], 
        $referencia_equip, 
        $data['status'], 
        $qtd_faixa, 
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
    // Verifica se o erro é de entrada duplicada (código 1062)
    if ($e->getCode() == 1062) {
        // Verifica se a duplicata foi na chave 'referencia_equip'
        if (strpos($e->getMessage(), 'referencia_equip') !== false) {
            echo json_encode(['success' => false, 'message' => 'Erro: A Referência informada já está em uso por outro equipamento.']);
        } else {
            // Caso seja outra chave única no futuro
            echo json_encode(['success' => false, 'message' => 'Erro: Já existe um registro com um dos valores informados.']);
        }
    } else {
        // Para todos os outros erros de banco de dados
        error_log("Erro ao adicionar equipamento: " . $e->getMessage()); 
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
    }
}

$conn->close();