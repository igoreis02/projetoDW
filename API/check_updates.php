<?php
// /API/check_updates.php
session_start();
header('Content-Type: application/json');
require_once 'conexao_bd.php';

$context = $_GET['context'] ?? '';

if (empty($context)) {
    echo json_encode(['success' => false, 'message' => 'Contexto não fornecido.']);
    exit();
}

$response = ['success' => false, 'checksum' => null];

try {
    $tables = [];
    // Mapeia o contexto para as tabelas do banco de dados correspondentes
    switch ($context) {
        case 'solicitacoes_clientes':
            $tables = ['solicitacao_cliente'];
            break;

        case 'ocorrencias_pendentes':
        case 'ocorrencias_em_andamento':
        case 'gestao_ocorrencias': // 
            // Todas estas páginas dependem de mudanças em manutenções ou ocorrências semafóricas
            $tables = ['manutencoes', 'ocorrencia_semaforica'];
            break;
        case 'gestao_global':
            // Este contexto verifica todas as tabelas que afetam o dashboard e as listas
            $tables = [
                'manutencoes',
                'ocorrencia_semaforica',
                'ocorrencia_provedor',
                'ocorrencia_processamento',
                'solicitacao_cliente',
                'equipamentos' // Para o KPI de aferições
            ];
            break;

        case 'ocorrencias_provedores':
            // Esta página verifica as tabelas de ocorrências de provedor e manutenções gerais
            $tables = ['ocorrencia_provedor', 'manutencoes'];
            break;

        case 'ocorrencias_processamento':
            // Esta página verifica as tabelas de ocorrências de processamento e manutenções
            $tables = ['ocorrencia_processamento', 'manutencoes'];
            break;

        case 'equipamentos':
            // A página de equipamentos precisa saber se um equipamento ou seu endereço foi alterado
            $tables = ['equipamentos', 'endereco'];
            break;

        case 'lacres_imetro':
            // A página de lacres depende apenas da tabela de controle de lacres
            $tables = ['controle_lacres'];
            break;
        case 'provedores':
            $tables = ['provedor'];
            break;

        // -----------------------------------------

        default:
            throw new Exception("Contexto de verificação inválido.");
    }

    $totalChecksum = 0;
    foreach ($tables as $table) {
        $result = $conn->query("CHECKSUM TABLE `$table`");
        if ($result) {
            $row = $result->fetch_assoc();
            // Soma os checksums de todas as tabelas relevantes para criar uma assinatura única
            $totalChecksum += (int) $row['Checksum'];
        } else {
            throw new Exception("Erro ao calcular checksum para a tabela: $table");
        }
    }

    $response['success'] = true;
    $response['checksum'] = $totalChecksum;

} catch (Exception $e) {
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    http_response_code(500); // Adiciona um código de erro HTTP
}

$conn->close();
echo json_encode($response);
?>