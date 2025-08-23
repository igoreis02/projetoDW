// O JAVASCRIPT PERMANECE O MESMO, MAS O EVENT LISTENER FOI AJUSTADO
document.addEventListener('DOMContentLoaded', function () {
    const typeFilterContainer = document.getElementById('typeFilterContainer'); // Novo ID para o container
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    const cityFilterContainer = document.getElementById('cityFilterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const loadingMessage = document.getElementById('loadingMessage');

    let filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' };
    let allData = null, currentItem = null, currentPendentIds = new Set(), updateInterval;

    async function fetchData(isUpdateCheck = false) {
        if (!isUpdateCheck) {
            loadingMessage.classList.remove('hidden');
            ocorrenciasContainer.innerHTML = '';
            cityFilterContainer.innerHTML = '';
        }
        const params = new URLSearchParams({ tipo: filters.type, status: filters.status, data_inicio: filters.startDate, data_fim: filters.endDate });
        try {
            const response = await fetch(`API/get_ocorrencias_provedores.php?${params.toString()}`);
            const result = await response.json();
            if (isUpdateCheck) { handleUpdateCheck(result); return; }
            loadingMessage.classList.add('hidden');
            if (result.success) {
                allData = result.data;
                renderAllOcorrencias(allData);
                updateCityFilters();
                updateDisplay();
                updatePendentIds(result.data);
            } else {
                ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
            }
        } catch (error) {
            console.error('Erro ao buscar dados:', error);
            if (!isUpdateCheck) { loadingMessage.classList.add('hidden'); ocorrenciasContainer.innerHTML = `<p class="message error">Ocorreu um erro ao carregar os dados. Verifique a consola.</p>`; }
        }
    }

    function renderAllOcorrencias(data) {
        ocorrenciasContainer.innerHTML = '';
        if (data.ocorrencias && Object.keys(data.ocorrencias).length > 0) {
            const sortedCities = Object.keys(data.ocorrencias).sort();
            for (const cidade of sortedCities) {
                const cityGroup = document.createElement('div');
                cityGroup.className = 'city-group';
                cityGroup.dataset.city = cidade;
                let cityGridHTML = '';
                data.ocorrencias[cidade].forEach(item => { cityGridHTML += createOcorrenciaHTML(item); });
                cityGroup.innerHTML = `<h2 class="city-group-title">${cidade}</h2><div class="city-ocorrencias-grid">${cityGridHTML}</div>`;
                ocorrenciasContainer.appendChild(cityGroup);
            }
        } else {
            ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">Nenhuma ocorrência encontrada.</p>`;
        }
    }

    function createOcorrenciaHTML(item) {
        const statusClass = item.status_reparo || 'pendente';
        const statusHTML = `<span class="status-tag ${statusClass}">${statusClass}</span>`;
        const inicioReparoFormatted = item.inicio_reparo ? new Date(item.inicio_reparo).toLocaleString('pt-BR') : 'Data não informada';
        let atribuidoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Reportado por</strong> <span>${item.atribuido_por}</span></div>` : '';
        let reparoFinalizadoHTML = '';
        if (item.status_reparo === 'concluido' && item.reparo_finalizado) {
            reparoFinalizadoHTML = `<div class="detail-item reparo-info"><strong>Reparo Realizado</strong> <span>${item.reparo_finalizado}</span></div>`;
        }
        const detailsHTML = `
                    <div class="detail-item"><strong>Provedor</strong> <span>${item.nome_prov || 'Não especificado'}</span></div>
                    <div class="detail-item"><strong>Problema</strong> <span>${item.ocorrencia_reparo || 'Não especificado'}</span></div>
                    ${reparoFinalizadoHTML}
                    <div class="detail-item"><strong>Início Ocorrência</strong> <span>${inicioReparoFormatted}</span></div>
                    ${atribuidoPorHTML}
                    <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                    <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>`;

        let actionsContent = '';
        if (statusClass === 'pendente') {
            actionsContent = `<button class="item-btn concluir-btn" onclick="openConcluirModal(${item.id_manutencao})">Concluir</button><button class="item-btn edit-btn" onclick="openEditModal(${item.id_manutencao})">Editar</button><button class="item-btn cancel-btn" onclick="openConfirmationModal('cancelar', ${item.id_manutencao})">Cancelar</button>`;
        } else if (statusClass === 'concluido') {
            actionsContent = `<button class="item-btn edit-btn" onclick="openEditModal(${item.id_manutencao})">Editar</button>`;
        }
        const actionsHTML = actionsContent ? `<div class="item-actions">${actionsContent}</div>` : `<div class="item-actions" style="min-height: 40px;"></div>`;

        return `<div class="ocorrencia-item status-${statusClass}" data-id="${item.id_manutencao}"><div class="ocorrencia-header"><h3>${item.nome_equip} - ${item.referencia_equip}</h3></div><div class="ocorrencia-details">${detailsHTML}</div>${actionsHTML}</div>`;
    }

    function updateCityFilters() {
        cityFilterContainer.innerHTML = '';
        const cities = allData.cidades || [];
        if (cities.length > 0) {
            const allButton = document.createElement('button');
            allButton.className = 'filter-btn active city';
            allButton.dataset.city = 'todos';
            allButton.textContent = 'Todos';
            cityFilterContainer.appendChild(allButton);
            cities.sort().forEach(cidade => {
                const button = document.createElement('button');
                button.className = 'filter-btn city';
                button.dataset.city = cidade;
                button.textContent = cidade;
                cityFilterContainer.appendChild(button);
            });
        }
    }

    function updateDisplay() {
        document.querySelectorAll('.city-group').forEach(group => {
            group.style.display = (filters.city === 'todos' || group.dataset.city === filters.city) ? 'block' : 'none';
        });
    }

    function initializeFilters() {
        // <<< JS ATUALIZADO PARA O NOVO LAYOUT
        typeFilterContainer.addEventListener('click', (e) => {
            if (e.target.matches('.action-btn')) {
                document.querySelectorAll('#typeFilterContainer .action-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                filters.type = e.target.dataset.type;
                fetchData();
            }
        });
        statusFilterContainer.addEventListener('click', (e) => {
            if (e.target.matches('.filter-btn[data-status]')) {
                document.querySelectorAll('#statusFilterContainer .filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                filters.status = e.target.dataset.status;
                fetchData();
            }
        });
        cityFilterContainer.addEventListener('click', (e) => {
            if (e.target.matches('.filter-btn[data-city]')) {
                document.querySelectorAll('#cityFilterContainer .filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                filters.city = e.target.dataset.city;
                updateDisplay();
            }
        });
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        startDateInput.addEventListener('change', (e) => {
            filters.startDate = e.target.value;
            if (filters.endDate && filters.endDate < filters.startDate) {
                filters.endDate = filters.startDate;
                endDateInput.value = filters.startDate;
            }
            endDateInput.min = filters.startDate;
            fetchData();
        });
        endDateInput.addEventListener('change', (e) => { filters.endDate = e.target.value; fetchData(); });
    }

    function updatePendentIds(data) {
        currentPendentIds.clear();
        const sourceData = data || allData;
        if (sourceData && sourceData.ocorrencias) {
            for (const city in sourceData.ocorrencias) {
                sourceData.ocorrencias[city].forEach(item => {
                    if (item.status_reparo === 'pendente') {
                        currentPendentIds.add(item.id_manutencao);
                    }
                });
            }
        }
    }

    function startUpdatePolling() {
        if (updateInterval) clearInterval(updateInterval);
        updateInterval = setInterval(() => { fetchData(true); }, 30000);
    }

    function handleUpdateCheck(result) {
        if (!result.success) return;
        const newPendentIds = new Set();
        if (result.data.ocorrencias) {
            for (const city in result.data.ocorrencias) {
                result.data.ocorrencias[city].forEach(item => {
                    if (item.status_reparo === 'pendente') newPendentIds.add(item.id_manutencao);
                });
            }
        }
        if (newPendentIds.size !== currentPendentIds.size || [...newPendentIds].some(id => !currentPendentIds.has(id))) {
            if (filters.status === 'pendente' || filters.status === 'todos') fetchData();
        }
    }

    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');
    window.closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        modal.classList.remove('is-active');
        if (modalId === 'confirmationModal') {
            document.getElementById('confirmationButtons').style.display = 'flex';
            document.getElementById('confirmationMessage').classList.add('hidden');
            document.getElementById('confirmActionButton').disabled = false;
            document.getElementById('confirmSpinner').classList.remove('is-active');
        }
        if (modalId === 'editModal') {
            document.getElementById('editButtons').style.display = 'flex';
            document.getElementById('editMessage').classList.add('hidden');
            document.getElementById('saveEditBtn').disabled = false;
            document.getElementById('saveEditBtn').querySelector('.spinner').classList.remove('is-active');
        }
    };

    function findOcorrenciaById(id) {
        for (const city in allData.ocorrencias) {
            const found = allData.ocorrencias[city].find(item => item.id_manutencao == id);
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

    window.openEditModal = (id) => {
        currentItem = findOcorrenciaById(id);
        if (!currentItem) return;
        const reparoGroup = document.getElementById('editReparoGroup');
        const reparoTextarea = document.getElementById('editReparoTextarea');
        document.getElementById('editModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('editOcorrenciaTextarea').value = currentItem.ocorrencia_reparo;
        if (currentItem.status_reparo === 'concluido') {
            reparoTextarea.value = currentItem.reparo_finalizado || '';
            reparoGroup.classList.remove('hidden');
        } else {
            reparoTextarea.value = '';
            reparoGroup.classList.add('hidden');
        }
        openModal('editModal');
    }

    window.saveOcorrenciaEdit = async () => {
        const saveButton = document.getElementById('saveEditBtn');
        const spinner = saveButton.querySelector('.spinner');
        const messageEl = document.getElementById('editMessage');
        const newProblemText = document.getElementById('editOcorrenciaTextarea').value.trim();
        if (!newProblemText) { alert('A descrição do problema não pode ser vazia.'); return; }
        saveButton.disabled = true;
        spinner.classList.add('is-active');
        messageEl.classList.add('hidden');
        const dataToSend = {
            action: 'edit_ocorrencia',
            id_manutencao: currentItem.id_manutencao,
            ocorrencia_reparo: newProblemText
        };
        if (currentItem.status_reparo === 'concluido') {
            dataToSend.reparo_finalizado = document.getElementById('editReparoTextarea').value.trim();
        }
        try {
            const response = await fetch('API/update_ocorrencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dataToSend) });
            const result = await response.json();
            if (result.success) {
                document.getElementById('editButtons').style.display = 'none';
                messageEl.textContent = 'Ocorrência atualizada com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
                setTimeout(() => { closeModal('editModal'); fetchData(); }, 3000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            alert(`Erro ao salvar: ${error.message}`);
            saveButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    window.openConfirmationModal = (type, id) => {
        if (id) currentItem = findOcorrenciaById(id);
        if (!currentItem) return;
        const titleEl = document.getElementById('confirmationModalTitle');
        const textEl = document.getElementById('confirmationModalText');
        const reparoTextEl = document.getElementById('confirmReparoText');
        const actionButton = document.getElementById('confirmActionButton');
        const actionText = document.getElementById('confirmActionText');
        reparoTextEl.classList.add('hidden');
        actionButton.className = 'modal-btn btn-primary';
        if (type === 'concluir') {
            const reparoDesc = document.getElementById('reparoRealizadoTextarea').value;
            if (!reparoDesc.trim()) { alert('A descrição do reparo é obrigatória.'); return; }
            titleEl.textContent = 'Confirmar Conclusão';
            textEl.textContent = 'Deseja marcar esta ocorrência como concluída?';
            reparoTextEl.textContent = `Reparo: "${reparoDesc}"`;
            reparoTextEl.classList.remove('hidden');
            actionText.textContent = "Sim, Concluir";
            actionButton.onclick = () => saveConclusion();
        } else if (type === 'cancelar') {
            titleEl.textContent = 'Confirmar Cancelamento';
            textEl.textContent = 'Tem certeza que deseja cancelar esta ocorrência?';
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
        try {
            const response = await fetch('API/update_ocorrencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'update_status', id_manutencao: id, status: status }) });
            const result = await response.json();
            if (result.success) {
                document.getElementById('confirmationButtons').style.display = 'none';
                messageEl.textContent = 'Status atualizado com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
                setTimeout(() => { closeModal('confirmationModal'); closeModal('concluirModal'); fetchData(); }, 3000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            messageEl.textContent = `Erro: ${error.message}`;
            messageEl.className = 'message error';
            messageEl.classList.remove('hidden');
            confirmButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    window.saveConclusion = async () => {
        const reparoFinalizado = document.getElementById('reparoRealizadoTextarea').value.trim();
        const confirmButton = document.getElementById('confirmActionButton');
        const spinner = document.getElementById('confirmSpinner');
        const messageEl = document.getElementById('confirmationMessage');
        spinner.classList.add('is-active');
        confirmButton.disabled = true;
        try {
            const response = await fetch('API/update_ocorrencia.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'concluir_provedor', id_manutencao: currentItem.id_manutencao, reparo_finalizado: reparoFinalizado }) });
            const result = await response.json();
            if (result.success) {
                document.getElementById('confirmationButtons').style.display = 'none';
                messageEl.textContent = 'Ocorrência concluída com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
                setTimeout(() => { closeModal('confirmationModal'); closeModal('concluirModal'); fetchData(); }, 3000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            messageEl.textContent = `Erro: ${error.message}`;
            messageEl.className = 'message error';
            messageEl.classList.remove('hidden');
            confirmButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    initializeFilters();
    fetchData();
    startUpdatePolling();
});