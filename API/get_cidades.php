<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições de qualquer origem (para desenvolvimento)

require_once 'conexao_bd.php';


$sql = "SELECT c.id_cidade, c.nome, c.cod_cidade, c.sigla_cidade, c.somente_semaforo FROM cidades c";

if (isset($_GET['context']) && $_GET['context'] === 'manutencao') {
    // A cidade precisa atender a DUAS CONDIÇÕES ao mesmo tempo:
    // 1. O campo 'somente_semaforo' deve ser 0.
    // 2. A cidade deve ter pelo menos um equipamento cadastrado (EXISTS).
    $sql .= " WHERE c.somente_semaforo = 0 AND EXISTS (SELECT 1 FROM equipamentos e WHERE e.id_cidade = c.id_cidade)";
}

// Adiciona a ordenação
$sql .= " ORDER BY c.nome ASC";

$cidades = [];
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cidades[] = $row;
    }
    echo json_encode(['success' => true, 'cidades' => $cidades]);
} else {
    $message = (isset($_GET['context']) && $_GET['context'] === 'manutencao')
        ? 'Nenhuma cidade aplicável (com equipamentos e não exclusiva de semáforo) foi encontrada.'
        : 'Nenhuma cidade encontrada.';
    echo json_encode(['success' => false, 'message' => $message]);
}

$conn->close();
?>