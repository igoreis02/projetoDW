<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexao_bd.php';

$user_id = $_GET['user_id'] ?? null;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário é obrigatório.']);
    exit();
}

$manutencoes = [];

$sql = "SELECT DISTINCT
            m.id_manutencao, m.inicio_reparo, e.nome_equip, e.referencia_equip,
            m.ocorrencia_reparo, m.tipo_manutencao, m.status_reparo,
            m.observacao_instalacao,
            c.nome AS cidade_nome, a.latitude, a.longitude, a.logradouro,
            mt.inicio_reparoTec, mt.fim_reparoT,
            m.inst_laco, m.inst_base, m.inst_infra, m.inst_energia,
            -- DATAS DE INSTALAÇÃO ADICIONADAS AQUI --
            m.dt_laco, m.dt_base, m.data_infra, m.dt_energia
        FROM manutencoes m
        JOIN manutencoes_tecnicos mt ON m.id_manutencao = mt.id_manutencao
        JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
        JOIN cidades c ON m.id_cidade = c.id_cidade
        LEFT JOIN endereco a ON e.id_endereco = a.id_endereco
        WHERE mt.id_tecnico = ? AND m.status_reparo = 'em andamento'
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
        $sql_veiculos = "SELECT DISTINCT v.nome, v.placa FROM manutencoes_tecnicos mt 
                         JOIN veiculos v ON mt.id_veiculo = v.id_veiculo 
                         WHERE mt.id_manutencao = ?";
        
        $stmt_veiculos = $conn->prepare($sql_veiculos);
        $stmt_veiculos->bind_param("i", $row['id_manutencao']);
        $stmt_veiculos->execute();
        $result_veiculos = $stmt_veiculos->get_result();
        
        $veiculos = [];
        while($veiculo = $result_veiculos->fetch_assoc()) {
            $veiculos[] = $veiculo['nome'] . ' - ' . $veiculo['placa'];
        }
        
        $row['veiculos_info'] = implode(' | ', $veiculos);
        $stmt_veiculos->close();
        $manutencoes[] = $row;
    }
    echo json_encode(['success' => true, 'manutencoes' => $manutencoes]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nenhuma manutenção em andamento atribuída a você.']);
}

$stmt->close();
$conn->close();
?>