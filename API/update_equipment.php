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

if (!isset($data['id_equipamento']) || !isset($data['id_endereco']) || !isset($data['tipo_equip']) ||
    !isset($data['nome_equip']) || !isset($data['status']) || !isset($data['id_cidade']) ||
    !isset($data['logradouro']) || !isset($data['bairro']) || !isset($data['id_provedor'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos para atualização.']);
    exit();
}

$conn->begin_transaction();

try {
    $stmt_endereco = $conn->prepare("UPDATE endereco SET logradouro = ?, bairro = ?, cep = ?, latitude = ?, longitude = ? WHERE id_endereco = ?");
    $cep = $data['cep'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $stmt_endereco->bind_param("sssddi", $data['logradouro'], $data['bairro'], $cep, $latitude, $longitude, $data['id_endereco']);
    $stmt_endereco->execute();
    $stmt_endereco->close();

    // LÓGICA ADICIONADA PARA CALCULAR A DATA DE VENCIMENTO
    $dt_afericao = $data['dt_afericao'] ?? null;
    $dt_vencimento = null;
    if (!empty($dt_afericao)) {
        try {
            $date = new DateTime($dt_afericao);
            $date->modify('+1 year');
            $date->modify('-1 day');
            $dt_vencimento = $date->format('Y-m-d');
        } catch (Exception $e) {
            $dt_vencimento = null;
        }
    }

    // QUERY E BIND_PARAM ATUALIZADOS
    $stmt_equipamento = $conn->prepare("UPDATE equipamentos SET tipo_equip = ?, nome_equip = ?, referencia_equip = ?, status = ?, qtd_faixa = ?, km = ?, sentido = ?, num_instrumento = ?, dt_afericao = ?, dt_vencimento = ?, id_cidade = ?, id_provedor = ? WHERE id_equipamento = ?");
    
    $referencia_equip = $data['referencia_equip'] ?? null;
    $data['qtd_faixa'] = $data['qtd_faixa'] ?? null;
    $km = $data['km'] ?? null;
    $sentido = $data['sentido'] ?? null;
    $num_instrumento = $data['num_instrumento'] ?? null;
    
    $stmt_equipamento->bind_param("ssssissssssii", 
        $data['tipo_equip'], 
        $data['nome_equip'], 
        $referencia_equip, 
        $data['status'], 
        $data['qtd_faixa'], 
        $km,
        $sentido,
        $num_instrumento,
        $dt_afericao,
        $dt_vencimento,
        $data['id_cidade'], 
        $data['id_provedor'], 
        $data['id_equipamento']
    );
    $stmt_equipamento->execute();
    $stmt_equipamento->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Equipamento atualizado com sucesso!']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Erro ao atualizar equipamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados. Tente novamente.']);
}

$conn->close();
?>