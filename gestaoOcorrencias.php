<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Ocorrências</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <style>
        /* Estilos Gerais */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Card Principal */
        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1400px;
            /* Aumentado para mais espaço */
            text-align: center;
            position: relative;
        }

        /* Cabeçalho */
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

        .back-btn-icon {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            color: #aaa;
            text-decoration: none;
        }

        /* Botões de Ação (Manutenção/Instalação) */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .action-btn {
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            color: var(--cor-principal);
            background-color: #eef2ff;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background-color: #e0e7ff;
        }

        .action-btn.active {
            background-color: #112058;
            color: white;
        }


        /* Container de Filtros */
        .filters-wrapper {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .top-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .date-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-filter label {
            font-weight: 600;
            color: #374151;
        }

        .date-filter input {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .status-filters {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 18px;
            font-size: 0.9em;
            color: #4b5563;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background-color: #e5e7eb;
        }

        .filter-btn.active {
            color: white;
        }

        /* Cores dos botões de status */
        .filter-btn[data-status="pendente"].active {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }

        .filter-btn[data-status="em andamento"].active {
            background-color: #f59e0b;
            border-color: #f59e0b;
        }

        .filter-btn[data-status="concluido"].active {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        .filter-btn[data-status="cancelado"].active {
            background-color: #ef4444;
            border-color: #ef4444;
        }

        .filter-btn[data-status="todos"].active {
            background-color: #6366f1;
            border-color: #6366f1;
        }

        .city-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            min-height: 38px;
        }

        /* Conteúdo */
        .ocorrencias-container {
            width: 100%;
        }

        .city-group {
            margin-bottom: 2.5rem;
        }

        .city-group-title {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--cor-principal);
        }

        .city-ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        /* Card de Ocorrência */
        .ocorrencia-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            text-align: left;
        }

        /* Cores da borda por status */
        .ocorrencia-item.status-pendente {
            border-left: 5px solid #3b82f6;
        }

        .ocorrencia-item.status-em-andamento {
            border-left: 5px solid #f59e0b;
        }

        .ocorrencia-item.status-concluido {
            border-left: 5px solid #22c55e;
        }

        .ocorrencia-item.status-cancelado {
            border-left: 5px solid #ef4444;
        }

        .ocorrencia-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #d1d5db;
            width: 100%;
        }

        .ocorrencia-header h3 {
            font-size: 1.3em;
            color: #111827;
            margin: 0;
        }

        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-grow: 1;
            width: 100%;
        }

        .detail-item {
            font-size: 0.95em;
            color: #374151;
            line-height: 1.5;
        }

        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }

        .detail-item strong::after {
            content: ": ";
        }

        .status-value.instalado {
            color: #16a34a;
            font-weight: 600;
        }

        .status-value.aguardando {
            color: #ef4444;
            font-weight: 600;
        }

        /* Tags de Status */
        .status-tag {
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            color: white;
        }

        .tag-pendente {
            background-color: #3b82f6;
        }

        .tag-em-andamento {
            background-color: #f59e0b;
        }

        .tag-concluido {
            background-color: #22c55e;
        }

        .tag-cancelado {
            background-color: #ef4444;
        }

        .voltar-btn {
            display: inline-block;
            padding: 15px 30px;
            margin-top: 3rem;
            text-align: center;
            background-color: var(--botao-voltar);
            color: var(--cor-letra-botaoVoltar);
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Gestão de Ocorrências</h2>
        </div>

        <div class="action-buttons">
            <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Manutenções</button>
            <button id="btnInstalacoes" class="action-btn" data-type="instalacao">Instalações</button>
        </div>

        <div class="filters-wrapper">
            <div class="top-filters">
                <div class="date-filter">
                    <label for="startDate">De:</label>
                    <input type="date" id="startDate">
                    <label for="endDate">Até:</label>
                    <input type="date" id="endDate">
                </div>
                <div id="statusFilters" class="status-filters">
                    <button class="filter-btn active" data-status="todos">Todos</button>
                    <button class="filter-btn" data-status="pendente">Pendente</button>
                    <button class="filter-btn" data-status="em andamento">Em Andamento</button>
                    <button class="filter-btn" data-status="concluido">Concluído</button>
                    <button class="filter-btn" data-status="cancelado">Cancelado</button>
                </div>
            </div>
            <div id="cityFilters" class="city-filters"></div>
        </div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <p id="loadingMessage">A carregar ocorrências...</p>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <script src="js/gestaoOcorrencias.js"></script>
</body>

</html>