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
    !isset($data['id_provedor']) || 
    empty($data['num_instrumento']) || empty($data['dt_afericao'])
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

    // --- LÓGICA ADICIONADA ---
    // Pega os novos campos e calcula a data de vencimento
    $num_instrumento = $data['num_instrumento'] ?? null;
    $dt_afericao = $data['dt_afericao'] ?? null;
    $dt_vencimento = null;

    if (!empty($dt_afericao)) {
        try {
            $date = new DateTime($dt_afericao);
            $date->modify('+1 year');
            $date->modify('-1 day');
            $dt_vencimento = $date->format('Y-m-d');
        } catch (Exception $e) {
            // Se a data for inválida, o vencimento permanece nulo
            $dt_vencimento = null;
        }
    }
    // --- FIM DA LÓGICA ADICIONADA ---


    // 2. Inserir na tabela `equipamentos` (com os novos campos)
    $stmt_equipamento = $conn->prepare("INSERT INTO equipamentos (tipo_equip, nome_equip, referencia_equip, status, qtd_faixa, km, sentido, num_instrumento, dt_afericao, dt_vencimento, id_cidade, id_endereco, id_provedor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $referencia_equip = $data['referencia_equip'] ?? null;
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $km = $data['km'] ?? null;
    $sentido = $data['sentido'] ?? null;
    $id_provedor = (int)$data['id_provedor'];
    $id_cidade = (int)$data['id_cidade'];
    
    // bind_param atualizado para incluir os novos campos
    $stmt_equipamento->bind_param("ssssissssssii", 
        $data['tipo_equip'], 
        $data['nome_equip'], 
        $referencia_equip, 
        $data['status'], 
        $qtd_faixa,
        $km,
        $sentido,
        $num_instrumento,      // Novo
        $dt_afericao,          // Novo
        $dt_vencimento,        // Novo
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
        // Verifica se a duplicata foi na chave de nome ou referência
        if (strpos($e->getMessage(), 'idx_nome_cidade_unica') !== false || strpos($e->getMessage(), 'idx_referencia_cidade_unica') !== false) {
            echo json_encode(['success' => false, 'message' => 'EQUIPAMENTO CADASTRADO COM ESSE NOME OU REFERENCIA.']);
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
?>