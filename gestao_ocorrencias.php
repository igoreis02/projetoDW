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
    <title>Gestão de Ocorrências</title>
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
            max-width: 1400px; /* Aumentado para mais espaço */
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
            color: var(--cor-principal);
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
            background-color: var(--cor-principal);
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
        .filter-btn:hover { background-color: #e5e7eb; }
        .filter-btn.active { color: white; }

        /* Cores dos botões de status */
        .filter-btn[data-status="pendente"].active { background-color: #3b82f6; border-color: #3b82f6; }
        .filter-btn[data-status="em andamento"].active { background-color: #f59e0b; border-color: #f59e0b; }
        .filter-btn[data-status="concluido"].active { background-color: #22c55e; border-color: #22c55e; }
        .filter-btn[data-status="cancelado"].active { background-color: #ef4444; border-color: #ef4444; }
        .filter-btn[data-status="todos"].active { background-color: #6366f1; border-color: #6366f1; }

        .city-filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            min-height: 38px;
        }

        /* Conteúdo */
        .ocorrencias-container { width: 100%; }
        .city-group { margin-bottom: 2.5rem; }
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        /* Cores da borda por status */
        .ocorrencia-item.status-pendente { border-left: 5px solid #3b82f6; }
        .ocorrencia-item.status-em-andamento { border-left: 5px solid #f59e0b; }
        .ocorrencia-item.status-concluido { border-left: 5px solid #22c55e; }
        .ocorrencia-item.status-cancelado { border-left: 5px solid #ef4444; }

        .ocorrencia-header {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #d1d5db;
            width: 100%;
        }
        .ocorrencia-header h3 { font-size: 1.3em; color: #111827; margin: 0; }
        
        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-grow: 1;
            width: 100%;
        }
        .detail-item { font-size: 0.95em; color: #374151; line-height: 1.5; }
        .detail-item strong { font-weight: 600; color: #1f2937; }
        .detail-item strong::after { content: ": "; }
        .status-value.instalado { color: #16a34a; font-weight: 600; }
        .status-value.aguardando { color: #ef4444; font-weight: 600; }
        
        /* Tags de Status */
        .status-tag {
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            color: white;
        }
        .tag-pendente { background-color: #3b82f6; }
        .tag-em-andamento { background-color: #f59e0b; }
        .tag-concluido { background-color: #22c55e; }
        .tag-cancelado { background-color: #ef4444; }

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
        .hidden { display: none !important; }

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loadingMessage = document.getElementById('loadingMessage');
            const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
            const actionButtons = document.querySelectorAll('.action-btn');
            const cityFilters = document.getElementById('cityFilters');
            const statusFilters = document.getElementById('statusFilters');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');

            let allData = [];
            let activeType = 'manutencao'; // Valor inicial
            let activeCity = 'todos';
            let activeStatus = 'todos';

            async function fetchData() {
                loadingMessage.classList.remove('hidden');
                ocorrenciasContainer.innerHTML = '';

                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                
                const params = new URLSearchParams({
                    type: activeType,
                    status: activeStatus,
                    data_inicio: startDate,
                    data_fim: endDate
                });

                try {
                    const response = await fetch(`get_gestao_ocorrencias.php?${params.toString()}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        allData = result.data;
                        renderAllOcorrencias(allData);
                        updateCityFilters(allData.cidades || []);
                        updateDisplay();
                    } else {
                        ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                        updateCityFilters([]);
                    }
                } catch (error) {
                    console.error('Erro ao buscar dados:', error);
                    ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados.</p>`;
                } finally {
                    loadingMessage.classList.add('hidden');
                }
            }

            function renderAllOcorrencias(data) {
                const { ocorrencias } = data;
                ocorrenciasContainer.innerHTML = '';
                if (ocorrencias && Object.keys(ocorrencias).length > 0) {
                    Object.keys(ocorrencias).sort().forEach(cidade => {
                        const cityGroup = document.createElement('div');
                        cityGroup.className = 'city-group';
                        cityGroup.dataset.city = cidade;
                        let cityGridHTML = '';
                        ocorrencias[cidade].forEach(item => {
                            cityGridHTML += createOcorrenciaHTML(item);
                        });
                        cityGroup.innerHTML = `
                            <h2 class="city-group-title">${cidade}</h2>
                            <div class="city-ocorrencias-grid">${cityGridHTML}</div>
                        `;
                        ocorrenciasContainer.appendChild(cityGroup);
                    });
                } else {
                     ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
                }
            }
            
            function updateCityFilters(cities) {
                const currentActiveCityButton = cityFilters.querySelector('.filter-btn.active');
                activeCity = currentActiveCityButton ? currentActiveCityButton.dataset.city : 'todos';

                cityFilters.innerHTML = '';
                const allButton = document.createElement('button');
                allButton.className = 'filter-btn';
                allButton.dataset.city = 'todos';
                allButton.textContent = 'Todas as Cidades';
                if (activeCity === 'todos') allButton.classList.add('active');
                cityFilters.appendChild(allButton);
                
                cities.forEach(cidade => {
                    const button = document.createElement('button');
                    button.className = 'filter-btn';
                    button.dataset.city = cidade;
                    button.textContent = cidade;
                    if (activeCity === cidade) button.classList.add('active');
                    cityFilters.appendChild(button);
                });

                document.querySelectorAll('#cityFilters .filter-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        document.querySelectorAll('#cityFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        activeCity = button.dataset.city;
                        updateDisplay();
                    });
                });
            }

            // FUNÇÃO ATUALIZADA para incluir detalhes da instalação
            function createOcorrenciaHTML(item) {
                const statusClass = item.status_reparo.replace(' ', '-');
                const statusTag = `<span class="status-tag tag-${statusClass}">${item.status_reparo}</span>`;

                let detailsHTML = '';
                const formatDate = (dateString) => {
                    if (!dateString || dateString === '0000-00-00') return '';
                    const date = new Date(dateString);
                    return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
                };

                // Verifica se é uma instalação para montar o HTML específico
                if (item.tipo_manutencao === 'instalação') {
                    
                    const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                    const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                    const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                    const provStatus = item.inst_prov == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_provedor)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;

                    detailsHTML += `<div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>`;
                    
                    // Adiciona o Laço apenas se o tipo de equipamento não for DOME
                    if (item.tipo_equip !== 'DOME') {
                        const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                        detailsHTML += `<div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>`;
                    }
                    
                    detailsHTML += `<div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>`;
                    detailsHTML += `<div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>`;
                    detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${provStatus}</span></div>`;

                } else {
                    // HTML padrão para manutenções
                    detailsHTML += `<div class="detail-item"><strong>Problema</strong> <span>${item.ocorrencia_reparo || 'N/A'}</span></div>`;
                    if (item.reparo_finalizado) {
                         detailsHTML += `<div class="detail-item"><strong>Reparo Realizado</strong> <span>${item.reparo_finalizado}</span></div>`;
                    }
                }

                // Adiciona informações comuns a ambos
                detailsHTML += `<div class="detail-item"><strong>Início</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>`;
                if (item.fim_reparo) {
                    detailsHTML += `<div class="detail-item"><strong>Conclusão</strong> <span>${new Date(item.fim_reparo).toLocaleString('pt-BR')}</span></div>`;
                }
                if (item.tecnicos_nomes) {
                    detailsHTML += `<div class="detail-item"><strong>Técnicos</strong> <span>${item.tecnicos_nomes}</span></div>`;
                }
                if (item.nome_prov && item.tipo_manutencao !== 'instalação') { // Provedor já mostrado em instalação
                    detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${item.nome_prov}</span></div>`;
                }
                detailsHTML += `<div class="detail-item"><strong>Status</strong> <span>${statusTag}</span></div>`;


                return `
                    <div class="ocorrencia-item status-${statusClass}" data-city="${item.cidade}">
                        <div class="ocorrencia-header">
                            <h3>${item.nome_equip} - ${item.referencia_equip}</h3>
                        </div>
                        <div class="ocorrencia-details">${detailsHTML}</div>
                    </div>`;
            }

            function updateDisplay() {
                 document.querySelectorAll('.city-group').forEach(group => {
                    group.style.display = (activeCity === 'todos' || group.dataset.city === activeCity) ? 'block' : 'none';
                });
            }
            
            // Listener para os botões de TIPO (Manutenção/Instalação)
            actionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    actionButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeType = button.dataset.type;
                    fetchData(); // Busca os novos dados
                });
            });

            statusFilters.addEventListener('click', (e) => {
                if(e.target.tagName === 'BUTTON') {
                    document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
                    e.target.classList.add('active');
                    activeStatus = e.target.dataset.status;
                    fetchData();
                }
            });

            startDateInput.addEventListener('change', fetchData);
            endDateInput.addEventListener('change', fetchData);

            fetchData();
        });
    </script>
</body>
</html>