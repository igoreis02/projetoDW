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
$data = json_decode($input, true);


$id_manutencao = $data['id_manutencao'] ?? null;
$selected_tecnicos_ids = $data['selected_tecnicos_ids'] ?? [];

// Validação básica dos dados
if (empty($id_manutencao)) {
    echo json_encode(['success' => false, 'message' => 'ID da manutenção é obrigatório.']);
    exit();
}

// Inicia uma transação
$conn->begin_transaction();

try {
    // 1. Atualiza o status da manutenção para 'em andamento'
    // A coluna id_tecnico foi removida da tabela manutencoes, então não tentamos mais atualizá-la aqui.
    // inicio_reparo é atualizado apenas se for NULL (COALESCE)
    $stmt_update_manutencao = $conn->prepare("UPDATE manutencoes SET status_reparo = 'em andamento', inicio_reparo = COALESCE(inicio_reparo, NOW()) WHERE id_manutencao = ?");
    
    if ($stmt_update_manutencao === false) {
        throw new Exception("Erro ao preparar a instrução SQL de atualização da manutenção: " . $conn->error);
    }
    
    $stmt_update_manutencao->bind_param("i", $id_manutencao);
    
    if (!$stmt_update_manutencao->execute()) {
        throw new Exception("Erro ao atualizar status da manutenção: " . $stmt_update_manutencao->error);
    }
    
    $stmt_update_manutencao->close();

    // 2. Remove as atribuições de técnicos existentes para esta manutenção na tabela de junção
    // Isso garante que se a atribuição for refeita, as antigas sejam limpas.
    $stmt_delete_tecnicos = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
    
    if ($stmt_delete_tecnicos === false) {
        throw new Exception("Erro ao preparar a instrução SQL de exclusão de técnicos: " . $conn->error);
    }
    
    $stmt_delete_tecnicos->bind_param("i", $id_manutencao);
    
    if (!$stmt_delete_tecnicos->execute()) {
        throw new Exception("Erro ao remover técnicos existentes: " . $stmt_delete_tecnicos->error);
    }
    
    $stmt_delete_tecnicos->close();

    // 3. Insere as novas atribuições de técnicos na tabela de junção
    if (!empty($selected_tecnicos_ids)) {
        $stmt_insert_tecnicos = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico) VALUES (?, ?)");
        
        if ($stmt_insert_tecnicos === false) {
            throw new Exception("Erro ao preparar a instrução SQL de inserção de técnicos: " . $conn->error);
        }

        foreach ($selected_tecnicos_ids as $tecnico_id) {
            // CORREÇÃO: Atribua o valor convertido a uma nova variável para passar por referência
            $tecnico_id_int = (int)$tecnico_id; 
            $stmt_insert_tecnicos->bind_param("ii", $id_manutencao, $tecnico_id_int); // Use $tecnico_id_int aqui
            if (!$stmt_insert_tecnicos->execute()) {
                throw new Exception("Erro ao inserir técnico " . $tecnico_id . ": " . $stmt_insert_tecnicos->error);
            }
        }
        $stmt_insert_tecnicos->close();
    }

    $conn->commit(); // Confirma a transação
    echo json_encode(['success' => true, 'message' => 'Técnico(s) atribuído(s) e status atualizado com sucesso!']);

} catch (Exception $e) {
    $conn->rollback(); // Reverte a transação em caso de erro
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Erro em atribuir_tecnicos_manutencao.php: " . $e->getMessage());
}

$conn->close();
?>
