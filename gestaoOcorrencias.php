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

        .main-actions-filter {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }

        .left-actions {
            grid-column: 1;
            justify-self: start;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .action-buttons {
            grid-column: 2;
            display: flex;
            justify-content: center;
            gap: 1rem;
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
            white-space: nowrap;
        }

        .action-btn:hover {
            background-color: #e0e7ff;
        }

        .action-btn.active {
            background-color: #112058;
            color: white;
        }

        .filters-wrapper {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .top-filters {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .date-filter {
            grid-column: 3;
            justify-self: end;
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
            flex-wrap: wrap;
            justify-content: center;
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

        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            white-space: normal;
        }

        .tag-pendente {
            background-color: #eff6ff;
            color: #3b82f6;
        }

        .tag-em-andamento {
            background-color: #fffbeb;
            color: #f59e0b;
        }

        .tag-concluido {
            background-color: #f0fdf4;
            color: #22c55e;
        }

        .tag-cancelado {
            background-color: #ef4444;
            color: #b91c1c;
        }


        .ocorrencia-list {
            list-style: decimal;
            padding-left: 20px;
            margin: 5px 0;
        }

        .ocorrencia-list::marker {
            color: black;
            font-weight: bold;
        }

        .ocorrencia-list li {
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .ocorrencia-list li:last-child {
            border-bottom: none;
            margin-bottom: 0;
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

        .dashboard-view {
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .dashboard-card {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: left;
        }

        .dashboard-card-full {
            grid-column: 1 / -1;
        }

        .dashboard-card h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.1em;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .kpi-card-content {
            text-align: center;
        }

        .kpi-number {
            font-size: 3em;
            font-weight: 700;
            color: #112058;
            margin: 0;
        }

        .kpi-label {
            font-size: 1em;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .date-clear-wrapper {
            grid-column: 3;
            justify-self: end;
            display: flex;
            flex-direction: column;
            /* Organiza em coluna */
            align-items: flex-end;
            /* Alinha os itens à direita */
            gap: 0.5rem;
            /* Espaço entre a data e o botão */
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        #searchInput {
            padding: 10px 15px;
            font-size: 1em;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            width: 500px;
            max-width: 100%;
            /* Limita a largura da busca */
        }

        #btnClearFilters {
            padding: 8px 15px;
            /* Botão menor */
            font-size: 0.85em;
            /* Fonte menor */
            font-weight: 600;
            color: #4b5563;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            white-space: nowrap;
        }

        #btnClearFilters:hover {
            background-color: #e5e7eb;
        }

        .ocorrencia-list {
            list-style: decimal;
            padding-left: 20px;
            margin: 5px 0;
        }

        .ocorrencia-list::marker {
            color: black;
            font-weight: bold;
        }

        .ocorrencia-list li {
            padding-bottom: 0.5rem;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .ocorrencia-list li:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .ocorrencia-list li div strong {
            font-weight: bold;
            color: #1f2937;
            /* Cor preta/cinza escuro para os títulos */
        }

        .date-sub-item {
            font-size: 0.9em;
            color: #6b7280;
            padding-left: 5px;
            margin-top: 5px;
        }

        #limparFiltroData {
            /* Estilos para um botão pequeno e discreto */
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f8f9fa;
            margin-top: 5px;
            /* Adiciona um pequeno espaço acima do botão */
        }

        @media (max-width: 950px) {
            .main-actions-filter {
                grid-template-columns: 1fr;
                justify-items: center;
            }

            .left-actions {
                grid-column: 1;
                justify-self: center;
                order: 1;
                margin-bottom: 1rem;
            }

            .action-buttons {
                grid-column: 1;
                order: 2;
            }

            .date-filter {
                display: flex;
                align-items: center;
                gap: 10px;
            }
        }

        #simplifiedView {
            text-align: left;
            font-size: 1.3em;
            padding: 1rem 0.2rem;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
            width: 100%;
        }

        #simplifiedView h2 {
            font-size: 1.5em;
            color: black;
            margin-bottom: 1.5rem;
        }

        #simplifiedView h3 {
            font-size: 1.2em;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        #simplifiedView ul {
            list-style-type: none;
            padding-left: 0;
        }

        #simplifiedView li {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.1em;
            padding: 0.4rem 0.2rem;
            border-radius: 4px;
            position: relative;
            /* Para o posicionamento dos dias */
            margin-bottom: 0.5rem;
            border-left: 3px solid #6b7280;
            /* Cor padrão */
            padding-left: 10px;
        }

        #simplifiedView li:nth-child(odd) {
            background-color: #f9fafb;
        }

        /* Estilos baseados no status da ocorrência */
        #simplifiedView li.status-pendente {
            color: #3b82f6;
            /* Azul */
            border-left-color: #3b82f6;
        }

        #simplifiedView li.status-em-andamento {
            color: #f59e0b;
            /* Amarelo/Laranja */
            border-left-color: #f59e0b;
        }

        #simplifiedView li.status-concluido {
            color: #16a34a;
            /* Verde */
            border-left-color: #16a34a;
        }

        .card-dias-simplificado {
            float: right;
            text-align: right;
            font-size: 0.9em;
            color: #6b7280;
        }

        .dias-simplificado {
            font-weight: bold;
        }

        .cidade-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            /* Espaço entre a seta e o nome da cidade */
            user-select: none;
            /* Impede que o texto seja selecionado ao clicar */
        }

        .arrow-toggle {
            font-size: 0.8em;
            transition: transform 0.2s ease-in-out;
        }

         .mttr-label {
            text-align: center;
            font-size: 0.9em;
            color: #6b7280;
            font-weight: 600;
            margin-top: 1rem;
            border-top: 1px solid #f3f4f6;
            padding-top: 0.8rem;
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

        <div class="main-actions-filter">
            <div class="left-actions">
                <button id="btnDashboard" class="action-btn active">Dashboard</button>
                <button id="btnSimplificado" class="action-btn">Simplificado</button>
            </div>
            <div class="action-buttons">
                <button id="btnManutencoes" class="action-btn" data-type="manutencao">Ocorrências</button>
                <button id="btnInstalacoes" class="action-btn" data-type="instalacao">Instalações</button>
                <button id="btnSemaforica" class="action-btn" data-type="semaforica">Semafórica</button>
            </div>
            <div class="date-clear-wrapper">
                <div class="date-filter">
                    <label for="startDate">De:</label>
                    <input type="date" id="startDate">
                    <label for="endDate">Até:</label>
                    <input type="date" id="endDate">
                </div>
                <button id="limparFiltroData" style="display: none;">Limpar Filtro</button>
            </div>
        </div>

        <div id="searchContainer" class="search-container">
            <input type="search" id="searchInput" placeholder="Pesquisar por equipamento, status, cidade, técnico...">
            <button id="btnClearFilters">Limpar Filtros</button>
        </div>
        <div class="filters-wrapper hidden">
            <div class="top-filters">
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

        <div id="dashboardView" class="dashboard-view">
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div id="kpiManutencoesAbertas" class="kpi-card-content">
                        <p class="kpi-number">-</p>
                        <p class="kpi-label">Manutenções Abertas</p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div id="kpiConcluidasMes" class="kpi-card-content">
                        <p class="kpi-number">-</p>
                        <p id="concluidasLabel" class="kpi-label">Concluídas (Todo o Período)</p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div id="kpiMttr" class="kpi-card-content">
                        <p class="kpi-number">-</p>
                        <p class="kpi-label">Tempo Médio de Reparo (MTTR)</p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div id="kpiAfericoesVencendo" class="kpi-card-content">
                        <p class="kpi-number">-</p>
                        <p id="afericoesLabel" class="kpi-label">Aferições a Vencer (Total)</p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <h3 id="ocorrenciasTecnicasTitle">Ocorrências Técnicas</h3>
                    <canvas id="ocorrenciasTecnicasChart"></canvas>
                    <p class="mttr-label" id="mttrTecnicas">Média de Reparo: -</p>
                </div>
                <div class="dashboard-card">
                    <h3>Ocorrências Provedores</h3>
                    <canvas id="ocorrenciasProvedoresChart"></canvas>
                    <p class="mttr-label" id="mttrProvedores">Média de Reparo: -</p>
                </div>
                <div class="dashboard-card">
                    <h3>Ocorrências Processamento</h3>
                    <canvas id="ocorrenciasProcessamentoChart"></canvas>
                    <p class="mttr-label" id="mttrProcessamento">Média de Reparo: -</p>
                </div>
                <div class="dashboard-card">
                    <h3>Solicitações Clientes</h3>
                    <canvas id="solicitacoesClientesChart"></canvas>
                    <p class="mttr-label" id="mttrClientes">Média de Reparo: -</p>
                </div>
                <div class="dashboard-card dashboard-card-full">
                    <h3>Manutenções Abertas por Cidade</h3>
                    <canvas id="manutencoesPorCidadeChart"></canvas>
                </div>
                <div class="dashboard-card dashboard-card-full">
                    <h3 id="evolucaoTitle">Evolução Mensal (Todo o Período)</h3>
                    <canvas id="evolucaoDiariaChart"></canvas>
                </div>
            </div>
        </div>

        <div id="ocorrenciasView" class="hidden">

            <div id="ocorrenciasContainer" class="ocorrencias-container">
                <p id="loadingMessage">A carregar ocorrências...</p>
            </div>
        </div>
        <div id="simplifiedView" class="hidden"></div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/gestaoOcorrencias.js"></script>
</body>

</html>