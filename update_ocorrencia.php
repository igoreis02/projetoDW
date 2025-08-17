<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// --- Configurações do Banco de Dados ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes";

// --- Conexão com o Banco de Dados ---
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
    exit();
}

// Pega o corpo da requisição POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados de entrada inválidos.']);
    exit();
}

$action = $input['action'] ?? null;
$id_manutencao = $input['id_manutencao'] ?? null;

if (empty($action) || empty($id_manutencao)) {
    echo json_encode(['success' => false, 'message' => 'Ação e ID da manutenção são obrigatórios.']);
    exit();
}

$conn->begin_transaction();

try {
    if ($action === 'update_status') {
        $new_status = $input['status'] ?? null;
        if (empty($new_status) || !in_array($new_status, ['pendente', 'cancelado'])) {
            throw new Exception('Status inválido fornecido.');
        }

        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = ? WHERE id_manutencao = ?");
        $stmt->bind_param('si', $new_status, $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar o status da manutenção.');
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso para ' . $new_status]);

    } elseif ($action === 'edit' || $action === 'assign') { // Agora trata 'assign' também
        $ocorrencia = $input['ocorrencia_reparo'] ?? '';
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];

        // 1. Atualiza a tabela principal de manutenções
        if ($action === 'assign') {
            // Se for uma atribuição, muda o status para 'em andamento'
            $stmt = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ?, status_reparo = 'em andamento' WHERE id_manutencao = ?");
            $stmt->bind_param('si', $ocorrencia, $id_manutencao);
        } else {
            // Se for uma edição normal, apenas atualiza a ocorrência
            $stmt = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ? WHERE id_manutencao = ?");
            $stmt->bind_param('si', $ocorrencia, $id_manutencao);
        }
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar a ocorrência.');
        }
        $stmt->close();

        // 2. Apaga as associações antigas de técnicos e veículos
        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        // 3. Insere as novas associações de técnicos e veículos
        if (!empty($tecnicos)) {
            $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT) VALUES (?, ?, ?, ?, ?)");
            
            $veiculos_count = count($veiculos);
            $i = 0;
            foreach ($tecnicos as $id_tecnico) {
                // Associa um veículo a cada técnico, fazendo um ciclo na lista de veículos se necessário
                $id_veiculo_associado = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
                
                $stmt->bind_param('iiiss', $id_manutencao, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
                if (!$stmt->execute()) {
                    throw new Exception('Falha ao inserir novo técnico/veículo.');
                }
                $i++;
            }
            $stmt->close();
        }
        
        $message = $action === 'assign' ? 'Ocorrência atribuída com sucesso.' : 'Ocorrência atualizada com sucesso.';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        throw new Exception('Ação desconhecida.');
    }

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
