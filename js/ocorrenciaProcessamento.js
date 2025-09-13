document.addEventListener('DOMContentLoaded', function () {
    // --- Referências aos Elementos do DOM ---
    const typeFilterContainer = document.getElementById('typeFilterContainer');
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    const cityFilterContainer = document.getElementById('cityFilterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const loadingMessage = document.getElementById('loadingMessage');
    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const searchSpacer = document.querySelector('.search-spacer');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');
    const reparoRealizadoError = document.getElementById('reparoRealizadoError');

    // --- Variáveis de Estado ---
    let filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' };
    let allData = null, currentItem = null;

    // --- Funções Principais ---

    async function initialLoad() {
        loadingMessage.classList.remove('hidden');

        // Busca inicial para verificar a existência de manutenções em validação
        const paramsCheck = new URLSearchParams({ status: 'validacao' });
        try {
            const response = await fetch(`API/get_ocorrencias_processamento.php?${paramsCheck.toString()}`);
            const result = await response.json();

            let hasValidacao = false;
            if (result.success && result.data.ocorrencias && Object.keys(result.data.ocorrencias).length > 0) {
                const allItems = Object.values(result.data.ocorrencias).flat();
                if (allItems.length > 0) {
                    hasValidacao = true;
                }
            }

            const btnValidacao = document.getElementById('btnValidacao');
            if (hasValidacao) {
                btnValidacao.style.display = ''; // Mostra o botão
                filters.status = 'validacao'; // Define como filtro padrão
            } else {
                btnValidacao.style.display = 'none'; // Garante que o botão está escondido
                filters.status = 'todos'; // Mantém o padrão 'todos'
            }

            // Atualiza a aparência dos botões de filtro de status
            document.querySelectorAll('#statusFilterContainer .filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === filters.status);
            });

            // Agora, busca os dados novamente com o filtro correto definido
            await fetchData();

        } catch (error) {
            console.error('Erro na carga inicial:', error);
            ocorrenciasContainer.innerHTML = `<p class="message error">Ocorreu um erro na carga inicial.</p>`;
        } finally {
            loadingMessage.classList.add('hidden');
        }
    }

    async function fetchData() {
        ocorrenciasContainer.innerHTML = `<p id="loadingMessage">Carregando suas manutenções...</p>`;
        cityFilterContainer.innerHTML = '';

        const params = new URLSearchParams({ tipo: filters.type, status: filters.status, data_inicio: filters.startDate, data_fim: filters.endDate });
        try {
            const response = await fetch(`API/get_ocorrencias_processamento.php?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                allData = result.data;
                renderAllOcorrencias(allData);
                updateCityFilters();
                updateDisplay();
                adjustSearchSpacer();
            } else {
                ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
            }
        } catch (error) {
            console.error('Erro ao buscar dados:', error);
            ocorrenciasContainer.innerHTML = `<p class="message error">Ocorreu um erro ao carregar os dados.</p>`;
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
                data.ocorrencias[cidade].forEach(item => {
                    cityGridHTML += `<div class="ocorrencia-item-wrapper">${createOcorrenciaHTML(item)}</div>`;
                });
                cityGroup.innerHTML = `<h2 class="city-group-title">${cidade}</h2><div class="city-ocorrencias-grid">${cityGridHTML}</div>`;
                ocorrenciasContainer.appendChild(cityGroup);
            }
        } else {
            ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
        }
    }

    function createOcorrenciaHTML(item) {
        const statusClass = (item.status || 'pendente').toLowerCase();

        const inicioReparoFormatted = item.inicio_reparo ? new Date(item.inicio_reparo).toLocaleString('pt-BR') : 'N/A';
        const fimReparoFormatted = item.fim_reparo ? new Date(item.fim_reparo).toLocaleString('pt-BR') : 'N/A';
        let atribuidoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Reportado por</strong> <span>${item.atribuido_por}</span></div>` : '';
        let reparoFinalizadoHTML = '';

        if ((statusClass === 'concluido' || statusClass === 'validacao') && item.reparo_finalizado) {
            reparoFinalizadoHTML = `<div class="detail-item reparo-info"><strong>Solução</strong> <span>${item.reparo_finalizado}</span></div>`;
        }

        const detailsHTML = `
            <div class="detail-item"><strong>Problema</strong> <span class="searchable">${item.ocorrencia_reparo || 'N/A'}</span></div>
            ${reparoFinalizadoHTML}
            <div class="detail-item"><strong>Início Ocorrência</strong> <span>${inicioReparoFormatted}</span></div>
            ${statusClass === 'concluido' ? `<div class="detail-item"><strong>Fim Ocorrência</strong> <span>${fimReparoFormatted}</span></div>` : ''}
            ${atribuidoPorHTML}
            <div class="detail-item"><strong>Status</strong> <span class="status-tag ${statusClass}">${item.status}</span></div>
            <div class="detail-item"><strong>Local</strong> <span class="searchable">${item.local_completo || 'N/A'}</span></div>
        `;

        let actionsContent = '';
        if (statusClass === 'validacao') {
            actionsContent = `<button class="item-btn validar-btn" onclick="openValidarModal(${item.id})">Validar</button>`;
        } else if (statusClass === 'pendente') {
            actionsContent = `<button class="item-btn concluir-btn" onclick="openConcluirModal(${item.id}, '${item.origem}')">Concluir</button><button class="item-btn edit-btn" onclick="openEditModal(${item.id}, '${item.origem}')">Editar</button><button class="item-btn cancel-btn" onclick="openConfirmationModal('cancelar', ${item.id}, '${item.origem}')">Cancelar</button>`;
        } else if (statusClass === 'concluido') {
            actionsContent = `<button class="item-btn edit-btn" onclick="openEditModal(${item.id}, '${item.origem}')">Editar</button>`;
        }
        const actionsHTML = actionsContent ? `<div class="item-actions">${actionsContent}</div>` : `<div class="item-actions" style="min-height: 40px;"></div>`;

        return `<div class="ocorrencia-item status-${statusClass}" data-id="${item.id}" data-origem="${item.origem}">
                    <div class="ocorrencia-header"><h3><span class="searchable">${item.nome_equip}</span> - <span class="searchable">${item.referencia_equip}</span></h3></div>
                    <div class="ocorrencia-details">${detailsHTML}</div>
                    ${actionsHTML}
                </div>`;
    }

    function updateDisplay() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const currentCity = filters.city;
        document.querySelectorAll('.city-group').forEach(group => {
            let hasVisibleItemsInGroup = false;
            const groupCity = group.dataset.city;
            const isCityVisible = currentCity === 'todos' || groupCity === currentCity;
            if (isCityVisible) {
                group.querySelectorAll('.ocorrencia-item-wrapper').forEach(wrapper => {
                    const item = wrapper.querySelector('.ocorrencia-item');
                    const searchableSpans = item.querySelectorAll('.searchable');
                    let searchableText = '';
                    searchableSpans.forEach(span => { searchableText += (span.textContent || span.innerText) + ' '; });
                    searchableText = searchableText.toLowerCase();
                    const isSearchMatch = searchTerm === '' || searchableText.includes(searchTerm);
                    if (isSearchMatch) {
                        wrapper.style.display = 'block';
                        hasVisibleItemsInGroup = true;
                    } else {
                        wrapper.style.display = 'none';
                    }
                });
            }
            group.style.display = isCityVisible && hasVisibleItemsInGroup ? 'block' : 'none';
        });
    }

    function adjustSearchSpacer() {
        if (clearFiltersBtn && searchSpacer) {
            searchSpacer.style.width = `${clearFiltersBtn.offsetWidth}px`;
        }
    }

    function initializeFilters() {
        typeFilterContainer.addEventListener('click', (e) => { if (e.target.matches('.action-btn')) { document.querySelectorAll('#typeFilterContainer .action-btn').forEach(btn => btn.classList.remove('active')); e.target.classList.add('active'); filters.type = e.target.dataset.type; fetchData(); } });
        statusFilterContainer.addEventListener('click', (e) => { if (e.target.matches('.filter-btn[data-status]')) { document.querySelectorAll('#statusFilterContainer .filter-btn').forEach(btn => btn.classList.remove('active')); e.target.classList.add('active'); filters.status = e.target.dataset.status; fetchData(); } });
        cityFilterContainer.addEventListener('click', (e) => { if (e.target.matches('.filter-btn[data-city]')) { document.querySelectorAll('#cityFilterContainer .filter-btn').forEach(btn => btn.classList.remove('active')); e.target.classList.add('active'); filters.city = e.target.dataset.city; updateDisplay(); } });
        startDateInput.addEventListener('change', () => {
            const dataDe = startDateInput.value;

            if (dataDe) {
                endDateInput.min = dataDe;
                if (endDateInput.value && endDateInput.value < dataDe) {
                    endDateInput.value = '';
                }
                endDateInput.showPicker();
            } else {
                endDateInput.min = '';
            }
            fetchData();
        });
        endDateInput.addEventListener('change', () => { filters.endDate = endDateInput.value; fetchData(); });
        searchInput.addEventListener('input', updateDisplay);
        clearFiltersBtn.addEventListener('click', () => { searchInput.value = ''; startDateInput.value = ''; endDateInput.value = ''; filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' }; document.querySelectorAll('.filter-btn, .action-btn').forEach(btn => btn.classList.remove('active')); document.querySelector('.action-btn[data-type="manutencao"]').classList.add('active'); document.querySelector('.filter-btn[data-status="todos"]').classList.add('active'); const allCitiesBtn = document.querySelector('.filter-btn[data-city="todos"]'); if (allCitiesBtn) allCitiesBtn.classList.add('active'); initialLoad(); });
    }

    // --- Lógica de Modais ---
    window.openValidarModal = (id) => {
        currentItem = findOcorrenciaById(id, 'manutencao');
        if (!currentItem) return;
        document.getElementById('validarModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('validarOcorrenciaText').textContent = currentItem.ocorrencia_reparo;
        document.getElementById('validarReparoText').textContent = currentItem.reparo_finalizado;
        openModal('validarModal');
    }

    window.openRetornarModal = () => {
        if (!currentItem) return;
        closeModal('validarModal');
        document.getElementById('retornarModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('retornarMotivoTextarea').value = '';
        document.getElementById('retornarMotivoError').style.display = 'none';
        openModal('retornarModal');
    }

    window.handleValidar = async () => {
        if (!currentItem) return;
        const payload = { action: 'validar_reparo', id: currentItem.id };
        await executeApiUpdate(payload, 'Ocorrência validada com sucesso!', 'validarModal', 'btnConfirmarValidacao', 'validarMessage', 'validarButtons');
    }

    window.handleRetornar = async () => {
        if (!currentItem) return;
        const motivo = document.getElementById('retornarMotivoTextarea').value.trim();
        const errorEl = document.getElementById('retornarMotivoError');

        if (motivo === '') {
            errorEl.textContent = 'O motivo do retorno é obrigatório.';
            errorEl.style.display = 'block';
            return;
        }
        errorEl.style.display = 'none';

        const payload = { action: 'retornar_manutencao', id: currentItem.id, nova_ocorrencia: motivo };
        await executeApiUpdate(payload, 'Ocorrência retornada para pendente com sucesso!', 'retornarModal', 'btnConfirmarRetorno', 'retornarMessage', 'retornarButtons');
    }

    window.openConcluirModal = (id, origem) => { currentItem = findOcorrenciaById(id, origem); if (!currentItem) return; reparoRealizadoTextarea.value = ''; reparoRealizadoError.style.display = 'none'; document.getElementById('concluirModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`; document.getElementById('concluirOcorrenciaText').textContent = currentItem.ocorrencia_reparo; openModal('concluirModal'); };
    function validateConclusionForm() { if (reparoRealizadoTextarea.value.trim() === '') { reparoRealizadoError.textContent = 'A descrição da solução é obrigatória.'; reparoRealizadoError.style.display = 'block'; return false; } reparoRealizadoError.style.display = 'none'; return true; }
    window.handleConclusion = () => { if (validateConclusionForm()) { openConfirmationModal('concluir'); } };
    async function saveConclusion() { const reparoFinalizado = reparoRealizadoTextarea.value.trim(); const payload = { action: 'concluir_ocorrencia_processamento', id: currentItem.id, reparo_finalizado: reparoFinalizado }; await executeApiUpdate(payload, 'Concluído com sucesso!', 'concluirModal'); }
    function findOcorrenciaById(id, origem) { for (const city in allData.ocorrencias) { const found = allData.ocorrencias[city].find(item => item.id == id && (item.origem == origem || filters.status === 'validacao')); if (found) return found; } return null; }
    window.openEditModal = (id, origem) => { currentItem = findOcorrenciaById(id, origem); if (!currentItem) return; const reparoGroup = document.getElementById('editReparoGroup'); const reparoTextarea = document.getElementById('editReparoTextarea'); document.getElementById('editModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`; document.getElementById('editOcorrenciaTextarea').value = currentItem.ocorrencia_reparo; if (currentItem.status === 'concluido') { reparoTextarea.value = currentItem.reparo_finalizado || ''; reparoGroup.classList.remove('hidden'); } else { reparoTextarea.value = ''; reparoGroup.classList.add('hidden'); } openModal('editModal'); };
    window.saveOcorrenciaEdit = async () => { const newProblemText = document.getElementById('editOcorrenciaTextarea').value.trim(); if (!newProblemText) { alert('A descrição do problema não pode ser vazia.'); return; } const payload = { action: 'edit_ocorrencia', id: currentItem.id, origem: 'ocorrencia_processamento', ocorrencia_reparo: newProblemText }; if (currentItem.status === 'concluido') { payload.reparo_finalizado = document.getElementById('editReparoTextarea').value.trim(); } await executeApiUpdate(payload, 'Atualizado com sucesso!', 'editModal', 'saveEditBtn', 'editMessage', 'editButtons'); };
    window.openConfirmationModal = (type, id, origem) => { if (id) currentItem = findOcorrenciaById(id, origem); if (!currentItem) return; const titleEl = document.getElementById('confirmationModalTitle'); const textEl = document.getElementById('confirmationModalText'); const actionButton = document.getElementById('confirmActionButton'); const actionText = document.getElementById('confirmActionText'); actionButton.className = 'modal-btn btn-primary'; if (type === 'concluir') { titleEl.textContent = 'Confirmar Conclusão'; textEl.textContent = 'Deseja marcar esta ocorrência como concluída?'; actionText.textContent = "Sim, Concluir"; actionButton.onclick = () => saveConclusion(); } else if (type === 'cancelar') { titleEl.textContent = 'Confirmar Cancelamento'; textEl.textContent = 'Tem certeza que deseja cancelar esta ocorrência?'; actionText.textContent = "Sim, Cancelar"; actionButton.classList.add('cancel'); actionButton.onclick = () => executeStatusChange(currentItem.id, 'cancelado', 'ocorrencia_processamento'); } openModal('confirmationModal'); };
    async function executeStatusChange(id, status, origem) { const payload = { action: 'update_status', id: id, status: status, origem: origem }; await executeApiUpdate(payload, 'Status atualizado com sucesso!'); }

    async function executeApiUpdate(payload, successMessage, modalToClose, btnId, msgId, btnsId) {
        const button = btnId ? document.getElementById(btnId) : document.getElementById('confirmActionButton');
        if (!button) { console.error("Botão não encontrado:", btnId); return; }

        const spinner = button.querySelector('.spinner');
        const messageEl = msgId ? document.getElementById(msgId) : document.getElementById('confirmationMessage');
        const buttonsDiv = btnsId ? document.getElementById(btnsId) : document.getElementById('confirmationButtons');

        button.disabled = true;
        if (spinner) spinner.classList.add('is-active');
        if (messageEl) messageEl.classList.add('hidden');
        if (buttonsDiv) buttonsDiv.querySelectorAll('.modal-btn').forEach(b => b.disabled = true);


        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Erro na comunicação com o servidor.');

            if (buttonsDiv) buttonsDiv.style.display = 'none';
            if (messageEl) {
                messageEl.textContent = successMessage;
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
            }

            setTimeout(() => {
                if (modalToClose) closeModal(modalToClose);
                closeModal('confirmationModal');
                initialLoad();
            }, 2000);

        } catch (error) {
            if (messageEl) {
                messageEl.textContent = `Erro: ${error.message}`;
                messageEl.className = 'message error';
                messageEl.classList.remove('hidden');
            }
            if (button) button.disabled = false;
            if (spinner) spinner.classList.remove('is-active');
            if (buttonsDiv) buttonsDiv.querySelectorAll('.modal-btn').forEach(b => b.disabled = false);
        }
    }

    function updateCityFilters() { cityFilterContainer.innerHTML = ''; const cities = allData.cidades || []; if (cities.length > 0) { const allButton = document.createElement('button'); allButton.className = 'filter-btn active todos'; allButton.dataset.city = 'todos'; allButton.textContent = 'Todas'; cityFilterContainer.appendChild(allButton); cities.sort().forEach(cidade => { const button = document.createElement('button'); button.className = 'filter-btn city'; button.dataset.city = cidade; button.textContent = cidade; cityFilterContainer.appendChild(button); }); } }

    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');

    window.closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('is-active');

        const messageEl = modal.querySelector('.message');
        const buttonsDiv = modal.querySelector('.modal-footer-buttons');

        if (messageEl) { messageEl.classList.add('hidden'); }
        if (buttonsDiv) {
            buttonsDiv.style.display = 'flex';
            buttonsDiv.querySelectorAll('.modal-btn').forEach(b => b.disabled = false);
        }
        const spinner = modal.querySelector('.spinner.is-active');
        if (spinner) { spinner.classList.remove('is-active'); }
    };

    // --- Inicialização da Página ---
    initializeFilters();
    initialLoad(); // Usa a nova função de carga inicial inteligente
    window.addEventListener('resize', adjustSearchSpacer);
});