<?php
header('Content-Type: application/json');
require_once 'conexao_bd.php'; // Eu incluo minha conexão com o banco

// Eu pego todos os filtros que enviei pela URL
$cityId = $_GET['city'] ?? 'todos';
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$reportType = $_GET['reportType'] ?? 'matriz_tecnica';
// [MUDANÇA AQUI] Eu pego o status, que agora pode ser um array
$status = $_GET['status'] ?? ['todos'];
// Garanto que seja um array para tratar da mesma forma
if (!is_array($status)) {
    $status = [$status];
}

$params = [];
$types = '';
$sqlBase = ""; 
$headers = []; 

$sqlWhere = " WHERE 1=1";

// Eu escolho qual consulta fazer com base no tipo de relatório
switch ($reportType) {
    case 'matriz_tecnica':
    case 'controle_ocorrencia':
        // [MUDANÇA AQUI] Eu adiciono "Status" aos cabeçalhos
        $headers = ["Cidade", "Equipamento", "Data Início", "Descrição Problema", "Data Fim", "Descrição Reparo", "Atendido em dia(s)", "Status", "Técnico"];
        
        // [MUDANÇA AQUI] Eu adiciono m.status_reparo à consulta
        $sqlBase = "SELECT
                        c.nome as Cidade,
                        CONCAT(SUBSTRING_INDEX(e.nome_equip, ' - ', 1), ' - ', e.referencia_equip) as Equipamento,
                        DATE_FORMAT(m.inicio_reparo, '%d/%m/%Y %H:%i') as 'Data Início',
                        m.ocorrencia_reparo as 'Descrição Problema',
                        DATE_FORMAT(m.fim_reparo, '%d/%m/%Y %H:%i') as 'Data Fim',
                        m.reparo_finalizado as 'Descrição Reparo',
                        DATEDIFF(m.fim_reparo, m.inicio_reparo) as 'Atendido em dia(s)',
                        m.status_reparo as Status,
                        (SELECT GROUP_CONCAT(u.nome SEPARATOR ', ') FROM manutencoes_tecnicos mt JOIN usuario u ON mt.id_tecnico = u.id_usuario WHERE mt.id_manutencao = m.id_manutencao) as Técnico
                    FROM manutencoes m
                    JOIN equipamentos e ON m.id_equipamento = e.id_equipamento
                    JOIN cidades c ON e.id_cidade = c.id_cidade";
        
        // Eu aplico o filtro de tipo de manutenção que você pediu
        if ($reportType === 'controle_ocorrencia') {
            $sqlWhere .= " AND m.tipo_manutencao = 'preditiva'";
        } else { // Matriz Técnica (Sempre corretiva, como solicitado)
            $sqlWhere .= " AND m.tipo_manutencao = 'corretiva'";
        }
        
        $dateColumn = 'm.inicio_reparo';
        $statusColumn = 'm.status_reparo';
        $cityColumn = 'e.id_cidade'; // Corrigido para e.id_cidade para consistência
        break;

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

// Eu só aplico o filtro se a opção "todos" não estiver selecionada
if (!in_array('todos', $status) && !empty($status)) {
    // Eu crio os placeholders (?, ?, ?) dinamicamente
    $placeholders = implode(',', array_fill(0, count($status), '?'));
    $sqlWhere .= " AND {$statusColumn} IN ({$placeholders})";
    // Eu adiciono os tipos ('s' para cada status)
    $types .= str_repeat('s', count($status));
    // Eu junto os valores de status ao array de parâmetros
    $params = array_merge($params, $status);
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

// --- Agrupamento dos resultados por cidade ---
$groupedData = [];
// Só agrupo se for um dos relatórios que usam esse formato
if ($reportType === 'matriz_tecnica' || $reportType === 'controle_ocorrencia') {
    foreach ($data as $row) {
        $cidade = $row['Cidade'];
        if (!isset($groupedData[$cidade])) {
            $groupedData[$cidade] = [];
        }
        unset($row['Cidade']);
        $groupedData[$cidade][] = $row;
    }
} else { // Para os outros, eu mantenho o formato antigo mas dentro de uma chave "geral" para manter a estrutura
    $groupedData['geral'] = $data;
}


$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'headers' => $headers, 'data' => $groupedData]);