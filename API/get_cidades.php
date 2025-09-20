<?php
header('Content-Type: application/json');


require_once 'conexao_bd.php';

// Seleciona as novas colunas
$sql = "SELECT c.id_cidade, c.nome, c.cod_cidade, c.sigla_cidade, c.semaforica, c.radares FROM cidades c";

// Lógica de filtragem ATUALIZADA
if (isset($_GET['context'])) {
    $context = $_GET['context'];
    
    if ($context === 'manutencao') {
        // Para Matriz Técnica, Controle de Ocorrência e Instalação, mostra cidades com RADARES = 1
     $sql .= " WHERE c.radares = 1 AND EXISTS (SELECT 1 FROM equipamentos e WHERE e.id_cidade = c.id_cidade)";

    } elseif ($context === 'semaforica') {
        // Para Matriz Semafórica, mostra cidades com SEMAFORICA = 1
        $sql .= " WHERE c.semaforica = 1";
    }
}

$sql .= " ORDER BY c.nome ASC";

$cidades = [];
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $cidades[] = $row;
    }
    echo json_encode(['success' => true, 'cidades' => $cidades]);
} else {
    $message = 'Nenhuma cidade encontrada.';
    if (isset($_GET['context'])) {
        if ($_GET['context'] === 'manutencao') {
            $message = 'Nenhuma cidade com manutenção para radar encontrada.';
        } elseif ($_GET['context'] === 'semaforica') {
            $message = 'Nenhuma cidade com manutenção para semáforo encontrada.';
        }
    }
    echo json_encode(['success' => false, 'message' => $message]);
}

$conn->close();
?>