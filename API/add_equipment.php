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

// --- Validação Essencial ---
$required_fields = ['tipo_equip', 'nome_equip', 'status', 'id_cidade', 'logradouro', 'bairro'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios.']);
        exit();
    }
}

$tipos_selecionados = is_array($data['tipo_equip']) ? $data['tipo_equip'] : [];
$tipo_equip_str = implode(', ', $tipos_selecionados);

// --- Validação Condicional Baseada no Tipo ---
if (in_array('RADAR FIXO', $tipos_selecionados) || in_array('LOMBADA ELETRONICA', $tipos_selecionados)) {
    if (empty($data['num_instrumento']) || empty($data['dt_afericao'])) {
        echo json_encode(['success' => false, 'message' => 'Nº do Instrumento e Data de Aferição são obrigatórios para RADAR FIXO ou LOMBADA.']);
        exit();
    }
}

$conn->begin_transaction();

try {
    // --- Tratamento de Endereço e Coordenadas ---
    $coordenadas = explode(",", $data['coordenadas'] ?? '');
    $latitude = isset($coordenadas[0]) && is_numeric(trim($coordenadas[0])) ? (float)trim($coordenadas[0]) : null;
    $longitude = isset($coordenadas[1]) && is_numeric(trim($coordenadas[1])) ? (float)trim($coordenadas[1]) : null;

    $stmt_endereco = $conn->prepare("INSERT INTO endereco (logradouro, bairro, cep, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
    $cep = !empty($data['cep']) ? $data['cep'] : null;
    $stmt_endereco->bind_param("sssdd", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude);
    $stmt_endereco->execute();
    $id_endereco = $conn->insert_id;
    $stmt_endereco->close();

    // --- Tratamento de Datas e Campos Opcionais ---
    $dt_afericao = !empty($data['dt_afericao']) ? $data['dt_afericao'] : null;
    $dt_vencimento = null;
    if ($dt_afericao) {
        $date = new DateTime($dt_afericao);
        $date->modify('+1 year')->modify('-1 day');
        $dt_vencimento = $date->format('Y-m-d');
    }
    
    // --- Preparação para Inserção do Equipamento 
    $stmt_equipamento = $conn->prepare(
        "INSERT INTO equipamentos (
            tipo_equip, nome_equip, referencia_equip, status, qtd_faixa, km, sentido, 
            num_instrumento, dt_afericao, dt_vencimento, id_cidade, id_endereco, id_provedor, 
            data_instalacao, dt_estudoTec, 
            dt_fabricacao, dt_sinalizacao_adicional, dt_inicio_processamento, id_tecnico_instalacao, num_certificado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    // Garante que valores opcionais sejam NULL se estiverem vazios (Seu código original mantido e expandido)
    $referencia_equip = !empty($data['referencia_equip']) ? $data['referencia_equip'] : null;
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $km = !empty($data['km']) ? $data['km'] : null;
    $sentido = !empty($data['sentido']) ? $data['sentido'] : null;
    $num_instrumento = !empty($data['num_instrumento']) ? $data['num_instrumento'] : null;
    $id_provedor = !empty($data['id_provedor']) ? (int)$data['id_provedor'] : null;
    $id_cidade = (int)$data['id_cidade'];
    $data_instalacao = !empty($data['data_instalacao']) ? $data['data_instalacao'] : null;
    $dt_estudoTec = !empty($data['dt_estudoTec']) ? $data['dt_estudoTec'] : null;

  
    $dt_fabricacao = !empty($data['dt_fabricacao']) ? $data['dt_fabricacao'] : null;
    $dt_sinalizacao_adicional = !empty($data['dt_sinalizacao_adicional']) ? $data['dt_sinalizacao_adicional'] : null;
    $dt_inicio_processamento = !empty($data['dt_inicio_processamento']) ? $data['dt_inicio_processamento'] : null;
    $num_certificado = !empty($data['num_certificado']) ? $data['num_certificado'] : null;
    
    // Converte o array de técnicos em uma string separada por vírgulas
    $id_tecnico_instalacao_array = $data['id_tecnico_instalacao'] ?? [];
    $id_tecnico_instalacao_str = !empty($id_tecnico_instalacao_array) ? implode(',', $id_tecnico_instalacao_array) : null;



    $stmt_equipamento->bind_param("ssssisssssiiisssssss", 
        $tipo_equip_str, $data['nome_equip'], $referencia_equip, $data['status'], 
        $qtd_faixa, $km, $sentido, $num_instrumento, $dt_afericao, $dt_vencimento, 
        $id_cidade, $id_endereco, $id_provedor,
        $data_instalacao, $dt_estudoTec,
        $dt_fabricacao, $dt_sinalizacao_adicional, $dt_inicio_processamento,
        $id_tecnico_instalacao_str, $num_certificado
    );
    
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento adicionado com sucesso!']);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    if ($e->getCode() == 1062) {
        echo json_encode(['success' => false, 'message' => 'EQUIPAMENTO JÁ CADASTRADO (Nome ou Referência já existe para esta cidade).']);
    } else {
        error_log("Erro ao adicionar equipamento: " . $e->getMessage()); 
        echo json_encode(['success' => false, 'message' => 'Ocorreu um erro inesperado no banco de dados.']);
    }
}
$conn->close();
?>