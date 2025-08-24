<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Inclui a conexão com o banco de dados (ajuste o caminho se necessário)
require_once 'conexao_bd.php';

// Verifica a conexão
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

$search_term = $_GET['search_term'] ?? '';
$equipamentos = [];

try {
    // Consulta SQL completa com JOINs para obter todas as informações necessárias
    // CORREÇÃO: Adicionada a junção com a tabela provedor e a seleção do nome_prov e id_provedor
    $sql = "SELECT
                e.id_equipamento,
                e.tipo_equip,
                e.nome_equip,
                e.referencia_equip,
                e.status,
                e.qtd_faixa,
                c.nome AS cidade,
                e.id_cidade,
                p.nome_prov,
                e.id_provedor,
                e.id_endereco,
                en.logradouro,
                en.bairro,
                en.cep,
                en.latitude,
                en.longitude
            FROM equipamentos AS e
            JOIN cidades AS c ON e.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            LEFT JOIN provedor AS p ON e.id_provedor = p.id_provedor
            WHERE 1=1";

    // Prepara a consulta para incluir a pesquisa
    $params = [];
    $types = '';

    if (!empty($search_term)) {
        $sql .= " AND (e.nome_equip LIKE ? OR e.referencia_equip LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $types .= "ss";
    }

    $sql .= " ORDER BY c.nome, e.tipo_equip, e.nome_equip";

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $equipamentos[] = $row;
        }
        echo json_encode(['success' => true, 'equipamentos' => $equipamentos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum equipamento encontrado.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar equipamentos: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>