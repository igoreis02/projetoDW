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
    <link rel="stylesheet" href="css/gestaoOcorrencias.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">

</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Gestão de Ocorrências</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
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

            </div>
            <div id="pageLoader" class="main-loading-state">
                <div class="main-loading-spinner"></div>
                <span>Carregando ocorrências...</span>
            </div>
        </div>
        <div id="simplifiedView" class="hidden"></div>


        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>

        <button id="btnVoltarAoTopo" title="Voltar ao topo">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/gestaoOcorrencias.js" defer></script>
</body>

</html>