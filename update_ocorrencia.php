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

    } elseif ($action === 'concluir_provedor') { 
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        if (empty($reparo_finalizado)) {
            throw new Exception('A descrição do reparo finalizado é obrigatória.');
        }

        $stmt = $conn->prepare(
            "UPDATE manutencoes 
             SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ? 
             WHERE id_manutencao = ?"
        );
        $stmt->bind_param('si', $reparo_finalizado, $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a ocorrência do provedor.');
        }
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Ocorrência do provedor concluída com sucesso.']);

    } elseif ($action === 'concluir_reparo') {
        $reparo_finalizado = $input['reparo_finalizado'] ?? null;
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];

        if (empty($reparo_finalizado) || empty($inicio_reparo) || empty($fim_reparo) || empty($tecnicos) || empty($veiculos)) {
            throw new Exception('Todos os campos são obrigatórios para concluir o reparo.');
        }

        // 1. Atualiza a tabela principal de manutenções
        $stmt = $conn->prepare("UPDATE manutencoes SET status_reparo = 'concluido', fim_reparo = NOW(), reparo_finalizado = ? WHERE id_manutencao = ?");
        $stmt->bind_param('si', $reparo_finalizado, $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao concluir a manutenção principal.');
        }
        $stmt->close();

        // 2. Apaga as associações antigas de técnicos e veículos
        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        // 3. Insere as novas associações (já concluídas)
        $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT, status_tecnico) VALUES (?, ?, ?, ?, ?, 'concluido')");
        
        $veiculos_count = count($veiculos);
        $i = 0;
        foreach ($tecnicos as $id_tecnico) {
            $id_veiculo_associado = $veiculos_count > 0 ? $veiculos[$i % $veiculos_count] : null;
            
            $stmt->bind_param('iiiss', $id_manutencao, $id_tecnico, $id_veiculo_associado, $inicio_reparo, $fim_reparo);
            if (!$stmt->execute()) {
                throw new Exception('Falha ao inserir novo técnico/veículo para a conclusão.');
            }
            $i++;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Reparo concluído e registrado com sucesso.']);


    } elseif ($action === 'edit' || $action === 'assign') {
        $ocorrencia = $input['ocorrencia_reparo'] ?? '';
        $inicio_reparo = $input['inicio_reparo'] ?? null;
        $fim_reparo = $input['fim_reparo'] ?? null;
        $tecnicos = $input['tecnicos'] ?? [];
        $veiculos = $input['veiculos'] ?? [];

        if ($action === 'assign') {
            $stmt = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ?, status_reparo = 'em andamento' WHERE id_manutencao = ?");
            $stmt->bind_param('si', $ocorrencia, $id_manutencao);
        } else {
            $stmt = $conn->prepare("UPDATE manutencoes SET ocorrencia_reparo = ? WHERE id_manutencao = ?");
            $stmt->bind_param('si', $ocorrencia, $id_manutencao);
        }
        if (!$stmt->execute()) {
            throw new Exception('Falha ao atualizar a ocorrência.');
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM manutencoes_tecnicos WHERE id_manutencao = ?");
        $stmt->bind_param('i', $id_manutencao);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao limpar técnicos e veículos antigos.');
        }
        $stmt->close();

        if (!empty($tecnicos)) {
            $stmt = $conn->prepare("INSERT INTO manutencoes_tecnicos (id_manutencao, id_tecnico, id_veiculo, inicio_reparoTec, fim_reparoT) VALUES (?, ?, ?, ?, ?)");
            
            $veiculos_count = count($veiculos);
            $i = 0;
            foreach ($tecnicos as $id_tecnico) {
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