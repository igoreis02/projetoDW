<?php
header('Content-Type: application/json');


require_once 'conexao_bd.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados: ' . $conn->connect_error]);
    exit();
}

// Pega o termo de pesquisa da URL, se houver
$search_term = $_GET['search_term'] ?? '';
$equipamentos = [];

try {
     $tables = ['equipamentos', 'endereco'];
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

    // Consulta base selecionando todas as colunas necessárias das tabelas corretas
    $sql = "
    SELECT 
        e.id_equipamento, 
        e.nome_equip, 
        e.referencia_equip, 
        e.tipo_equip, 
        c.nome as cidade,
        e.id_cidade,
        ed.id_endereco,
        ed.logradouro,
        ed.bairro,
        ed.cep,
        ed.latitude,
        ed.longitude,
        e.status, 
        e.data_instalacao, 
        e.dt_estudoTec, 
        e.qtd_faixa, 
        e.id_provedor, 
        p.nome_prov, 
        e.num_instrumento, 
        e.dt_afericao, 
        e.dt_vencimento, 
        e.Km as km, 
        e.sentido
    FROM 
        equipamentos e
    LEFT JOIN 
        cidades c ON e.id_cidade = c.id_cidade
    LEFT JOIN 
        endereco ed ON e.id_endereco = ed.id_endereco
    LEFT JOIN 
        provedor p ON e.id_provedor = p.id_provedor
    ";

    $params = [];
    $types = '';

    // Adiciona a cláusula WHERE apenas se houver um termo de pesquisa
    if (!empty($search_term)) {
        $sql .= " WHERE (e.nome_equip LIKE ? OR e.referencia_equip LIKE ? OR c.nome LIKE ?)";
        $searchTermParam = "%" . $search_term . "%";
        $params[] = $searchTermParam;
        $params[] = $searchTermParam;
        $params[] = $searchTermParam;
        $types .= "sss";
    }

    // Adiciona a ordenação no final da consulta
    $sql .= " ORDER BY c.nome, e.nome_equip";

    $stmt = $conn->prepare($sql);

    // Vincula os parâmetros se eles existirem
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $equipamentos[] = $row;
        }
        echo json_encode([
            'success' => true, 
            'checksum' => $totalChecksum,
            'equipamentos' => $equipamentos
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'checksum' => $totalChecksum,
            'equipamentos' => [],
            'message' => 'Nenhum equipamento encontrado.'
        ]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar equipamentos: ' . $e->getMessage()]);
}

$conn->close();