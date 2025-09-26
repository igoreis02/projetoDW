<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

require_once 'API/conexao_bd.php';

$cities_com_radares = []; // Cidades para o padrão (radares=1)
$cities_com_semaforos = []; // Cidades para semáforos (semaforica=1)
$all_cities_with_equipment = []; // Todas as cidades com equipamentos para os botões

try {
    // Busca cidades COM RADARES e que POSSUEM equipamentos cadastrados
    $sql_radares = "SELECT DISTINCT c.id_cidade, c.nome 
                    FROM cidades c 
                    WHERE c.radares = 1 AND EXISTS (SELECT 1 FROM equipamentos e WHERE e.id_cidade = c.id_cidade)
                    ORDER BY c.nome ASC";
    $result_radares = $conn->query($sql_radares);
    if ($result_radares) {
        while ($row = $result_radares->fetch_assoc()) {
            $cities_com_radares[] = $row;
        }
    }

    // Busca cidades COM SEMÁFOROS e que POSSUEM equipamentos cadastrados
    $sql_semaforos = "SELECT DISTINCT c.id_cidade, c.nome 
                      FROM cidades c 
                      WHERE c.semaforica = 1 AND EXISTS (SELECT 1 FROM equipamentos e WHERE e.id_cidade = c.id_cidade)
                      ORDER BY c.nome ASC";
    $result_semaforos = $conn->query($sql_semaforos);
    if ($result_semaforos) {
        while ($row = $result_semaforos->fetch_assoc()) {
            $cities_com_semaforos[] = $row;
        }
    }

    // Busca TODAS as cidades que possuem qualquer tipo de equipamento para os botões de filtro
    $sql_all_cities = "SELECT DISTINCT c.nome 
                       FROM cidades c 
                       JOIN equipamentos e ON c.id_cidade = e.id_cidade 
                       ORDER BY c.nome ASC";
    $result_all_cities = $conn->query($sql_all_cities);
    if ($result_all_cities) {
        while ($row = $result_all_cities->fetch_assoc()) {
            $all_cities_with_equipment[] = $row['nome'];
        }
    }


} catch (Exception $e) {
    error_log("Erro em equipamentos.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Gerenciar Equipamentos</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 0;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1400px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .card h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }


        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 2rem;
        }

        .header-container h2 {
            font-size: 2.2em;
            color: black;
            margin: 0;
        }

        .conteudo-cabecalho {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .titulo-cabecalho {
            flex-grow: 1;
            text-align: center;
            margin: 0;
        }

        .botao-voltar {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 50%;
            color: white;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 2rem;
            left: 5%;
        }

        .botao-voltar:hover {
            background-color: var(--cor-secundaria);
        }

        .top-controls-wrapper {
            display: grid;
            /* Cria 3 colunas: Esquerda (1fr), Centro (auto), Direita (1fr) */
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 1rem;
            /* Espaçamento entre os elementos */
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .container-botao-adicionar-equipamento {
            grid-column: 1;
            /* Posiciona na primeira coluna */
            justify-self: start;
            /* Alinha o conteúdo à esquerda */
            margin-bottom: 0;
        }

        .botao-adicionar-equipamento {
            background-color: #112058;
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-adicionar-equipamento:hover {
            background-color: #1a3684ff;
        }

        .container-pesquisa {
            grid-column: 2;
            /* Posiciona na coluna central */
            justify-self: center;
            /* Centraliza o container na célula do grid */
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            width: auto;


        }

        .container-pesquisa input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 550px;
        }

        /* NOVO: Estilo para o botão Limpar Filtros */
        .botao-limpar-filtros {
            padding: 10px 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            color: #374151;
            background-color: #f8f9fa;
            cursor: pointer;
            width: 150px;
            font-size: 1em;
            transition: all 0.2s ease;
        }

        .botao-limpar-filtros:hover {
            background-color: #e2e6ea;
        }

        .voltar-btn {
            display: inline-block;
            width: auto;
            min-width: 200px;
            padding: 15px 30px;
            margin-top: 3rem;
            text-align: center;
            background-color: #112058;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .voltar-btn:hover {
            background-color: #192e73ff;
        }

        .close-btn,
        .back-btn-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }

        .close-btn {
            right: 0;
        }

        .back-btn-icon {
            left: 0;
        }

        .close-btn:hover,
        .back-btn-icon:hover {
            color: #333;
        }

        .city-buttons-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            
        }

        .city-button {
            background-color: #e2e8f0;
            color: #112058;
            padding: 8px 16px;
            border: 1px solid #cbd5e0;
            border-radius: 9999px;
            /* rounded-full */
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-size: 0.9em;
        }

        .city-button:hover {
            background-color: #cbd5e0;
        }

        .city-button.active {
            background-color: #112058;
            color: white;
            border-color: var(--cor-principal);
        }

        .main-loading-state {
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
            color: #555;
            padding: 40px 0;
        }

        .main-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--cor-principal);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        .equipment-list-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            width: 100%;
        }

        .city-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #112058;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--cor-principal);
        }

        .city-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: left;
        }

        .item-equipamento-titulo {
            margin: 0;
            color: #112058;
        }

        .equipment-grid {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(3, 1fr);
        }

        .item-equipamento {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
            display: flex;
            /* Adicionado para layout flexível */
            flex-direction: column;
            /* Organiza o conteúdo em coluna */
        }

        .item-equipamento-conteudo {
            flex-grow: 1;
            /* Faz o conteúdo crescer e empurrar o botão para baixo */
        }

        .item-equipamento h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: #112058;
        }

        .item-equipamento p {
            margin: 0.25rem 0;
            color: #555;
        }
        .item-botoes-container {
            display: flex;
            justify-content: flex-end; /* Alinha os botões à direita */
            gap: 10px; /* Espaço entre os botões */
            margin-top: 1rem; /* Espaço acima dos botões */
        }


        .item-equipamento .botao-editar,
        .item-equipamento .botao-localizacao {
           display: inline-flex;       /* Garante que se comportem como blocos flexíveis */
            align-items: center;       /* Centraliza o texto verticalmente, garantindo a mesma altura */
            justify-content: center;   /* Centraliza o texto horizontalmente */
            padding: 0.5rem 1rem;      /* Espaçamento interno */
            border-radius: 0.5rem;     /* Bordas arredondadas */
            border: none;              /* Remove borda padrão */
            color: white;              /* Cor do texto */
            font-family: inherit;      /* Garante que ambos usem a mesma fonte da página */
            font-size: 0.9em;          /* Define um tamanho de fonte explícito e igual para os dois */
            text-decoration: none;     /* Remove o sublinhado do link "Localização" */
            cursor: pointer;           /* Mostra o cursor de "mãozinha" */
            transition: background-color 0.3s ease;
            box-sizing: border-box;    /* Assegura que o padding não altere a altura final */
        }
        .item-equipamento .botao-editar {
            background-color: #007bff; /* Azul */
        }

        .item-equipamento .botao-editar:hover {
            background-color: #0056b3;
        }
         .item-equipamento .botao-localizacao {
            background-color: #28a745; /* Verde */
        }
        .item-equipamento .botao-localizacao:hover {
            background-color: #218838;
        }

        .status-cell {
            font-weight: normal;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .status-ativo {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-inativo {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-remanejado {
            background-color: #fffbeb;
            color: #f97316;
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .message.error {
            background-color: #fee2e2;
            color: #ef4444;
            border: 1px solid #fca5a5;
        }

        .message.success {
            background-color: #dcfce7;
            color: #22c55e;
            border: 1px solid #86efac;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: flex-start;
        }

        .modal.is-active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            position: relative;
            text-align: left;
            margin-top: 5%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            margin: 0;
            color: #333333;
            flex-grow: 1;
            text-align: center;
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content input[type="date"],
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .modal-content .form-buttons {
            display: flex;
            justify-content: space-around;
            gap: 15px;
            margin-top: 1.5rem;
        }

        .modal-content .form-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content .form-buttons .save-button {
            background-color: #28a745;
            color: white;
        }

        .modal-content .form-buttons .save-button:hover {
            background-color: #218838;
        }

        .modal-content .form-buttons .cancel-button {
            background-color: #dc3545;
            color: white;
        }

        .modal-content .form-buttons .cancel-button:hover {
            background-color: #c82333;
        }

        .close-button {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
        }

        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
            display: none;
            vertical-align: middle;
            margin-left: 8px;
        }

        .loading-spinner.is-active {
            display: inline-block;
        }

        .custom-date-input {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            cursor: pointer;
            /* Adicionado para indicar que é clicável */
        }

        .custom-date-input::after {
            font-family: "Font Awesome 5 Free";
            content: '\f073';
            /* Ícone de calendário */
            font-weight: 900;
            position: absolute;
            right: 15px;
            color: #888;
            pointer-events: none;
            /* Permite clicar no campo de data através do ícone */
        }

        .custom-date-input input[type="date"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 35px;
            /* Espaço para o ícone */
        }

        /* Esconde o ícone de calendário padrão do Chrome/Edge */
        .custom-date-input input[type="date"]::-webkit-calendar-picker-indicator {
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .hidden {
            display: none !important;
        }

        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        .equipment-type-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .equipment-type-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }

        /* NOVO: Estilo para o botão Voltar ao Topo */
        #backToTopBtn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #213fadff;
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s, opacity 0.5s;
        }

        #backToTopBtn:hover {
            background-color: #12287eff;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .ativos-container {
            width: 100%;
            margin-bottom: 1rem;
            margin-top: -0.5rem;
            /* Puxa um pouco para cima */
        }

        .botao-adicionar-ativos {
            background-color: #007bff;
            /* Azul para diferenciar */
            color: white;
            padding: 10px 20px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-adicionar-ativos:hover {
            background-color: #0056b3;
        }

        /* NOVOS ESTILOS PARA SELEÇÃO DE TÉCNICOS */
        .tecnicos-selecionados-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: -1rem; /* Puxa para perto do select */
            margin-bottom: 1rem;
            min-height: 40px; /* Altura mínima para não "pular" */
        }
        .tecnico-pill {
            display: flex;
            align-items: center;
            background-color: #e2e8f0;
            color: #112058;
            border-radius: 16px;
            padding: 4px 8px;
            font-size: 0.9em;
        }
        .remove-tecnico-btn {
            margin-left: 8px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: bold;
            color: #94a3b8;
            font-size: 1.1em;
        }
        .remove-tecnico-btn:hover {
            color: #ef4444;
        }
        /* FIM DOS NOVOS ESTILOS */

        @media (max-width: 1200px) {
            .top-controls-wrapper {
                grid-template-columns: 1fr;
                /* Muda para coluna única */
                justify-items: center;
                /* Centraliza os itens na coluna */
                gap: 1.5rem;
                /* Aumenta o espaço entre as linhas */
            }

            .container-botao-adicionar-equipamento {
                grid-column: 1;
                justify-self: left;
                /* Centraliza o botão */
            }

            .container-pesquisa {
                grid-column: 1;
                /* Coloca a pesquisa na mesma (e única) coluna */
                width: 100%;
                max-width: 700px;
                /* Define uma largura máxima para a pesquisa */
            }
        }

        @media (max-width: 768px) {

            .container-pesquisa {
                flex-direction: column;
                /* Empilha o input e o botão */
            }

            .container-pesquisa input,
            .container-pesquisa .botao-limpar-filtros {
                width: 100%;
                /* Faz ambos ocuparem a largura total */
                box-sizing: border-box;
                /* Garante que padding não afete a largura */
            }

            .equipment-grid {
                grid-template-columns: 1fr;
                /* Uma coluna para os cards */
            }

            .card {
                padding: 1.5rem;
                width: 95%;
            }

            .titulo-cabecalho {
                font-size: 1.5rem;
            }

            .botao-voltar {
                position: static;
                margin-right: 1rem;
            }

            .container-botao-adicionar-equipamento {
                justify-content: center;
            }

            .modal-content {
                padding: 1.5rem;
            }

            .modal-content .form-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <script>
        // Passa as cidades do PHP para o JavaScript para uso dinâmico
        const CIDADES_SEMAFORO = <?php echo json_encode($cities_com_semaforos); ?>;
        const CIDADES_PADRAO = <?php echo json_encode($cities_com_radares); ?>;
    </script>
    <div class="background"></div>
    <main class="card">
        <header class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">←</a>
            <h1 class="titulo-cabecalho">Equipamentos</h1>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">×</a>
        </header>
        <div class="top-controls-wrapper">
            <div class="container-botao-adicionar-equipamento">
                <button class="botao-adicionar-equipamento" id="addEquipmentBtn">Adicionar Equipamento</button>
            </div>

            <div class="container-pesquisa">
                <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou referência...">
                <button id="clearFiltersBtn" class="botao-limpar-filtros">Limpar filtros</button>
            </div>
        </div>
        <div id="cityButtonsContainer" class="city-buttons-container">
            <button class="city-button active" data-city="all">Mostrar Todos</button>
             <?php 
                $all_cities = array_unique(array_merge(
                    array_map(function($c) { return $c['nome']; }, $cities_com_radares),
                    array_map(function($c) { return $c['nome']; }, $cities_com_semaforos)
                ));
                sort($all_cities);
                foreach ($all_cities as $city_name) : 
            ?>
                <button class="city-button" data-city="<?php echo htmlspecialchars($city_name); ?>">
                    <?php echo htmlspecialchars($city_name); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div id="mainLoadingState" class="main-loading-state">
            <div class="main-loading-spinner"></div>
            <span>Carregando dados...</span>
        </div>
        <div id="containerListaEquipamentos" class="equipment-list-container">
        </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </main>
    <div class="footer">
        <p>© 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <button id="backToTopBtn" title="Voltar ao topo"><i class="fas fa-arrow-up"></i></button>

    <div id="addEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Adicionar Novo Equipamento</h3>
                <button class="close-button" id="closeAddEquipmentModal">×</button>
            </div>
            <form id="addEquipmentForm">
                <label for="equipmentType">Tipo de Equipamento:</label>
                <div id="addEquipmentType" class="equipment-type-group">
                    <label><input type="checkbox" name="tipo_equip[]" value="CCO"> CCO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="RADAR FIXO"> RADAR FIXO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="MONITOR DE SEMÁFORO"> MONITOR DE SEMÁFORO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="VÍDEO MONITORAMENTO"> VÍDEO MONITORAMENTO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="DOME"> DOME</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="LOMBADA ELETRÔNICA"> LOMBADA ELETRÔNICA</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="LAP"> LAP</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="EDUCATIVO"> EDUCATIVO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="SEMÁFORO"> SEMÁFORO</label>
                </div>
                <label for="equipmentName">Nome:</label>
                <input type="text" id="equipmentName" name="nome_equip" required>
                <label for="equipmentReference">Referência:</label>
                <input type="text" id="equipmentReference" name="referencia_equip">

                <div id="add-radar-lombada-fields" class="hidden">
                    <label for="add_dt_fabricacao">Data de Fabricação:</label>
                    <div class="custom-date-input"><input type="date" id="add_dt_fabricacao" name="dt_fabricacao"></div>
                    <label for="add_dt_sinalizacao_adicional">Data de Sinalização Adicional:</label>
                    <div class="custom-date-input"><input type="date" id="add_dt_sinalizacao_adicional" name="dt_sinalizacao_adicional"></div>
                    <label for="add_dt_inicio_processamento">Data de Início do Processamento:</label>
                    <div class="custom-date-input"><input type="date" id="add_dt_inicio_processamento" name="dt_inicio_processamento"></div>
                    <label for="add_num_certificado">Número do Certificado:</label>
                    <input type="text" id="add_num_certificado" name="num_certificado">
                </div>

                <label for="add_id_tecnico_instalacao">Técnico(s) da Instalação:</label>
                <select id="add_id_tecnico_instalacao" multiple> <option value="">Carregando técnicos...</option>
                </select>
                
                <label for="dt_instalacao">Data de Instalação:</label>
                <div class="custom-date-input"><input type="date" id="dt_instalacao" name="data_instalacao"></div>

                 <div id="add-specific-fields-container" class="hidden">
                    <label for="equipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="equipmentQtdFaixa" name="qtd_faixa">
                    <label for="equipmentKm">Velocidade (KM/h):</label>
                    <input type="text" id="equipmentKm" name="km">
                    <label for="equipmentSentido">Sentido:</label>
                    <input type="text" id="equipmentSentido" name="sentido">
                </div>
                <div id="add-afericao-fields-container" class="hidden">
                    <label for="numInstrumento">Nº Instrumento:</label>
                    <input type="text" id="numInstrumento" name="num_instrumento">
                    <label for="dtAfericao">Data Aferição:</label>
                    <div class="custom-date-input"><input type="date" id="dtAfericao" name="dt_afericao"></div>
                </div>
                <div id="add-date-fields-container">
                    <label for="add_dt_estudoTec">Data de Estudo Técnico:</label>
                    <div class="custom-date-input"><input type="date" id="add_dt_estudoTec" name="dt_estudoTec"></div>
                </div>

                <label for="equipmentCity">Cidade:</label>
                <select id="equipmentCity" name="id_cidade" required>
                    <option value="">Selecione a Cidade</option>
                </select>
                <label for="equipmentLogradouro">Logradouro:</label>
                <input type="text" id="equipmentLogradouro" name="logradouro" required>
                <label for="equipmentBairro">Bairro:</label>
                <input type="text" id="equipmentBairro" name="bairro" required>
                <label for="equipmentProvider">Provedor:</label>
                 <select id="equipmentProvider" name="id_provedor">
                    <option value="">Carregando...</option>
                </select>
                <label for="equipmentCep">CEP:</label>
                <input type="text" id="equipmentCep" name="cep">
                <label for="coordenadas">Coordenadas (Latitude, Longitude):</label>
                <input type="text" id="coordenadas" name="coordenadas" placeholder="-17.726909, -48.567032">
                
                <input type="hidden" name="status" value="ativo">


                <p id="addEquipmentMessage" class="message hidden"></p>
                <div class="form-buttons" id="add-form-buttons">
                    <button type="submit" class="save-button" id="saveAddEquipmentButton">Salvar Equipamento<span id="addEquipmentSpinner" class="loading-spinner"></span></button>
                    <button type="button" class="cancel-button" id="cancelAddEquipmentButton">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Equipamento</h3>
                <button class="close-button" id="closeEditEquipmentModal">×</button>
            </div>
            <form id="editEquipmentForm">
                <input type="hidden" id="editEquipmentId" name="id_equipamento">
                <input type="hidden" id="editEnderecoId" name="id_endereco">

                <label for="editEquipmentType">Tipo de Equipamento:</label>
                <div id="editEquipmentType" class="equipment-type-group">
                    <label><input type="checkbox" name="tipo_equip[]" value="CCO"> CCO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="RADAR FIXO"> RADAR FIXO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="MONITOR DE SEMÁFORO"> MONITOR DE SEMÁFORO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="VÍDEO MONITORAMENTO"> VÍDEO MONITORAMENTO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="DOME"> DOME</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="LOMBADA ELETRÔNICA"> LOMBADA ELETRÔNICA</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="LAP"> LAP</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="EDUCATIVO"> EDUCATIVO</label>
                    <label><input type="checkbox" name="tipo_equip[]" value="SEMÁFORO"> SEMÁFORO</label>
                </div>
                <label for="editEquipmentName">Nome:</label>
                <input type="text" id="editEquipmentName" name="nome_equip">
                <label for="editEquipmentReference">Referência:</label>
                <input type="text" id="editEquipmentReference" name="referencia_equip">

                <label for="editEquipmentStatus">Status:</label>
                <select id="editEquipmentStatus" name="status">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>
                <div id="edit-remanejado-date-container" class="hidden">
                    <label for="edit_dt_remanejado">Data de Remanejamento:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_remanejado" name="dt_remanejado"></div>
                </div>
                <div id="edit-inativo-date-container" class="hidden">
                    <label for="edit_dt_desativado">Data de Inativação:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_desativado" name="dt_desativado"></div>
                </div>
                
                <div id="edit-radar-lombada-fields" class="hidden">
                    <label for="edit_dt_fabricacao">Data de Fabricação:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_fabricacao" name="dt_fabricacao"></div>
                    <label for="edit_dt_sinalizacao_adicional">Data de Sinalização Adicional:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_sinalizacao_adicional" name="dt_sinalizacao_adicional"></div>
                    <label for="edit_dt_inicio_processamento">Data de Início do Processamento:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_inicio_processamento" name="dt_inicio_processamento"></div>
                    <label for="edit_num_certificado">Número do Certificado:</label>
                    <input type="text" id="edit_num_certificado" name="num_certificado">
                </div>

                <label for="edit_id_tecnico_instalacao">Técnico(s) da Instalação:</label>
                <select id="edit_id_tecnico_instalacao" multiple> <option value="">Carregando técnicos...</option>
                </select>
                
                <label for="edit_dt_instalacao">Data de Instalação:</label>
                <div class="custom-date-input"><input type="date" id="edit_dt_instalacao" name="data_instalacao"></div>

                <div id="edit-specific-fields-container" class="hidden">
                    <label for="editEquipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="editEquipmentQtdFaixa" name="qtd_faixa">
                    <label for="editEquipmentKm">Velocidade (KM/h):</label>
                    <input type="text" id="editEquipmentKm" name="km">
                    <label for="editEquipmentSentido">Sentido:</label>
                    <input type="text" id="editEquipmentSentido" name="sentido">
                </div>
                <div id="edit-afericao-fields-container" class="hidden">
                    <label for="editNumInstrumento">Nº Instrumento:</label>
                    <input type="text" id="editNumInstrumento" name="num_instrumento">
                    <label for="editDtAfericao">Data Aferição:</label>
                    <div class="custom-date-input"><input type="date" id="editDtAfericao" name="dt_afericao"></div>
                </div>
                <div id="edit-date-fields-container">
                    <label for="edit_dt_estudoTec">Data de Estudo Técnico:</label>
                    <div class="custom-date-input"><input type="date" id="edit_dt_estudoTec" name="dt_estudoTec"></div>
                </div>

                <label for="editEquipmentCity">Cidade:</label>
                 <select id="editEquipmentCity" name="id_cidade">
                    </select>
                <label for="editEquipmentLogradouro">Logradouro:</label>
                <input type="text" id="editEquipmentLogradouro" name="logradouro">
                <label for="editEquipmentBairro">Bairro:</label>
                <input type="text" id="editEquipmentBairro" name="bairro">
                <label for="editEquipmentProvider">Provedor:</label>
                <select id="editEquipmentProvider" name="id_provedor">
                    <option value="">Carregando...</option>
                </select>
                <label for="editEquipmentCep">CEP:</label>
                <input type="text" id="editEquipmentCep" name="cep">
                <label for="editCoordenadas">Coordenadas (Latitude, Longitude):</label>
                <input type="text" id="editCoordenadas" name="coordenadas" placeholder="-17.726909, -48.567032">

                <p id="editEquipmentMessage" class="message hidden"></p>
                <div class="form-buttons" id="edit-form-buttons">
                    <button type="submit" class="save-button" id="saveEditEquipmentButton">Salvar Alterações<span id="editEquipmentSpinner" class="loading-spinner"></span></button>
                    <button type="button" class="cancel-button" id="cancelEditEquipmentButton">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/equipamentos.js"></script>
</body>

</html>