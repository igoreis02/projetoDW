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

if (!isset($data['id_equipamento']) || !isset($data['id_endereco'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos. ID do equipamento ou endereço ausente.']);
    exit();
}

$tipo_equip_str = is_array($data['tipo_equip']) ? implode(', ', $data['tipo_equip']) : $data['tipo_equip'];

$conn->begin_transaction();

try {
    // --- ATUALIZAÇÃO DO ENDEREÇO ---
    $coordenadas = explode(",", $data['coordenadas'] ?? '');
    $latitude = isset($coordenadas[0]) && is_numeric(trim($coordenadas[0])) ? (float)trim($coordenadas[0]) : null;
    $longitude = isset($coordenadas[1]) && is_numeric(trim($coordenadas[1])) ? (float)trim($coordenadas[1]) : null;

    $stmt_endereco = $conn->prepare("UPDATE endereco SET logradouro = ?, bairro = ?, cep = ?, latitude = ?, longitude = ? WHERE id_endereco = ?");
    $cep = !empty($data['cep']) ? $data['cep'] : null;
    $stmt_endereco->bind_param("sssddi", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude, $data['id_endereco']);
    $stmt_endereco->execute();
    $stmt_endereco->close();

    // --- CÁLCULO DA DATA DE VENCIMENTO ---
    $dt_afericao = !empty($data['dt_afericao']) ? $data['dt_afericao'] : null;
    $dt_vencimento = null;
    if ($dt_afericao) {
        $date = new DateTime($dt_afericao);
        $date->modify('+1 year')->modify('-1 day');
        $dt_vencimento = $date->format('Y-m-d');
    }

    // --- ATUALIZAÇÃO DO EQUIPAMENTO ---
    $stmt_equipamento = $conn->prepare(
        "UPDATE equipamentos SET 
            tipo_equip = ?, nome_equip = ?, referencia_equip = ?, status = ?, 
            qtd_faixa = ?, Km = ?, sentido = ?, num_instrumento = ?, 
            dt_afericao = ?, dt_vencimento = ?, id_cidade = ?, id_provedor = ?, 
            data_instalacao = ?, dt_estudoTec = ?,
            dt_fabricacao = ?, dt_sinalizacao_adicional = ?, dt_inicio_processamento = ?,
            id_tecnico_instalacao = ?, num_certificado = ?, dt_remanejado = ?, dt_desativado = ?
        WHERE id_equipamento = ?"
    );
    
    // --- TRATAMENTO DOS DADOS ---
    $referencia_equip = !empty($data['referencia_equip']) ? $data['referencia_equip'] : null;
    $qtd_faixa = !empty($data['qtd_faixa']) ? (int)$data['qtd_faixa'] : null;
    $km = !empty($data['km']) ? $data['km'] : null;
    $sentido = !empty($data['sentido']) ? $data['sentido'] : null;
    $num_instrumento = !empty($data['num_instrumento']) ? $data['num_instrumento'] : null;
    $dt_instalacao = !empty($data['dt_instalacao']) ? $data['dt_instalacao'] : null;
    $dt_estudoTec = !empty($data['dt_estudoTec']) ? $data['dt_estudoTec'] : null;
    $id_provedor = !empty($data['id_provedor']) ? (int)$data['id_provedor'] : null;

    $dt_fabricacao = !empty($data['dt_fabricacao']) ? $data['dt_fabricacao'] : null;
    $dt_sinalizacao_adicional = !empty($data['dt_sinalizacao_adicional']) ? $data['dt_sinalizacao_adicional'] : null;
    $dt_inicio_processamento = !empty($data['dt_inicio_processamento']) ? $data['dt_inicio_processamento'] : null;
    $num_certificado = !empty($data['num_certificado']) ? $data['num_certificado'] : null;

    // Converte array de técnicos para string
    $id_tecnico_instalacao_array = $data['id_tecnico_instalacao'] ?? [];
    $id_tecnico_instalacao_str = !empty($id_tecnico_instalacao_array) ? implode(',', $id_tecnico_instalacao_array) : null;

    // Lógica condicional para datas de status
    $dt_remanejado = ($data['status'] === 'remanejado' && !empty($data['dt_remanejado'])) ? $data['dt_remanejado'] : null;
    $dt_desativado = ($data['status'] === 'inativo' && !empty($data['dt_desativado'])) ? $data['dt_desativado'] : null;

    // BIND DOS PARÂMETROS ATUALIZADO
    $stmt_equipamento->bind_param("ssssisssssiisssssssssi", 
        $tipo_equip_str, $data['nome_equip'], $referencia_equip, $data['status'], 
        $qtd_faixa, $km, $sentido, $num_instrumento, $dt_afericao, $dt_vencimento,
        $data['id_cidade'], $id_provedor, 
        $dt_instalacao, $dt_estudoTec,
        $dt_fabricacao, $dt_sinalizacao_adicional, $dt_inicio_processamento,
        $id_tecnico_instalacao_str, $num_certificado, $dt_remanejado, $dt_desativado,
        $data['id_equipamento']
    );
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento atualizado com sucesso!']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao atualizar equipamento: " . $e->getMessage());
    if ($e->getCode() == 1062) {
        echo json_encode(['success' => false, 'message' => 'NOME ou REFERÊNCIA já existe para esta cidade.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados ao atualizar.']);
    }
}

$conn->close();
?>