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
    <title>Ocorrências de Provedores</title>
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        /* Item de Ocorrência Individual */
        .ocorrencia-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 5px solid #f97316; /* Laranja para provedores */
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: box-shadow 0.3s, transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
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
            padding: 8px 16px;
            font-size: 0.9em;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .concluir-btn {
            background-color: #22c55e; /* Verde */
            color: white;
        }
        .concluir-btn:hover {
            background-color: #16a34a;
        }
        .cancel-btn {
            background-color: #ef4444; /* Vermelho */
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

        /* Estilos para Modais */
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
        .form-group textarea, .form-group p {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1em;
            box-sizing: border-box;
            min-height: 100px;
            margin: 0;
        }
         .form-group p {
            background-color: #f3f4f6;
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
            background-color: #22c55e;
            color: white;
        }
        .btn-primary.cancel {
            background-color: #ef4444;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .confirmation-modal .modal-body {
            text-align: center;
            font-size: 1.1em;
        }
        .message {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .message.success { background-color: #dcfce7; color: #16a34a; }
        .message.error { background-color: #fee2e2; color: #ef4444; }

        .spinner {
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
        .spinner.is-active {
            display: inline-block;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências de Provedores</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div id="filterContainer" class="filter-container"></div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <p id="loadingMessage">A carregar ocorrências...</p>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <div id="concluirModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Concluir Ocorrência</h3>
                <button class="modal-close" onclick="closeModal('concluirModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="concluirModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Problema Reportado</label>
                    <p id="concluirOcorrenciaText"></p>
                </div>
                <div class="form-group">
                    <label for="reparoRealizadoTextarea">Descrição do Reparo</label>
                    <textarea id="reparoRealizadoTextarea" placeholder="Descreva o serviço que foi realizado..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('concluirModal')">Cancelar</button>
                <button class="modal-btn btn-primary" onclick="openConfirmationModal('concluir')">Confirmar Reparo</button>
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
                <strong id="confirmReparoText" class="hidden" style="display: block; margin-top: 10px; font-style: italic;"></strong>
            </div>
            <div class="modal-footer" id="confirmationButtons">
                <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                <button id="confirmActionButton" class="modal-btn btn-primary">
                    <span id="confirmActionText"></span>
                    <span id="confirmSpinner" class="spinner"></span>
                </button>
            </div>
            <p id="confirmationMessage" class="message hidden"></p>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ... (demais variáveis e funções existentes) ...
            const filterContainer = document.getElementById('filterContainer');
            const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
            const loadingMessage = document.getElementById('loadingMessage');

            let activeCity = 'todos';
            let allData = null;
            let currentItem = null;

            async function fetchData() {
                try {
                    const response = await fetch('get_ocorrencias_provedores.php');
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
                            <h2 class="city-group-title">${cidade}</h2>
                            <div class="city-ocorrencias-grid">
                                ${cityGridHTML}
                            </div>
                        `;
                        ocorrenciasContainer.appendChild(cityGroup);
                    }
                } else {
                    ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência de provedor pendente encontrada.</p>`;
                }
            }

            function updateCityFilters() {
                filterContainer.innerHTML = '';
                const cities = allData.cidades || [];
                const allButton = document.createElement('button');
                allButton.className = 'filter-btn active';
                allButton.dataset.city = 'todos';
                allButton.textContent = 'Todos';
                filterContainer.appendChild(allButton);

                cities.sort().forEach(cidade => {
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
                let atribuidoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Reportado por</strong> <span>${item.atribuido_por}</span></div>` : '';

                const detailsHTML = `
                    <div class="detail-item"><strong>Provedor</strong> <span>${item.nome_prov || 'Não especificado'}</span></div>
                    <div class="detail-item"><strong>Problema</strong> <span>${item.ocorrencia_reparo || 'Não especificado'}</span></div>
                    <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                    ${atribuidoPorHTML}
                    <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                    <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                `;

                // ADICIONADO BOTÃO DE CANCELAR
                const actionsHTML = `
                    <div class="item-actions">
                        <button class="item-btn concluir-btn" onclick="openConcluirModal(${item.id_manutencao})">Concluir</button>
                        <button class="item-btn cancel-btn" onclick="openConfirmationModal('cancelar', ${item.id_manutencao})">Cancelar</button>
                    </div>
                `;
                return `
                    <div class="ocorrencia-item" data-id="${item.id_manutencao}">
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
            
            function updateDisplay() {
                document.querySelectorAll('.city-group').forEach(group => {
                    group.style.display = (activeCity === 'todos' || group.dataset.city === activeCity) ? 'block' : 'none';
                });
            }

            function addFilterListeners() {
                document.querySelectorAll('.filter-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        activeCity = button.dataset.city;
                        updateDisplay();
                    });
                });
            }

            window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');
            window.closeModal = (modalId) => {
                const modal = document.getElementById(modalId);
                modal.classList.remove('is-active');

                if(modalId === 'confirmationModal') {
                    document.getElementById('confirmationButtons').style.display = 'flex';
                    document.getElementById('confirmationMessage').classList.add('hidden');
                    const confirmButton = document.getElementById('confirmActionButton');
                    const spinner = document.getElementById('confirmSpinner');
                    spinner.classList.remove('is-active');
                    confirmButton.disabled = false;
                }
            }

            function findOcorrenciaById(id) {
                for (const cidade in allData.ocorrencias) {
                    const found = allData.ocorrencias[cidade].find(item => item.id_manutencao == id);
                    if (found) return found;
                }
                return null;
            }

            window.openConcluirModal = (id) => {
                currentItem = findOcorrenciaById(id);
                if (!currentItem) return;

                document.getElementById('concluirModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
                document.getElementById('concluirOcorrenciaText').textContent = currentItem.ocorrencia_reparo;
                document.getElementById('reparoRealizadoTextarea').value = '';
                openModal('concluirModal');
            }

            window.openConfirmationModal = (type, id) => {
                if (id) {
                    currentItem = findOcorrenciaById(id);
                }

                const titleEl = document.getElementById('confirmationModalTitle');
                const textEl = document.getElementById('confirmationModalText');
                const reparoTextEl = document.getElementById('confirmReparoText');
                const actionButton = document.getElementById('confirmActionButton');
                const actionText = document.getElementById('confirmActionText');

                reparoTextEl.classList.add('hidden');
                actionButton.classList.remove('cancel');
                
                if (type === 'concluir') {
                    const reparoDesc = document.getElementById('reparoRealizadoTextarea').value;
                    if (!reparoDesc.trim()) {
                        alert('A descrição do reparo é obrigatória.');
                        return;
                    }
                    titleEl.textContent = 'Confirmar Conclusão';
                    textEl.textContent = 'Tem certeza que deseja marcar esta ocorrência como concluída com a seguinte descrição de reparo?';
                    reparoTextEl.textContent = `"${reparoDesc}"`;
                    reparoTextEl.classList.remove('hidden');
                    actionText.textContent = "Sim, Concluir";
                    actionButton.onclick = () => saveConclusion();
                    closeModal('concluirModal');

                } else if (type === 'cancelar') {
                    titleEl.textContent = 'Confirmar Cancelamento';
                    textEl.textContent = 'Tem certeza que deseja cancelar esta ocorrência? Esta ação não pode ser desfeita.';
                    actionText.textContent = "Sim, Cancelar";
                    actionButton.classList.add('cancel');
                    actionButton.onclick = () => executeStatusChange(currentItem.id_manutencao, 'cancelado');
                }

                openModal('confirmationModal');
            }
            
            async function executeStatusChange(id, status) {
                 const confirmButton = document.getElementById('confirmActionButton');
                const spinner = document.getElementById('confirmSpinner');
                const messageEl = document.getElementById('confirmationMessage');

                spinner.classList.add('is-active');
                confirmButton.disabled = true;

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
                     document.getElementById('confirmationButtons').style.display = 'none';

                    if (result.success) {
                        messageEl.textContent = 'Ocorrência cancelada com sucesso!';
                        messageEl.className = 'message success';
                        setTimeout(() => { closeModal('confirmationModal'); fetchData(); }, 2000);
                    } else {
                         throw new Error(result.message || 'Erro desconhecido.');
                    }
                } catch (error) {
                    messageEl.textContent = `Erro: ${error.message}`;
                    messageEl.className = 'message error';
                } finally {
                     messageEl.classList.remove('hidden');
                }
            }


            window.saveConclusion = async () => {
                const reparoFinalizado = document.getElementById('reparoRealizadoTextarea').value.trim();
                const confirmButton = document.getElementById('confirmActionButton');
                const spinner = document.getElementById('confirmSpinner');
                const messageEl = document.getElementById('confirmationMessage');

                spinner.classList.add('is-active');
                confirmButton.disabled = true;

                const dataToSend = {
                    action: 'concluir_provedor',
                    id_manutencao: currentItem.id_manutencao,
                    reparo_finalizado: reparoFinalizado
                };

                try {
                    const response = await fetch('update_ocorrencia.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dataToSend)
                    });
                    const result = await response.json();
                    document.getElementById('confirmationButtons').style.display = 'none';

                    if (result.success) {
                        messageEl.textContent = 'Ocorrência concluída com sucesso!';
                        messageEl.className = 'message success';
                        setTimeout(() => {
                            closeModal('confirmationModal');
                            fetchData();
                        }, 2000);
                    } else {
                        throw new Error(result.message || 'Erro desconhecido.');
                    }
                } catch (error) {
                    messageEl.textContent = `Erro: ${error.message}`;
                    messageEl.className = 'message error';
                } finally {
                    messageEl.classList.remove('hidden');
                }
            }

            fetchData();
        });
    </script>
</body>
</html>