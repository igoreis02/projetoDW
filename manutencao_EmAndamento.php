<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ocorrências em Andamento</title>
    <link rel="stylesheet" href="css/style.css">
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
            max-width: 1200px;
            text-align: center;
            position: relative;
        }

        /* Título e Botões de Navegação */
        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 2rem;
        }
        .header-container h2 {
            font-size: 2.2em;
            color: var(--cor-principal);
            margin: 0;
        }
        .close-btn, .back-btn-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }
        .close-btn { right: 0; }
        .back-btn-icon { left: 0; }
        .close-btn:hover, .back-btn-icon:hover { color: #333; }

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
            background-color: var(--cor-principal);
            color: white;
        }

        /* Filtros de Cidade */
        .filter-container {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
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
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        /* Container de Grupos de Cidade */
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
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        /* Item de Ocorrência Individual */
        .ocorrencia-item {
            background-color: #ffffff; 
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            transition: box-shadow 0.3s, transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .ocorrencia-item[data-type="instalação"] {
            border-left-color: #f97316; 
        }
        .ocorrencia-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        /* Layout do Cabeçalho do Item */
        .ocorrencia-header {
            text-align: left;
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
        
        /* Layout dos Detalhes do Item */
        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.8rem;
            flex-grow: 1;
            width: 100%;
        }
        .detail-item {
            font-size: 0.95em;
            color: #374151;
            line-height: 1.5;
            text-align: left;
        }
        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }
        .detail-item strong::after {
            content: ": ";
        }
        .detail-item span {
            word-break: break-word;
        }
        .detail-item.stacked strong {
            display: block;
        }
        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .status-em-andamento {
            background-color: #fffbeb;
            color: #f59e0b;
        }

        .ocorrencia-tag{
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .ocorrencia-em-andamento {
            background-color: #f0f9ff;
            color: #f59e0b;
        }
        .status-pendente {
            background-color: #eff6ff;
            color: #3b82f6;
        }
        .status-value.instalado {
            color: #16a34a;
            font-weight: 600;
        }
        .status-value.aguardando {
            color: #ef4444;
            font-weight: 600;
        }

        /* Botão Voltar */
        .voltar-btn {
            display: inline-block;
            width: auto;
            min-width: 200px;
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
        .voltar-btn:hover {
            background-color: var(--botao-voltar-hover);
        }

        .hidden { display: none !important; }

        /* Responsividade */
        @media (max-width: 1200px) {
            .city-ocorrencias-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .city-ocorrencias-grid {
                grid-template-columns: 1fr;
            }
            .card { padding: 1.5rem; }
            .header-container h2 { font-size: 1.8em; }
            .action-buttons, .filter-container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências em Andamento</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="action-buttons">
            <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Manutenções</button>
            <button id="btnInstalacoes" class="action-btn" data-type="instalação">Instalações</button>
        </div>

        <div id="filterContainer" class="filter-container">
            <!-- Botões de filtro de cidade serão inseridos aqui pelo JavaScript -->
        </div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <!-- Grupos de cidades e ocorrências serão inseridos aqui pelo JavaScript -->
            <p id="loadingMessage">A carregar ocorrências...</p>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionButtons = document.querySelectorAll('.action-btn');
            const filterContainer = document.getElementById('filterContainer');
            const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
            const loadingMessage = document.getElementById('loadingMessage');

            let activeType = 'manutencao';
            let activeCity = 'todos';

            // Função para buscar os dados da API
            async function fetchData() {
                try {
                    const response = await fetch('get_ocorrencias_em_andamento.php');
                    const result = await response.json();

                    loadingMessage.classList.add('hidden');

                    if (result.success) {
                        renderContent(result.data);
                    } else {
                        ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                    }
                } catch (error) {
                    console.error('Erro ao buscar dados:', error);
                    loadingMessage.classList.add('hidden');
                    ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
                }
            }

            // Função para renderizar os botões e as ocorrências
            function renderContent(data) {
                const { cidades, ocorrencias } = data;

                // Renderiza os botões de filtro
                filterContainer.innerHTML = '<button class="filter-btn active" data-city="todos">Todos</button>';
                if (cidades && cidades.length > 0) {
                    cidades.forEach(cidade => {
                        const button = document.createElement('button');
                        button.className = 'filter-btn';
                        button.dataset.city = cidade;
                        button.textContent = cidade;
                        filterContainer.appendChild(button);
                    });
                }

                // Renderiza as ocorrências
                ocorrenciasContainer.innerHTML = '';
                if (ocorrencias && Object.keys(ocorrencias).length > 0) {
                    for (const cidade in ocorrencias) {
                        const cityGroup = document.createElement('div');
                        cityGroup.className = 'city-group';
                        cityGroup.dataset.city = cidade;

                        let cityGridHTML = '';
                        ocorrencias[cidade].forEach(item => {
                            cityGridHTML += createOcorrenciaHTML(item);
                        });

                        cityGroup.innerHTML = `
                            <h2 class="city-group-title">${cidade}</h2>
                            <div class="city-ocorrencias-grid">
                                ${cityGridHTML}
                            </div>
                        `;
                        ocorrenciasContainer.appendChild(cityGroup);
                    }
                } else {
                    ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência em andamento encontrada.</p>`;
                }
                
                addFilterListeners();
                updateDisplay();
            }

            // Função para criar o HTML de um item de ocorrência
            function createOcorrenciaHTML(item) {
                const tempoReparo = calculateRepairTime(item.inicio_periodo_reparo, item.fim_periodo_reparo);
                const tipoOcorrencia = item.tipo_manutencao === 'instalação' ? 'Instalação' : item.tipo_manutencao.charAt(0).toUpperCase() + item.tipo_manutencao.slice(1);
                
                // Lógica para o status dinâmico
                const statusClass = item.status_reparo === 'pendente' ? 'status-pendente' : 'status-em-andamento';
                const statusText = item.status_reparo.charAt(0).toUpperCase() + item.status_reparo.slice(1);
                const statusHTML = `<span class="status-tag ${statusClass}">${statusText}</span>`;

                let detailsHTML = '';
                if (item.tipo_manutencao !== 'instalação') {
                    detailsHTML = `
                        <div class="detail-item"><strong>Ocorrência</strong> <span class="ocorrencia-tag status-em-andamento">${item.ocorrencia_reparo || ''}</span></div>
                        <div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        <div class="detail-item"><strong>Tempo Reparo</strong> <span>${tempoReparo}</span></div>
                    `;
                } else {
                    const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    detailsHTML = `
                        <div class="detail-item stacked"><strong>Tipo</strong> <span>${tipoOcorrencia}</span></div>
                        <div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>
                        <div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>
                        <div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>
                        <div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        <div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
                        <div class="detail-item"><strong>Tempo Instalação</strong> <span>${tempoReparo}</span></div>
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                    `;
                }

                return `
                    <div class="ocorrencia-item" data-type="${item.tipo_manutencao}">
                        <div class="ocorrencia-header">
                            <h3>${item.nome_equip} - ${item.referencia_equip}</h3>
                        </div>
                        <div class="ocorrencia-details">
                            ${detailsHTML}
                        </div>
                    </div>
                `;
            }

            function formatDate(dateString) {
                if (!dateString || dateString === '0000-00-00') return '';
                const date = new Date(dateString);
                return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
            }

            function calculateRepairTime(startDate, endDate) {
                if (!startDate || !endDate) return "N/A";
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                return `${formatDate(startDate)} até ${formatDate(endDate)} (${diffDays} dia(s))`;
            }

            function updateDisplay() {
                const cityGroups = document.querySelectorAll('.city-group');
                cityGroups.forEach(group => {
                    const groupCity = group.dataset.city;
                    let hasVisibleItemsInGroup = false;

                    const cityMatch = activeCity === 'todos' || groupCity === activeCity;

                    if (cityMatch) {
                        group.querySelectorAll('.ocorrencia-item').forEach(item => {
                            const itemType = item.dataset.type;
                            let typeMatch = false;

                            if (activeType === 'manutencao') {
                                if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) {
                                    typeMatch = true;
                                }
                            } else if (activeType === 'instalação') {
                                if (itemType === 'instalação') {
                                    typeMatch = true;
                                }
                            }

                            if (typeMatch) {
                                item.classList.remove('hidden');
                                hasVisibleItemsInGroup = true;
                            } else {
                                item.classList.add('hidden');
                            }
                        });

                        group.classList.toggle('hidden', !hasVisibleItemsInGroup);
                    } else {
                        group.classList.add('hidden');
                    }
                });
            }

            actionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    actionButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeType = button.dataset.type;
                    updateDisplay();
                });
            });

            function addFilterListeners() {
                const filterButtons = document.querySelectorAll('.filter-btn');
                filterButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        activeCity = button.dataset.city;
                        updateDisplay();
                    });
                });
            }

            // Inicia o carregamento dos dados
            fetchData();
        });
    </script>
</body>
</html>
