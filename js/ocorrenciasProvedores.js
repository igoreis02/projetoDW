document.addEventListener('DOMContentLoaded', function () {
    // --- Referências aos Elementos do DOM ---
    const typeFilterContainer = document.getElementById('typeFilterContainer');
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    const cityFilterContainer = document.getElementById('cityFilterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const pageLoader = document.getElementById('pageLoader');

    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const searchSpacer = document.querySelector('.search-spacer');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    const btnProvedor = document.getElementById('btnProvedor');
    const btnInLoco = document.getElementById('btnInLoco');
    const btnSemIntervencao = document.getElementById('btnSemIntervencao');
    const btnTecnicoDw = document.getElementById('btnTecnicoDw');
    const reparoRealizadoContainer = document.getElementById('reparoRealizadoContainer');
    const problemaTecnicoDwContainer = document.getElementById('problemaTecnicoDwContainer');
    const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');
    const problemaTecnicoDwTextarea = document.getElementById('problemaTecnicoDwTextarea');
    const reparoRealizadoError = document.getElementById('reparoRealizadoError');
    const problemaTecnicoDwError = document.getElementById('problemaTecnicoDwError');
    const concluirModalError = document.getElementById('concluirModalError');

    const btnVoltarAoTopo = document.getElementById("btnVoltarAoTopo");

    // --- Variáveis de Estado ---
    let filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' };
    let allData = null, currentItem = null;
    let conclusionType = null;

    let currentChecksum = null;
    let updateTimeoutId = null;
    const BASE_INTERVAL = 15000; // 15 segundos
    const MAX_INTERVAL = 120000; // 2 minutos
    let currentInterval = BASE_INTERVAL;

    // --- Funções Principais ---

    async function fetchData(isUpdateCheck = false) {
        if (!isUpdateCheck) {
            pageLoader.style.display = 'flex'; 
            ocorrenciasContainer.innerHTML = '';
            cityFilterContainer.innerHTML = '';
        }
        const params = new URLSearchParams({ tipo: filters.type, status: filters.status, data_inicio: filters.startDate, data_fim: filters.endDate });
        try {
            const response = await fetch(`API/get_ocorrencias_provedores.php?${params.toString()}`);
            const result = await response.json();
            if (isUpdateCheck) {
                handleUpdateCheck(result);
                return;
            }

            pageLoader.style.display = 'none';

            if (result.success) {
                currentChecksum = result.checksum;
                allData = result.data;
                renderAllOcorrencias(allData);
                updateCityFilters();
                updateDisplay();
                adjustSearchSpacer(); // Ajusta o espaçador após renderizar
            } else {
                ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
            }
        } catch (error) {
            console.error('Erro ao buscar dados:', error);
            if (!isUpdateCheck) { 
                pageLoader.style.display = 'none'; 
                ocorrenciasContainer.innerHTML = `<p class="message error">Ocorreu um erro ao carregar os dados. Verifique a consola.</p>`; }
        }
    }

    async function scheduleNextCheck() {
        if (updateTimeoutId) {
            clearTimeout(updateTimeoutId);
        }
        try {
            // Usa o contexto correto para esta página
            const checkResponse = await fetch('API/check_updates.php?context=ocorrencias_provedores');
            const checkResult = await checkResponse.json();

            if (checkResult.success && checkResult.checksum !== currentChecksum) {
                console.log('Novas atualizações de ocorrências de provedor detectadas. Recarregando...');
                await fetchData();
                currentInterval = BASE_INTERVAL;
                console.log('Intervalo de verificação de provedores resetado.');
            } else {
                currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
                console.log(`Nenhuma atualização. Próxima verificação de provedores em ${currentInterval / 1000}s.`);
            }
        } catch (error) {
            console.error('Erro no ciclo de verificação de atualizações:', error);
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
        } finally {
            updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
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
            ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">Nenhuma ocorrência encontrada.</p>`;
        }
    }

    function createOcorrenciaHTML(item) {
        const statusClass = item.status || 'pendente';
        const inicioReparoFormatted = item.inicio_reparo ? new Date(item.inicio_reparo).toLocaleString('pt-BR') : 'N/A';
        const fimReparoFormatted = item.fim_reparo ? new Date(item.fim_reparo).toLocaleString('pt-BR') : 'N/A';
        let atribuidoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Reportado por</strong> <span>${item.atribuido_por}</span></div>` : '';
        let statusHTML = `<span class="status-tag ${statusClass}">${statusClass}</span>`;
        let completionDetails = '';
        if (statusClass === 'concluido') {
            if (item.provedor == 1) {
                completionDetails = '<span class="completion-tag">Provedor</span>';
            } else if (item.inLoco == 1) {
                completionDetails = '<span class="completion-tag">Técnico Provedor</span>';
            } else if (item.sem_intervencao == 1) {
                completionDetails = '<span class="completion-tag">Sem Intervenção Técnica</span>';
            } else if (item.tecnico_dw == 1) {
                completionDetails = '<span class="completion-tag">Reparo Deltaway</span>';
            }
            statusHTML += completionDetails;
        }
        let reparoFinalizadoHTML = '';
        if (item.status === 'concluido' && (item.reparo_finalizado || item.des_reparo)) {
            reparoFinalizadoHTML = `<div class="detail-item reparo-info"><strong>Reparo Realizado</strong> <span>${item.reparo_finalizado || item.des_reparo}</span></div>`;
        }
        const detailsHTML = `
            <div class="detail-item"><strong>Provedor</strong> <span class="searchable">${item.nome_prov || 'N/A'}</span></div>
            <div class="detail-item"><strong>Problema</strong> <span class="searchable">${item.ocorrencia_reparo || 'N/A'}</span></div>
            ${reparoFinalizadoHTML}
            <div class="detail-item"><strong>Início Ocorrência</strong> <span>${inicioReparoFormatted}</span></div>
            ${statusClass === 'concluido' ? `<div class="detail-item"><strong>Fim Ocorrência</strong> <span>${fimReparoFormatted}</span></div>` : ''}
            ${atribuidoPorHTML}
            <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
            <div class="detail-item"><strong>Local</strong> <span class="searchable">${item.local_completo || 'N/A'}</span></div>
        `;
        let actionsContent = '';
        if (statusClass === 'pendente') {
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
                    searchableSpans.forEach(span => {
                        searchableText += (span.textContent || span.innerText) + ' ';
                    });
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
            const buttonWidth = clearFiltersBtn.offsetWidth;
            searchSpacer.style.width = `${buttonWidth}px`;
        }
    }


    function initializeFilters() {
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
        clearFiltersBtn.addEventListener('click', () => {
            searchInput.value = '';
            startDateInput.value = '';
            endDateInput.value = '';
            filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' };
            document.querySelectorAll('#statusFilterContainer .filter-btn, #cityFilterContainer .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector('.filter-btn[data-status="todos"]').classList.add('active');
            const allCitiesBtn = document.querySelector('.filter-btn[data-city="todos"]');
            if (allCitiesBtn) allCitiesBtn.classList.add('active');
            fetchData();
        });
    }

    function setupConclusionModalListeners() {
        const optionButtons = [btnInLoco, btnSemIntervencao, btnTecnicoDw, btnProvedor];

        optionButtons.forEach(btn => {
            if (btn) { // Verifica se o botão existe antes de adicionar o listener

                btn.addEventListener('click', () => {
                    if (concluirModalError) {
                        concluirModalError.style.display = 'none'; // Oculta a mensagem de erro
                    }
                    optionButtons.forEach(b => {
                        if (b) b.classList.remove('active')
                    });
                    btn.classList.add('active');

                    conclusionType = btn.id.replace('btn', '').toLowerCase();

                    const showReparo = conclusionType === 'inloco' || conclusionType === 'semintervencao' || conclusionType === 'provedor';

                    reparoRealizadoContainer.classList.toggle('hidden', !showReparo);
                    problemaTecnicoDwContainer.classList.toggle('hidden', showReparo);
                });
            }
        });
    }


    window.openConcluirModal = (id, origem) => {
        currentItem = findOcorrenciaById(id, origem);
        if (!currentItem) return;
        [btnInLoco, btnSemIntervencao, btnTecnicoDw, btnProvedor].forEach(b => b.classList.remove('active'));
        reparoRealizadoContainer.classList.add('hidden');
        problemaTecnicoDwContainer.classList.add('hidden');
        reparoRealizadoTextarea.value = '';
        problemaTecnicoDwTextarea.value = '';
        reparoRealizadoError.style.display = 'none';
        problemaTecnicoDwError.style.display = 'none';
        concluirModalError.style.display = 'none';
        conclusionType = null;
        document.getElementById('concluirModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('concluirOcorrenciaText').textContent = currentItem.ocorrencia_reparo;
        openModal('concluirModal');
    }

    function validateConclusionForm() {
        let isValid = true;
        reparoRealizadoError.style.display = 'none';
        problemaTecnicoDwError.style.display = 'none';

        if (!conclusionType) {
            if (concluirModalError) {
                concluirModalError.textContent = 'É obrigatório selecionar uma opção de conclusão.';
                concluirModalError.style.display = 'block';
            }
            isValid = false;
            // Não retorna aqui para permitir que outras validações de texto também apareçam, se necessário
        }

        if (conclusionType === 'inloco' || conclusionType === 'semintervencao' || conclusionType === 'provedor') {
            if (reparoRealizadoTextarea.value.trim() === '') {
                reparoRealizadoError.textContent = 'A descrição do reparo é obrigatória.';
                reparoRealizadoError.style.display = 'block';
                isValid = false;
            }
        } else if (conclusionType === 'tecnicodw') {
            if (problemaTecnicoDwTextarea.value.trim() === '') {
                problemaTecnicoDwError.textContent = 'A descrição do problema para o técnico é obrigatória.';
                problemaTecnicoDwError.style.display = 'block';
                isValid = false;
            }
        }
        return isValid;
    }

    window.handleConclusion = () => {
        if (validateConclusionForm()) {
            openConfirmationModal('concluir');
        }
    }

    async function saveConclusion() {
        let reparoFinalizado = '';
        let inLoco = 0, semIntervencao = 0, tecnicoDw = 0, provedor = 0;

        if (!validateConclusionForm()) {
            return; // Para a execução se o formulário for inválido
        }

        if (conclusionType === 'inloco' || conclusionType === 'semintervencao' || conclusionType === 'provedor') {
            reparoFinalizado = reparoRealizadoTextarea.value.trim();
            if (conclusionType === 'inloco') inLoco = 1;
            else if (conclusionType === 'semintervencao') semIntervencao = 1;
            else if (conclusionType === 'provedor') provedor = 1;

        } else if (conclusionType === 'tecnicodw') {
            reparoFinalizado = problemaTecnicoDwTextarea.value.trim();
            tecnicoDw = 1;
        }

        const confirmButton = document.getElementById('confirmActionButton');
        const spinner = document.getElementById('confirmSpinner');
        const messageEl = document.getElementById('confirmationMessage');

        spinner.classList.add('is-active');
        confirmButton.disabled = true;

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'concluir_ocorrencia_provedor',
                    id: currentItem.id,
                    origem: currentItem.origem,
                    reparo_finalizado: reparoFinalizado,
                    inLoco: inLoco,
                    sem_intervencao: semIntervencao,
                    tecnico_dw: tecnicoDw,
                    provedor: provedor
                })
            });
            const result = await response.json();
            if (result.success) {
                document.getElementById('confirmationButtons').style.display = 'none';
                messageEl.textContent = 'Ocorrência concluída com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
                setTimeout(() => { closeModal('confirmationModal'); closeModal('concluirModal'); fetchData(); }, 2000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            messageEl.textContent = `Erro: ${error.message}`;
            messageEl.className = 'message error';
            messageEl.classList.remove('hidden');
            confirmButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    function findOcorrenciaById(id, origem) {
        for (const city in allData.ocorrencias) {
            const found = allData.ocorrencias[city].find(item => item.id == id && item.origem == origem);
            if (found) return found;
        }
        return null;
    }

    window.openEditModal = (id, origem) => {
        currentItem = findOcorrenciaById(id, origem);
        if (!currentItem) return;
        const reparoGroup = document.getElementById('editReparoGroup');
        const reparoTextarea = document.getElementById('editReparoTextarea');
        document.getElementById('editModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('editOcorrenciaTextarea').value = currentItem.ocorrencia_reparo;
        if (currentItem.status === 'concluido') {
            reparoTextarea.value = currentItem.reparo_finalizado || currentItem.des_reparo || '';
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
        const dataToSend = { action: 'edit_ocorrencia', id: currentItem.id, origem: currentItem.origem, ocorrencia_reparo: newProblemText };
        if (currentItem.status === 'concluido') {
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
                setTimeout(() => { closeModal('editModal'); fetchData(); }, 2000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            messageEl.textContent = `Erro ao salvar: ${error.message}`;
            messageEl.className = 'message error';
            messageEl.classList.remove('hidden');
            saveButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    window.openConfirmationModal = (type, id, origem) => {
        if (id) currentItem = findOcorrenciaById(id, origem);
        if (!currentItem) return;
        const titleEl = document.getElementById('confirmationModalTitle');
        const textEl = document.getElementById('confirmationModalText');
        const actionButton = document.getElementById('confirmActionButton');
        const actionText = document.getElementById('confirmActionText');
        actionButton.className = 'modal-btn btn-primary';
        if (type === 'concluir') {
            titleEl.textContent = 'Confirmar Conclusão';
            textEl.textContent = 'Deseja marcar esta ocorrência como concluída?';
            actionText.textContent = "Sim, Concluir";
            actionButton.onclick = () => saveConclusion();
        } else if (type === 'cancelar') {
            titleEl.textContent = 'Confirmar Cancelamento';
            textEl.textContent = 'Tem certeza que deseja cancelar esta ocorrência?';
            actionText.textContent = "Sim, Cancelar";
            actionButton.classList.add('cancel');
            actionButton.onclick = () => executeStatusChange(currentItem.id, 'cancelado', currentItem.origem);
        }
        openModal('confirmationModal');
    }

    async function executeStatusChange(id, status, origem) {
        const confirmButton = document.getElementById('confirmActionButton');
        const spinner = document.getElementById('confirmSpinner');
        const messageEl = document.getElementById('confirmationMessage');
        spinner.classList.add('is-active');
        confirmButton.disabled = true;
        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', id: id, status: status, origem: origem })
            });
            const result = await response.json();
            if (result.success) {
                document.getElementById('confirmationButtons').style.display = 'none';
                messageEl.textContent = 'Status atualizado com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
                setTimeout(() => { closeModal('confirmationModal'); fetchData(); }, 2000);
            } else { throw new Error(result.message || 'Erro desconhecido.'); }
        } catch (error) {
            messageEl.textContent = `Erro: ${error.message}`;
            messageEl.className = 'message error';
            messageEl.classList.remove('hidden');
            confirmButton.disabled = false;
            spinner.classList.remove('is-active');
        }
    }

    function updateCityFilters() {
        cityFilterContainer.innerHTML = '';
        const cities = allData.cidades || [];
        if (cities.length > 0) {
            const allButton = document.createElement('button');
            allButton.className = 'filter-btn active todos';
            allButton.dataset.city = 'todos';
            allButton.textContent = 'Todas';
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

    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');
    window.closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove('is-active');
        const resetModalState = (modalElement) => {
            const messageEl = modalElement.querySelector('#editMessage, #confirmationMessage');
            const buttonsDiv = modalElement.querySelector('#editButtons, #confirmationButtons');
            const actionButton = modalElement.querySelector('#saveEditBtn, #confirmActionButton');
            const spinner = modalElement.querySelector('.spinner');
            if (messageEl) { messageEl.classList.add('hidden'); messageEl.textContent = ''; }
            if (buttonsDiv) { buttonsDiv.style.display = 'flex'; }
            if (actionButton) { actionButton.disabled = false; }
            if (spinner && spinner.classList.contains('is-active')) { spinner.classList.remove('is-active'); }
        };
        if (modalId === 'editModal' || modalId === 'confirmationModal') {
            resetModalState(modal);
        }
    };

    // --- Inicialização da Página ---
    initializeFilters();
    setupConclusionModalListeners();
    fetchData().then(() => {
        console.log('Carga inicial de ocorrências de provedores completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });

    reparoRealizadoTextarea.addEventListener('input', () => {
        if (reparoRealizadoTextarea.value.trim() !== '') {
            reparoRealizadoError.style.display = 'none';
        }
    });

    problemaTecnicoDwTextarea.addEventListener('input', () => {
        if (problemaTecnicoDwTextarea.value.trim() !== '') {
            problemaTecnicoDwError.style.display = 'none';
        }
    });
    // Ajusta o espaçador também em caso de redimensionamento da janela
    window.addEventListener('resize', adjustSearchSpacer);

    // Adiciona um "ouvinte" para o evento de rolagem da página
    window.onscroll = function () {
        controlarVisibilidadeBotao();
    };

    function controlarVisibilidadeBotao() {
        // Se a página for rolada mais de 20px para baixo...
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            // ...o botão aparece.
            btnVoltarAoTopo.style.display = "block";
        } else {
            // ...senão, ele desaparece.
            btnVoltarAoTopo.style.display = "none";
        }
    }

    btnVoltarAoTopo.addEventListener('click', function () {
        // Manda a página de volta para o topo de forma suave
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});