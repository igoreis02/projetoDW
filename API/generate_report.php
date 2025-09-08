<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php'; // Eu incluo minha conexão com o banco

// Eu pego todos os filtros que enviei pela URL
$cityId = $_GET['city'] ?? 'todos';
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$reportType = $_GET['reportType'] ?? 'matriz_tecnica';
$status = $_GET['status'] ?? 'todos';

$params = [];
$types = '';
$sqlBase = ""; 
$headers = []; 

$sqlWhere = " WHERE 1=1";

// Eu escolho qual consulta fazer com base no tipo de relatório
// Apenas os relatórios de Matriz Técnica e Controle de Ocorrência usarão o novo formato.
switch ($reportType) {
    case 'matriz_tecnica':
    case 'controle_ocorrencia':
        // Eu defino os cabeçalhos que vou usar no Excel
        $headers = ["Cidade", "Equipamento", "Data Início", "Descrição Problema", "Data Fim", "Descrição Reparo", "Atendido em dia(s)", "Técnico"];
        
        // Eu monto a minha consulta SQL principal, já formatando os campos como preciso
        $sqlBase = "SELECT
                        c.nome as Cidade,
                        -- Eu pego só a primeira parte do nome (ex: 'MT427') e junto com a referência
                        CONCAT(SUBSTRING_INDEX(e.nome_equip, ' - ', 1), ' - ', e.referencia_equip) as Equipamento,
                        DATE_FORMAT(m.inicio_reparo, '%d/%m/%Y %H:%i') as 'Data Início',
                        m.ocorrencia_reparo as 'Descrição Problema',
                        DATE_FORMAT(m.fim_reparo, '%d/%m/%Y %H:%i') as 'Data Fim',
                        m.reparo_finalizado as 'Descrição Reparo',
                        -- Eu calculo a diferença em dias entre o fim e o início do reparo
                        DATEDIFF(m.fim_reparo, m.inicio_reparo) as 'Atendido em dia(s)',
                        (SELECT GROUP_CONCAT(u.nome SEPARATOR ', ') FROM manutencoes_tecnicos mt JOIN usuario u ON mt.id_tecnico = u.id_usuario WHERE mt.id_manutencao = m.id_manutencao) as Técnico
                    FROM manutencoes m
                    JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
                    JOIN cidades c ON e.id_cidade = c.id_cidade";
        
        // Eu aplico o filtro de tipo de manutenção que você pediu
        if ($reportType === 'controle_ocorrencia') {
            $sqlWhere .= " AND m.tipo_manutencao = 'preditiva'";
        } else { // Matriz Técnica
            $sqlWhere .= " AND m.tipo_manutencao = 'corretiva'";
        }
        
        $dateColumn = 'm.inicio_reparo';
        $statusColumn = 'm.status_reparo';
        $cityColumn = 'm.id_cidade';
        break;

    // Os outros relatórios continuam com o formato de tabela simples de antes
    case 'rel_processamento':
    case 'rel_provedor':
        // ... (código para os outros relatórios permanece o mesmo)
        if ($reportType === 'rel_processamento') {
             $headers = ["ID", "Equipamento", "Referência", "Cidade", "Problema", "Status", "Data Ocorrência", "Data Resolução"];
             $sqlBase = "SELECT op.id_ocorrencia_processamento as ID, e.nome_equip as Equipamento, e.referencia_equip as Referência, c.nome as Cidade, op.descricao as Problema, op.status as Status, DATE_FORMAT(op.dt_ocorrencia, '%d/%m/%Y %H:%i') as 'Data Ocorrência', DATE_FORMAT(op.dt_resolucao, '%d/%m/%Y %H:%i') as 'Data Resolução' FROM ocorrencia_processamento op JOIN manutencoes m ON op.id_manutencao = m.id_manutencao JOIN equipamentos e ON m.id_equipamento = e.id_equipamento JOIN cidades c ON e.id_cidade = c.id_cidade";
             $sqlWhere .= " AND m.tipo_manutencao = 'preditiva'";
             $dateColumn = 'op.dt_ocorrencia'; $statusColumn = 'op.status'; $cityColumn = 'e.id_cidade';
        } else {
             $headers = ["ID", "Equipamento", "Cidade", "Provedor", "Problema", "Status", "Data Início", "Data Fim"];
             $sqlBase = "SELECT op.id_ocorrencia_provedor as ID, e.nome_equip as Equipamento, c.nome as Cidade, p.nome_prov as Provedor, op.des_ocorrencia as Problema, op.status as Status, DATE_FORMAT(op.dt_inicio_reparo, '%d/%m/%Y %H:%i') as 'Data Início', DATE_FORMAT(op.dt_fim_reparo, '%d/%m/%Y %H:%i') as 'Data Fim' FROM ocorrencia_provedor op LEFT JOIN equipamentos e ON op.id_equipamento = e.id_equipamento JOIN cidades c ON op.id_cidade = c.id_cidade JOIN provedor p ON op.id_provedor = p.id_provedor";
             $dateColumn = 'op.dt_inicio_reparo'; $statusColumn = 'op.status'; $cityColumn = 'op.id_cidade';
        }
        break;
}

// Eu construo o resto da cláusula WHERE com os filtros do usuário
if ($cityId !== 'todos' && !empty($cityId)) {
    $sqlWhere .= " AND {$cityColumn} = ?";
    $types .= 'i';
    $params[] = $cityId;
}
if (!empty($startDate)) {
    $sqlWhere .= " AND DATE({$dateColumn}) >= ?";
    $types .= 's';
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $sqlWhere .= " AND DATE({$dateColumn}) <= ?";
    $types .= 's';
    $params[] = $endDate;
}
if ($status !== 'todos' && !empty($status)) {
    $sqlWhere .= " AND {$statusColumn} = ?";
    $types .= 's';
    $params[] = $status;
}

$sqlFinal = $sqlBase . $sqlWhere;

$stmt = $conn->prepare($sqlFinal);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
    exit;
}

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// --- [MUDANÇA AQUI] Eu vou agrupar os resultados por cidade antes de enviar ---
$groupedData = [];
foreach ($data as $row) {
    $cidade = $row['Cidade']; // Pego o nome da cidade de cada linha
    if (!isset($groupedData[$cidade])) {
        $groupedData[$cidade] = []; // Se for a primeira vez que vejo essa cidade, eu crio um grupo para ela
    }
    unset($row['Cidade']); // Eu removo a cidade de dentro do registro para não repetir a informação
    $groupedData[$cidade][] = $row; // Adiciono o registro ao grupo da cidade correta
}

$stmt->close();
$conn->close();

// Eu retorno os dados já agrupados e os cabeçalhos para o Excel.
echo json_encode(['success' => true, 'headers' => $headers, 'data' => $groupedData]);