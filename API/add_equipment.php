<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

require_once 'conexao_bd.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação (mantida)
if (empty($data['tipo_equip']) || empty($data['nome_equip']) || empty($data['status']) || empty($data['id_cidade']) || empty($data['logradouro']) || empty($data['bairro']) || empty($data['id_provedor'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

// **NOVO**: Converte o array de tipos em string
$tipo_equip_str = is_array($data['tipo_equip']) ? implode(', ', $data['tipo_equip']) : $data['tipo_equip'];

// Validação condicional (mantida)
if (in_array('RADAR FIXO', $data['tipo_equip']) || in_array('LOMBADA ELETRONICA', $data['tipo_equip'])) {
    if (empty($data['num_instrumento']) || empty($data['dt_afericao'])) {
        echo json_encode(['success' => false, 'message' => 'Nº do Instrumento e Data de Aferição são obrigatórios para este tipo de equipamento.']);
        exit();
    }
}

$conn->begin_transaction();

try {
    $coordenadas = explode(",", $data['coordenadas'] ?? '');
    $latitude = isset($coordenadas[0]) ? trim($coordenadas[0]) : null;
    $longitude = isset($coordenadas[1]) ? trim($coordenadas[1]) : null;

    $stmt_endereco = $conn->prepare("INSERT INTO endereco (logradouro, bairro, cep, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
    $cep = $data['cep'] ?? null;
    $stmt_endereco->bind_param("sssdd", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude);
    $stmt_endereco->execute();
    $id_endereco = $conn->insert_id;
    $stmt_endereco->close();

    $dt_afericao = !empty($data['dt_afericao']) ? $data['dt_afericao'] : null;
    $dt_vencimento = null;
    if ($dt_afericao) {
        $date = new DateTime($dt_afericao);
        $date->modify('+1 year')->modify('-1 day');
        $dt_vencimento = $date->format('Y-m-d');
    }
    
    $stmt_equipamento = $conn->prepare("INSERT INTO equipamentos (tipo_equip, nome_equip, referencia_equip, status, qtd_faixa, km, sentido, num_instrumento, dt_afericao, dt_vencimento, id_cidade, id_endereco, id_provedor, dt_instalacao, dt_estudoTec) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $referencia_equip = $data['referencia_equip'] ?? null;
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $km = $data['km'] ?? null;
    $sentido = $data['sentido'] ?? null;
    $num_instrumento = !empty($data['num_instrumento']) ? $data['num_instrumento'] : null;
    $id_provedor = (int)$data['id_provedor'];
    $id_cidade = (int)$data['id_cidade'];
    $dt_instalacao = !empty($data['dt_instalacao']) ? $data['dt_instalacao'] : null;
    $dt_estudoTec = !empty($data['dt_estudoTec']) ? $data['dt_estudoTec'] : null;

    // **MODIFICADO**: Passa a string de tipos para o banco
    $stmt_equipamento->bind_param("ssssisssssiiiss", 
        $tipo_equip_str, $data['nome_equip'], $referencia_equip, $data['status'], 
        $qtd_faixa, $km, $sentido, $num_instrumento, $dt_afericao, $dt_vencimento, 
        $id_cidade, $id_endereco, $id_provedor,
        $dt_instalacao, $dt_estudoTec
    );
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento adicionado com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    // Lógica de erro original mantida
    if ($e->getCode() == 1062) {
        echo json_encode(['success' => false, 'message' => 'EQUIPAMENTO JÁ CADASTRADO.']);
    } else {
        error_log("Erro ao adicionar equipamento: " . $e->getMessage()); 
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados.']);
    }
}
$conn->close();
?>