<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];
$solicitacoes_por_cidade = [];
$cidades_com_solicitacoes = [];
$response_data = [];

$search_term = $_GET['search'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? null;
$data_fim = $_GET['data_fim'] ?? null;
$status_filtro = $_GET['status'] ?? 'todos';

try {
    // Primeiro, pega a contagem total de todas as solicitações
    $count_result = $conn->query("SELECT COUNT(*) as total_count FROM solicitacao_cliente");
    $total_count = $count_result->fetch_assoc()['total_count'];
    $response['total_count'] = $total_count;

    // Continua com a lógica de busca filtrada
    $sql = "SELECT
                s.id_solicitacao, s.solicitante, s.tipo_solicitacao, s.desc_solicitacao, s.desdobramento_soli,
                DATE_FORMAT(s.data_solicitacao, '%d/%m/%Y') AS data_solicitacao, 
                DATE_FORMAT(s.data_conclusao, '%d/%m/%Y') AS data_conclusao, 
                s.status_solicitacao,
                u.id_usuario, u.nome AS nome_usuario,
                c.id_cidade, c.nome AS nome_cidade
            FROM solicitacao_cliente AS s
            LEFT JOIN usuario AS u ON s.id_usuario = u.id_usuario
            LEFT JOIN cidades AS c ON s.id_cidade = c.id_cidade
            WHERE (s.solicitante LIKE ? OR c.nome LIKE ? OR u.nome LIKE ? OR s.desc_solicitacao LIKE ?)";

    $params = [];
    $types = "ssss";
    $param = "%" . $search_term . "%";
    array_push($params, $param, $param, $param, $param);

    if ($data_inicio) {
        $sql .= " AND DATE(s.data_solicitacao) >= ?";
        $types .= "s";
        $params[] = $data_inicio;
    }
    if ($data_fim) {
        $sql .= " AND DATE(s.data_solicitacao) <= ?";
        $types .= "s";
        $params[] = $data_fim;
    }
    if ($status_filtro && $status_filtro !== 'todos') {
        $sql .= " AND s.status_solicitacao = ?";
        $types .= "s";
        $params[] = $status_filtro;
    }

    $sql .= " ORDER BY c.nome, s.data_solicitacao DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cidade = $row['nome_cidade'];
            if (!isset($solicitacoes_por_cidade[$cidade])) {
                $solicitacoes_por_cidade[$cidade] = [];
            }
            $solicitacoes_por_cidade[$cidade][] = $row;
            
            if (!in_array($cidade, $cidades_com_solicitacoes)) {
                $cidades_com_solicitacoes[] = $cidade;
            }
        }
        
        sort($cidades_com_solicitacoes);
        
        $response_data['solicitacoes'] = $solicitacoes_por_cidade;
        $response_data['cidades'] = $cidades_com_solicitacoes;

        $response['success'] = true;
        $response['data'] = $response_data;

    } else {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }
    $stmt->close();

} catch (Exception $e) {
    $response['message'] = 'Erro ao buscar solicitações: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>