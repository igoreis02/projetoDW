<?php
session_start();
header('Content-Type: application/json');


require_once 'conexao_bd.php';

$user_id = $_GET['user_id'] ?? null;

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário é obrigatório.']);
    exit();
}

try {
    // --- BLOCO DE CÁLCULO DE CHECKSUM ---
    $tables = ['manutencoes', 'manutencoes_tecnicos'];
    $totalChecksum = 0;
    foreach ($tables as $table) {
        $result = $conn->query("CHECKSUM TABLE `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            $totalChecksum += (int)$row['Checksum'];
        } else {
            throw new Exception("Erro ao calcular checksum para a tabela: $table");
        }
    }


    $manutencoes = [];

    $sql = "SELECT DISTINCT
                m.id_manutencao,m.id_equipamento, m.inicio_reparo, e.nome_equip, e.referencia_equip,
                m.ocorrencia_reparo, m.tipo_manutencao, m.status_reparo,
                m.etiqueta_feita,
                m.observacao_instalacao,
                c.nome AS cidade_nome, m.id_cidade,
                a.latitude, a.longitude, a.logradouro,
                mt.inicio_reparoTec, mt.fim_reparoT,
                m.inst_laco, m.inst_base, m.inst_infra, m.inst_energia,
                m.dt_laco, m.dt_base, m.data_infra, m.dt_energia,
                e.tipo_equip, e.id_provedor
            FROM manutencoes m
            JOIN manutencoes_tecnicos mt ON m.id_manutencao = mt.id_manutencao
            JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
            JOIN cidades c ON m.id_cidade = c.id_cidade
            LEFT JOIN endereco a ON e.id_endereco = a.id_endereco
            WHERE mt.id_tecnico = ? AND (
                -- Condição 1: ... (mantida como está)
                (m.tipo_manutencao != 'instalação' AND m.status_reparo = 'em andamento')
                OR
                -- Condição 2 (MODIFICADA): Mostra a instalação APENAS se alguma etapa aplicável estiver pendente
                (
                    m.tipo_manutencao = 'instalação' AND m.status_reparo = 'em andamento' AND
                    CASE
                        -- Para CCO e DOME, verifica se infra OU energia estão pendentes
                        WHEN e.tipo_equip LIKE '%CCO%' OR e.tipo_equip LIKE '%DOME%'
                            THEN (m.inst_infra = 0 OR m.inst_energia = 0)
                        -- Para VÍDEO e LAP, verifica se base, infra OU energia estão pendentes
                        WHEN e.tipo_equip LIKE '%VÍDEO MONITORAMENTO%' OR e.tipo_equip LIKE '%LAP%'
                            THEN (m.inst_base = 0 OR m.inst_infra = 0 OR m.inst_energia = 0)
                        -- Para todos os outros, verifica se laço, base, infra OU energia estão pendentes
                        ELSE
                            (m.inst_laco = 0 OR m.inst_base = 0 OR m.inst_infra = 0 OR m.inst_energia = 0)
                    END
                )
            )
            ORDER BY c.nome DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Erro ao preparar a consulta: ' . $conn->error);
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
    }
    
    echo json_encode([
        'success' => true, 
        'checksum' => $totalChecksum,
        'manutencoes' => $manutencoes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}

$conn->close();
?>