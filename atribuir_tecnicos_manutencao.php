<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Obtém os dados JSON da requisição POST
$input = file_get_contents('php://input');
$dados_post = json_decode($input, true);

// Extrai os dados do array, usando chaves em português para maior consistência
$id_manutencao = $dados_post['id_manutencao'] ?? null;
$ids_tecnicos_selecionados = $dados_post['ids_tecnicos'] ?? [];
$ids_veiculos_selecionados = $dados_post['ids_veiculos'] ?? [];
$datahora_inicio = $dados_post['datahora_inicio'] ?? null;
$datahora_fim = $dados_post['datahora_fim'] ?? null;

// Validação dos dados para garantir que nada esteja faltando
if (empty($id_manutencao) || empty($ids_tecnicos_selecionados) || empty($ids_veiculos_selecionados) || empty($datahora_inicio) || empty($datahora_fim)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    exit();
}

// Valida se o número de técnicos e veículos é o mesmo
if (count($ids_tecnicos_selecionados) !== count($ids_veiculos_selecionados)) {
    echo json_encode(['success' => false, 'message' => 'O número de técnicos e veículos selecionados deve ser o mesmo.']);
    exit();
}

// Inicia uma transação para garantir que todas as operações sejam atômicas
$conn->begin_transaction();

try {
    // 1. Atualiza o status da manutenção para 'em andamento'
    $stmt_update_manutencao = $conn->prepare("UPDATE manutencoes SET status_reparo = 'em andamento' WHERE id_manutencao = ?");
    
    if ($stmt_update_manutencao === false) {
        throw new Exception("Erro ao preparar a instrução SQL de atualização da manutenção: " . $conn->error);
    }
    
    $stmt_update_manutencao->bind_param("i", $id_manutencao);
    if (!$stmt_update_manutencao->execute()) {
        throw new Exception("Erro ao atualizar o status da manutenção: " . $stmt_update_manutencao->error);
    }
    $stmt_update_manutencao->close();

    // 2. Deleta as atribuições existentes para a manutenção, se houver
    $stmt_delete_tecnicos = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
    
    if ($stmt_delete_tecnicos === false) {
        throw new Exception("Erro ao preparar a instrução SQL de exclusão de técnicos: " . $conn->error);
    }
    
    $stmt_delete_tecnicos->bind_param("i", $id_manutencao);
    if (!$stmt_delete_tecnicos->execute()) {
        throw new Exception("Erro ao excluir atribuições existentes: " . $stmt_delete_tecnicos->error);
    }
    $stmt_delete_tecnicos->close();

    // 3. Insere as novas atribuições de técnicos e veículos na tabela de junção
    $stmt_insert_tecnicos = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_atribuicao, fim_atribuicao) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt_insert_tecnicos === false) {
        throw new Exception("Erro ao preparar a instrução SQL de inserção de técnicos: " . $conn->error);
    }

    for ($i = 0; $i < count($ids_tecnicos_selecionados); $i++) {
        $tecnico_id = (int)$ids_tecnicos_selecionados[$i];
        $veiculo_id = (int)$ids_veiculos_selecionados[$i];
        
        $stmt_insert_tecnicos->bind_param("iiiss", $id_manutencao, $tecnico_id, $veiculo_id, $datahora_inicio, $datahora_fim);
        
        if (!$stmt_insert_tecnicos->execute()) {
            throw new Exception("Erro ao inserir técnico e veículo: " . $stmt_insert_tecnicos->error);
        }
    }
    
    $stmt_insert_tecnicos->close();

    $conn->commit(); // Confirma a transação
    echo json_encode(['success' => true, 'message' => 'Atribuição realizada com sucesso.']);
} catch (Exception $e) {
    $conn->rollback(); // Desfaz a transação em caso de erro
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
