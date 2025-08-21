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
    <title>Ocorrências Pendentes</title>
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
            min-height: 38px;
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
        
        .city-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--cor-principal);
        }

        .city-group-title {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin: 0;
            padding-bottom: 0.5rem;
        }
        
        .atribuir-cidade-btn {
            background-color: #3b82f6;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .atribuir-cidade-btn:hover {
            background-color: #2563eb;
        }

        .city-ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .ocorrencia-item {
            background-color: #ffffff; 
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); 
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            cursor: pointer;
        }
        
        .ocorrencia-item.selected {
            background-color: #eef2ff;
            border-color: #6366f1;
            transform: translateY(-2px);
        }

        .ocorrencia-item[data-type="instalação"] {
            border-left-color: #f97316; 
        }
        .ocorrencia-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
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
        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
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
        .item-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            width: 100%;
        }
        .item-btn {
            padding: 6px 12px;
            font-size: 0.9em;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .edit-btn {
            background-color: #f59e0b; /* Laranja */
            color: white;
        }
        .edit-btn:hover {
            background-color: #d97706;
        }
        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #dc2626;
        }
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }
        .modal.is-active {
            display: flex;
        }
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 600px;
            text-align: left;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.5em;
            color: #111827;
        }
        .modal-close {
            font-size: 2rem;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
            background: none;
            border: none;
        }
        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-group label {
            font-weight: 600;
            color: #374151;
        }
        .form-group input, .form-group textarea, .form-group p {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1em;
            box-sizing: border-box;
            margin: 0;
        }
        .form-group p {
            background-color: #f3f4f6;
            min-height: 44px;
        }
        .date-inputs {
            display: flex;
            gap: 1rem;
        }
        .date-inputs .form-group {
            flex: 1;
        }
        .choice-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .choice-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            background-color: #f9fafb;
            transition: all 0.2s;
        }
        .choice-btn.selected {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .confirmation-modal .modal-body {
            text-align: center;
            font-size: 1.1em;
        }
        @media (max-width: 1200px) { .city-ocorrencias-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .city-ocorrencias-grid { grid-template-columns: 1fr; } .card { padding: 1.5rem; } .header-container h2 { font-size: 1.8em; } .action-buttons, .filter-container { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências Pendentes</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="action-buttons">
            <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Manutenções</button>
            <button id="btnInstalacoes" class="action-btn" data-type="instalação">Instalações</button>
        </div>

        <div id="filterContainer" class="filter-container"></div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <p id="loadingMessage">A carregar ocorrências...</p>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atribuir Técnico</h3>
                <button class="modal-close" onclick="closeModal('assignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="assignModalInfo"></div>
                <div class="form-group">
                    <label>Selecione a data para execução</label>
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="assignInicioReparo">Início Reparo</label>
                            <input type="date" id="assignInicioReparo">
                        </div>
                        <div class="form-group">
                            <label for="assignFimReparo">Fim Reparo</label>
                            <input type="date" id="assignFimReparo">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Técnicos</label>
                    <div id="assignTecnicosContainer" class="choice-buttons"></div>
                </div>
                <div class="form-group">
                    <label>Veículos</label>
                    <div id="assignVeiculosContainer" class="choice-buttons"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('assignModal')">Fechar</button>
                <button class="modal-btn btn-primary" onclick="saveAssignment()">Salvar Atribuição</button>
            </div>
        </div>
    </div>

    <div id="editOcorrenciaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Ocorrência</h3>
                <button class="modal-close" onclick="closeModal('editOcorrenciaModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="editOcorrenciaModalInfo"></div>
                <div class="form-group">
                    <label for="editOcorrenciaTextarea">Descrição da Ocorrência</label>
                    <textarea id="editOcorrenciaTextarea" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('editOcorrenciaModal')">Cancelar</button>
                <button class="modal-btn btn-primary" onclick="saveOcorrenciaUpdate()">Salvar Alteração</button>
            </div>
        </div>
    </div>


    <div id="confirmationModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirmationModalTitle"></h3>
                <button class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmationModalText"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                <button id="confirmActionButton" class="modal-btn btn-primary">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionButtons = document.querySelectorAll('.action-btn');
            const filterContainer = document.getElementById('filterContainer');
            const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
            const loadingMessage = document.getElementById('loadingMessage');

            let activeType = 'manutencao';
            let activeCity = 'todos';
            let allData = null; 
            let currentItemsToAssign = [];
            let currentEditingItem = null;

            async function fetchData() {
                try {
                    const response = await fetch('get_ocorrencias_pendentes.php');
                    const result = await response.json();
                    loadingMessage.classList.add('hidden');
                    if (result.success) {
                        allData = result.data;
                        renderAllOcorrencias(allData);
                        updateCityFilters();
                        updateDisplay();
                    } else {
                        ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                    }
                } catch (error) {
                    console.error('Erro ao buscar dados:', error);
                    loadingMessage.classList.add('hidden');
                    ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
                }
            }

            function renderAllOcorrencias(data) {
                const { ocorrencias } = data;
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
                            <div class="city-group-header">
                                <h2 class="city-group-title">${cidade}</h2>
                                <button class="atribuir-cidade-btn hidden" data-city="${cidade}" onclick="handleMultiAssignClick(this)">Atribuir</button>
                            </div>
                            <div class="city-ocorrencias-grid">
                                ${cityGridHTML}
                            </div>
                        `;
                        ocorrenciasContainer.appendChild(cityGroup);
                    }
                } else {
                    ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada.</p>`;
                }
            }
            
            function updateCityFilters() {
                filterContainer.innerHTML = ''; 
                const citiesWithContent = new Set();
                document.querySelectorAll('.ocorrencia-item').forEach(item => {
                    const itemType = item.dataset.type;
                    let typeMatch = false;
                    if (activeType === 'manutencao') {
                        if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) typeMatch = true;
                    } else if (activeType === 'instalação') {
                        if (itemType === 'instalação') typeMatch = true;
                    }
                    if (typeMatch) {
                        const city = item.closest('.city-group').dataset.city;
                        citiesWithContent.add(city);
                    }
                });
                const allButton = document.createElement('button');
                allButton.className = 'filter-btn active';
                allButton.dataset.city = 'todos';
                allButton.textContent = 'Todos';
                filterContainer.appendChild(allButton);
                Array.from(citiesWithContent).sort().forEach(cidade => {
                    const button = document.createElement('button');
                    button.className = 'filter-btn';
                    button.dataset.city = cidade;
                    button.textContent = cidade;
                    filterContainer.appendChild(button);
                });
                addFilterListeners();
            }

            function createOcorrenciaHTML(item) {
                const statusHTML = `<span class="status-tag status-pendente">Pendente</span>`;
                let detailsHTML = '';
                let atribuidoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Solicitado por</strong> <span>${item.atribuido_por}</span></div>` : '';

                if (item.tipo_manutencao === 'instalação') {
                    const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    const provStatus = item.inst_prov == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_provedor)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                    
                    detailsHTML = `
                        <div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>
                        <div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>
                        <div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>
                        <div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>
                        <div class="detail-item"><strong>Provedor</strong> <span>${provStatus}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        ${atribuidoPorHTML}
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        ${item.observacao_instalacao ? `<div class="detail-item"><strong>Observação</strong> <span>${item.observacao_instalacao}</span></div>` : ''}
                    `;
                } else {
                     detailsHTML = `
                        <div class="detail-item"><strong>Ocorrência</strong> <span>${item.ocorrencia_reparo || 'Não especificada'}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        ${atribuidoPorHTML}
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        ${item.motivo_devolucao ? `<div class="detail-item"><strong>Devolvida</strong> <span>${item.motivo_devolucao}</span></div>` : ''}
                    `;
                }

                const actionsHTML = `
                    <div class="item-actions">
                        <button class="item-btn edit-btn" onclick="openEditOcorrenciaModal(${item.id_manutencao}, event)">Editar</button>
                        <button class="item-btn cancel-btn" onclick="openConfirmationModal(${item.id_manutencao}, 'cancelado', event)">Cancelar</button>
                    </div>
                `;

                return `
                    <div class="ocorrencia-item" data-type="${item.tipo_manutencao}" data-id="${item.id_manutencao}">
                        <div class="ocorrencia-header">
                            <h3>${item.nome_equip} - ${item.referencia_equip}</h3>
                        </div>
                        <div class="ocorrencia-details">
                            ${detailsHTML}
                        </div>
                        ${actionsHTML}
                    </div>
                `;
            }
            
            function formatDate(dateString) {
                if (!dateString || dateString === '0000-00-00') return '';
                const date = new Date(dateString);
                return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
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
                                if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) typeMatch = true;
                            } else if (activeType === 'instalação') {
                                if (itemType === 'instalação') typeMatch = true;
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
                checkSelectionAndToggleButtons();
            }

            actionButtons.forEach(button => {
                button.addEventListener('click', () => {
                    actionButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                    activeType = button.dataset.type;
                    activeCity = 'todos'; 
                    updateCityFilters(); 
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

            window.openModal = function(modalId) { document.getElementById(modalId).classList.add('is-active'); }
            window.closeModal = function(modalId) { document.getElementById(modalId).classList.remove('is-active'); }

            function findOcorrenciaById(id) {
                for (const cidade in allData.ocorrencias) {
                    const found = allData.ocorrencias[cidade].find(item => item.id_manutencao == id);
                    if (found) return found;
                }
                return null;
            }

            ocorrenciasContainer.addEventListener('click', function(e) {
                if (e.target.closest('.item-btn')) {
                    return;
                }
                const item = e.target.closest('.ocorrencia-item');
                if (item) {
                    item.classList.toggle('selected');
                    checkSelectionAndToggleButtons();
                }
            });

            function checkSelectionAndToggleButtons() {
                document.querySelectorAll('.city-group').forEach(group => {
                    const city = group.dataset.city;
                    const selectedItems = group.querySelectorAll('.ocorrencia-item.selected:not(.hidden)');
                    const btn = group.querySelector(`.atribuir-cidade-btn[data-city="${city}"]`);
                    
                    if (btn) {
                        btn.classList.toggle('hidden', selectedItems.length === 0);
                    }
                });
            }

            window.handleMultiAssignClick = async function(button) {
                const city = button.dataset.city;
                const group = document.querySelector(`.city-group[data-city="${city}"]`);
                const selectedItems = group.querySelectorAll('.ocorrencia-item.selected:not(.hidden)');
                
                currentItemsToAssign = Array.from(selectedItems).map(itemEl => {
                    const itemId = itemEl.dataset.id;
                    return findOcorrenciaById(itemId);
                });

                if (currentItemsToAssign.length === 0) return;
                
                const modalInfo = document.getElementById('assignModalInfo');
                modalInfo.innerHTML = `<p><strong>${currentItemsToAssign.length} ocorrência(s) selecionada(s) em ${city}.</strong></p>`;

                document.getElementById('assignInicioReparo').value = '';
                document.getElementById('assignFimReparo').value = '';
                
                const tecnicosContainer = document.getElementById('assignTecnicosContainer');
                const veiculosContainer = document.getElementById('assignVeiculosContainer');
                tecnicosContainer.innerHTML = 'A carregar...';
                veiculosContainer.innerHTML = 'A carregar...';
                
                openModal('assignModal');

                const [tecnicosRes, veiculosRes] = await Promise.all([fetch('get_tecnicos.php'), fetch('get_veiculos.php')]);
                const tecnicosData = await tecnicosRes.json();
                const veiculosData = await veiculosRes.json();
                
                tecnicosContainer.innerHTML = '';
                if (tecnicosData.success) {
                    tecnicosData.tecnicos.forEach(tec => {
                        const btn = document.createElement('button');
                        btn.className = 'choice-btn';
                        btn.dataset.id = tec.id_tecnico;
                        btn.textContent = tec.nome;
                        btn.onclick = () => btn.classList.toggle('selected');
                        tecnicosContainer.appendChild(btn);
                    });
                }

                veiculosContainer.innerHTML = '';
                if (veiculosData.length > 0) {
                    veiculosData.forEach(vec => {
                        const btn = document.createElement('button');
                        btn.className = 'choice-btn';
                        btn.dataset.id = vec.id_veiculo;
                        btn.textContent = `${vec.nome} (${vec.placa})`;
                        btn.onclick = () => btn.classList.toggle('selected');
                        veiculosContainer.appendChild(btn);
                    });
                }
            }

            window.saveAssignment = async function() {
                const selectedTecnicos = Array.from(document.querySelectorAll('#assignTecnicosContainer .choice-btn.selected')).map(btn => btn.dataset.id);
                const selectedVeiculos = Array.from(document.querySelectorAll('#assignVeiculosContainer .choice-btn.selected')).map(btn => btn.dataset.id);

                if (selectedTecnicos.length === 0) {
                    alert('Por favor, selecione pelo menos um técnico.');
                    return;
                }

                for (const item of currentItemsToAssign) {
                    const dataToSend = {
                        action: 'assign',
                        id_manutencao: item.id_manutencao,
                        ocorrencia_reparo: item.ocorrencia_reparo,
                        inicio_reparo: document.getElementById('assignInicioReparo').value,
                        fim_reparo: document.getElementById('assignFimReparo').value,
                        tecnicos: selectedTecnicos,
                        veiculos: selectedVeiculos
                    };

                    try {
                        const response = await fetch('update_ocorrencia.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(dataToSend)
                        });
                        const result = await response.json();
                        if (!result.success) {
                            alert(`Erro ao salvar a ocorrência ${item.id_manutencao}: ${result.message}`);
                        }
                    } catch (error) {
                        alert(`Erro de comunicação com o servidor para a ocorrência ${item.id_manutencao}.`);
                    }
                }
                
                closeModal('assignModal');
                fetchData();
            }

            window.openEditOcorrenciaModal = function(id, event) {
                event.stopPropagation();
                currentEditingItem = findOcorrenciaById(id);
                if (!currentEditingItem) return;

                document.getElementById('editOcorrenciaModalInfo').innerHTML = `
                    <p><strong>Equipamento:</strong> ${currentEditingItem.nome_equip} - ${currentEditingItem.referencia_equip}</p>
                `;
                document.getElementById('editOcorrenciaTextarea').value = currentEditingItem.ocorrencia_reparo;
                
                openModal('editOcorrenciaModal');
            }

            window.saveOcorrenciaUpdate = async function() {
                const newOcorrenciaText = document.getElementById('editOcorrenciaTextarea').value;
                if (!newOcorrenciaText.trim()) {
                    alert('A descrição da ocorrência não pode ficar em branco.');
                    return;
                }
                
                const dataToSend = {
                    action: 'edit_ocorrencia',
                    id_manutencao: currentEditingItem.id_manutencao,
                    ocorrencia_reparo: newOcorrenciaText
                };

                try {
                    const response = await fetch('update_ocorrencia.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dataToSend)
                    });
                    const result = await response.json();
                    if (result.success) {
                        closeModal('editOcorrenciaModal');
                        fetchData();
                    } else {
                        alert('Erro ao salvar alteração: ' + result.message);
                    }
                } catch (error) {
                    alert('Erro de comunicação com o servidor.');
                }
            }


            window.openConfirmationModal = function(id, status, event) {
                if(event) event.stopPropagation();
                const title = 'Cancelar Ocorrência';
                const text = 'Tem a certeza de que deseja cancelar esta ocorrência? Esta ação não pode ser desfeita.';
                
                document.getElementById('confirmationModalTitle').textContent = title;
                document.getElementById('confirmationModalText').textContent = text;
                
                const confirmBtn = document.getElementById('confirmActionButton');
                confirmBtn.onclick = () => executeStatusChange(id, status);

                openModal('confirmationModal');
            }

            async function executeStatusChange(id, status) {
                const dataToSend = {
                    action: 'update_status',
                    id_manutencao: id,
                    status: status
                };

                try {
                    const response = await fetch('update_ocorrencia.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dataToSend)
                    });
                    const result = await response.json();
                    if (result.success) {
                        closeModal('confirmationModal');
                        fetchData();
                    } else {
                        alert('Erro ao alterar o status: ' + result.message);
                    }
                } catch (error) {
                    alert('Erro de comunicação com o servidor.');
                }
            }
            
            fetchData();
        });
    </script>
</body>
</html>