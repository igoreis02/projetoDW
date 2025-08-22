<?php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$response = ['success' => false, 'message' => ''];
$solicitacoes_por_cidade = [];
$cidades_com_solicitacoes = [];
$response_data = [];

// Pega o termo de pesquisa da URL
$search_term = $_GET['search'] ?? '';

try {
    $sql = "SELECT
                s.id_solicitacao, s.solicitante, s.desc_solicitacao, s.desdobramento_soli,
                s.data_solicitacao, s.data_conclusao, s.status_solicitacao,
                u.id_usuario, u.nome AS nome_usuario,
                c.id_cidade, c.nome AS nome_cidade
            FROM solicitacao_cliente AS s
            LEFT JOIN usuario AS u ON s.id_usuario = u.id_usuario
            LEFT JOIN cidades AS c ON s.id_cidade = c.id_cidade
            WHERE (s.solicitante LIKE ? OR c.nome LIKE ? OR u.nome LIKE ? OR s.desc_solicitacao LIKE ?)
            ORDER BY c.nome, s.data_solicitacao DESC";

    $stmt = $conn->prepare($sql);
    $param = "%" . $search_term . "%";
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    
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