document.addEventListener('DOMContentLoaded', function () {
    // --- Referências aos Elementos do DOM ---
    const typeFilterContainer = document.getElementById('typeFilterContainer');
    const statusFilterContainer = document.getElementById('statusFilterContainer');
    const cityFilterContainer = document.getElementById('cityFilterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const searchSpacer = document.querySelector('.search-spacer');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const reparoRealizadoTextarea = document.getElementById('reparoRealizadoTextarea');
    const reparoRealizadoError = document.getElementById('reparoRealizadoError');
    const pageLoader = document.getElementById('pageLoader');

    const etiquetaModal = document.getElementById('etiquetaModal');
    const etiquetaSimBtn = document.getElementById('etiquetaSimBtn');
    const etiquetaNaoBtn = document.getElementById('etiquetaNaoBtn');
    const etiquetaDataGroup = document.getElementById('etiquetaDataGroup');
    const etiquetaDataInput = document.getElementById('etiquetaDataInput');
    const saveEtiquetaBtn = document.getElementById('saveEtiquetaBtn');

    const btnVoltarAoTopo = document.getElementById("btnVoltarAoTopo");


    // --- Variáveis de Estado ---
    let filters = { type: 'manutencao', status: 'todos', startDate: '', endDate: '', city: 'todos' };
    let allData = null, currentItem = null;
    let groupedDataForEditing = {};

    let currentChecksum = null;
    let updateTimeoutId = null;
    const BASE_INTERVAL = 15000; // 15 segundos
    const MAX_INTERVAL = 120000; // 2 minutos
    let currentInterval = BASE_INTERVAL;

    // --- Inicialização ---

    async function scheduleNextCheck() {
        if (updateTimeoutId) {
            clearTimeout(updateTimeoutId);
        }
        try {
            // Usa o contexto correto para esta página (baseado no seu check_updates.php)
            const checkResponse = await fetch('API/check_updates.php?context=ocorrencias_processamento');
            const checkResult = await checkResponse.json();

            if (checkResult.success && checkResult.checksum !== currentChecksum) {
                console.log('Novas atualizações de processamento detectadas. Recarregando...');
                await fetchData(true); // Recarrega os dados sem piscar a tela
                currentInterval = BASE_INTERVAL;
                console.log('Intervalo de verificação de processamento resetado.');
            } else {
                currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
                console.log(`Nenhuma atualização. Próxima verificação de processamento em ${currentInterval / 1000}s.`);
            }
        } catch (error) {
            console.error('Erro no ciclo de verificação de atualizações:', error);
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
        } finally {
            updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
        }
    }


    async function initialLoad() {
        pageLoader.style.display = 'flex';

        // 1. Busca TODAS as ocorrências na carga inicial para análise.
        const paramsCheck = new URLSearchParams({ status: 'todos' });
        try {
            const response = await fetch(`API/get_ocorrencias_processamento.php?${paramsCheck.toString()}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Falha ao carregar dados iniciais.');
            }

            // Armazena os dados e o checksum para futuras atualizações
            currentChecksum = result.checksum;
            allData = result.data;

            // 2. Analisa os dados recebidos para tomar decisões
            const allItems = Object.values(allData.ocorrencias || {}).flat();
            const hasValidacao = allItems.some(item => item.status === 'validacao');
            const hasPendentes = allItems.some(item => item.status === 'pendente');

            // 3. LÓGICA CORRIGIDA: Decide o filtro padrão com a prioridade correta
            if (hasValidacao) {
                filters.status = 'validacao'; // Prioridade 1: Validação
            } else if (hasPendentes) {
                filters.status = 'pendente';  // Prioridade 2: Pendente
            } else {
                filters.status = 'todos';     // Prioridade 3 (Fallback): Todos
            }

            // 4. Controla a visibilidade do botão 'Validação'
            const btnValidacao = document.getElementById('btnValidacao');
            if (hasValidacao) {
                btnValidacao.style.display = '';
            } else {
                btnValidacao.style.display = 'none';
            }

            // 5. Atualiza a classe 'active' nos botões de filtro de status
            document.querySelectorAll('#statusFilterContainer .filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === filters.status);
            });

            // 6. Renderiza os dados que já foram buscados
            renderAllOcorrencias(allData);
            //updateCityFilters();
            updateDisplay();
            adjustSearchSpacer();

        } catch (error) {
            console.error('Erro na carga inicial:', error);
            ocorrenciasContainer.innerHTML = `<p class="message error">Ocorreu um erro na carga inicial.</p>`;
        } finally {
            pageLoader.style.display = 'none';
        }
    }

    async function fetchData(isUpdate = false) {
        if (!isUpdate) { // Só mostra o "carregando" na carga inicial ou em filtros manuais
            pageLoader.style.display = 'flex';
            ocorrenciasContainer.innerHTML = '';
            cityFilterContainer.innerHTML = '';
        }

        const params = new URLSearchParams({ tipo: filters.type, status: filters.status, data_inicio: filters.startDate, data_fim: filters.endDate });
        try {
            const response = await fetch(`API/get_ocorrencias_processamento.php?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                currentChecksum = result.checksum;
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

                const itemsToRender = [];
                const groupedItems = {};

                (data.ocorrencias[cidade] || []).forEach(item => {
                    if (item.status === 'concluido') {
                        const key = `${item.nome_equip}|${item.referencia_equip}`;
                        if (!groupedItems[key]) {
                            groupedItems[key] = { ...item, isGrouped: true, ocorrencias_detalhadas: [] };
                        }
                        groupedItems[key].ocorrencias_detalhadas.push(item);
                    } else {
                        itemsToRender.push({ ...item, isGrouped: false, ocorrencias_detalhadas: [item] });
                    }
                });

                Object.values(groupedItems).forEach(group => {
                    if (group.ocorrencias_detalhadas.length === 1) {
                        group.isGrouped = false;
                    }
                    itemsToRender.push(group);
                });

                let cityGridHTML = itemsToRender.map(item => createOcorrenciaHTML(item)).join('');

                cityGroup.innerHTML = `<h2 class="city-group-title">${cidade}</h2><div class="city-ocorrencias-grid">${cityGridHTML}</div>`;
                ocorrenciasContainer.appendChild(cityGroup);
            }
        } else {
            ocorrenciasContainer.innerHTML = `<p style="text-align: center; padding: 2rem;">Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
        }
    }

    function createOcorrenciaHTML(item) {
        const firstItem = item.ocorrencias_detalhadas[0];
        const statusClass = (firstItem.status || 'pendente').toLowerCase();
        let detailsHTML = '';
        let actionsContent = '';

        if (item.isGrouped) {
            const uniqueId = `list-${firstItem.id}-${Math.random().toString(36).substr(2, 9)}`;
            const groupId = `group-${firstItem.id_equipamento || firstItem.id}`; // Cria uma chave única para o grupo

            // Armazena os dados detalhados na variável global
            groupedDataForEditing[groupId] = item.ocorrencias_detalhadas;

            let ocorrenciasListHTML = item.ocorrencias_detalhadas.map((ocor, index) => {
                const dataOcorrencia = ocor.inicio_reparo ? new Date(ocor.inicio_reparo).toLocaleDateString('pt-BR') : 'N/A';
                const dataConclusao = ocor.fim_reparo ? new Date(ocor.fim_reparo).toLocaleDateString('pt-BR') : 'N/A';

                return `
                <li style="padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; list-style: none;">
                    <div class="detail-item"><strong>${index + 1}. Ocorrência:</strong> <span class="status-tag concluido">${ocor.ocorrencia_reparo}</span></div>
                    <div style="margin-left: 25px;">
                        <div class="detail-item"><small><strong>Data Ocorrência:</strong> ${dataOcorrencia}</small></div>
                        <div class="detail-item"><small><strong>Solicitado por:</strong> ${ocor.atribuido_por || 'N/A'}</small></div>
                    </div>
                    <div class="detail-item reparo-info"><strong>Reparo Realizado:</strong> <span class="status-tag concluido">${ocor.reparo_finalizado || 'N/A'}</span></div>
                    <div style="margin-left: 25px;">
                        <div class="detail-item"><small><strong>Data Conclusão:</strong> ${dataConclusao}</small></div>
                        <div class="detail-item"><small><strong>Concluído por:</strong> ${ocor.concluido_por || 'N/A'}</small></div>
                    </div>
                </li>
            `;
            }).join('');

            let toggleButtonHTML = '';
            if (item.ocorrencias_detalhadas.length > 2) {
                toggleButtonHTML = `<button class="toggle-ocorrencias-btn" onclick="toggleOcorrencias('${uniqueId}', this)">Todas ocorrências</button>`;
            }

            detailsHTML = `
            <div id="${uniqueId}" class="ocorrencia-list-container collapsed">
                <ol style="color: black; padding-left: 0;">${ocorrenciasListHTML}</ol>
            </div>
            ${toggleButtonHTML}
            <div class="detail-item" style="margin-top: 1rem;"><strong>Status</strong> <span class="status-tag ${statusClass}">${firstItem.status}</span></div>
            <div class="detail-item"><strong>Local</strong> <span class="searchable">${firstItem.local_completo || 'N/A'}</span></div>
        `;

            actionsContent = `<button class="item-btn edit-btn" onclick="openGroupedEditModal('${groupId}')">Editar</button>`;

        } else {
            // Lógica para cards individuais 
            const inicioReparoFormatted = firstItem.inicio_reparo ? new Date(firstItem.inicio_reparo).toLocaleString('pt-BR') : 'N/A';
            const fimReparoFormatted = firstItem.fim_reparo ? new Date(firstItem.fim_reparo).toLocaleString('pt-BR') : 'N/A';
            let atribuidoPorHTML = firstItem.atribuido_por ? `<div class="detail-item"><strong>Reportado por</strong> <span>${firstItem.atribuido_por}</span></div>` : '';
            let reparoFinalizadoHTML = (statusClass === 'concluido' || statusClass === 'validacao') && firstItem.reparo_finalizado ? `<div class="detail-item reparo-info "><strong>Reparo realizado</strong> <span class="status-tag concluido">${firstItem.reparo_finalizado}</span></div>` : '';

            detailsHTML = `
            <div class="detail-item"><strong>Ocorrência</strong> <span class="searchable  status-tag concluido">${firstItem.ocorrencia_reparo || 'N/A'}</span></div>
            <div class="detail-item"><strong>Início Ocorrência</strong> <span>${inicioReparoFormatted}</span></div>
            ${atribuidoPorHTML}
            ${reparoFinalizadoHTML}
            ${statusClass === 'concluido' ? `<div class="detail-item"><strong>Fim Ocorrência</strong> <span>${fimReparoFormatted}</span></div>` : ''}
            ${statusClass === 'concluido' && firstItem.concluido_por ? `<div class="detail-item"><strong>Concluído por</strong> <span>${firstItem.concluido_por}</span></div>` : ''}
            <div class="detail-item"><strong>Status</strong> <span class="status-tag ${statusClass}">${firstItem.status}</span></div>
            <div class="detail-item"><strong>Local</strong> <span class="searchable">${firstItem.local_completo || 'N/A'}</span></div>
        `;

            if (statusClass === 'validacao') {
                actionsContent = `<button class="item-btn validar-btn" onclick="openValidarModal(${firstItem.id})">Validar</button>`;
            } else if (statusClass === 'pendente') {
                actionsContent = `<button class="item-btn concluir-btn" onclick="openConcluirModal(${firstItem.id}, '${firstItem.origem}')">Concluir</button><button class="item-btn edit-btn" onclick="openEditModal(${firstItem.id}, '${firstItem.origem}')">Editar</button><button class="item-btn cancel-btn" onclick="openConfirmationModal('cancelar', ${firstItem.id}, '${firstItem.origem}')">Cancelar</button>`;
            } else if (statusClass === 'concluido') {
                actionsContent = `<button class="item-btn edit-btn" onclick="openEditModal(${firstItem.id}, '${firstItem.origem}')">Editar</button>`;
            }
        }

        const actionsHTML = actionsContent ? `<div class="item-actions">${actionsContent}</div>` : `<div class="item-actions" style="min-height: 40px;"></div>`;

        return `<div class="ocorrencia-item status-${statusClass}" data-id="${firstItem.id}" data-origem="${firstItem.origem}">
                <div class="ocorrencia-header"><h3><span class="searchable">${firstItem.nome_equip}</span> - <span class="searchable">${firstItem.referencia_equip}</span></h3></div>
                <div class="ocorrencia-details">${detailsHTML}</div>
                ${actionsHTML}
            </div>`;
    }

    function updateDisplay() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const currentCity = filters.city;
        const currentStatus = filters.status;

        const visibleCities = new Set(); // Usa um Set para evitar cidades duplicadas

        document.querySelectorAll('.city-group').forEach(group => {
            let hasVisibleItemsInGroup = false;
            
            group.querySelectorAll('.ocorrencia-item').forEach(item => {
                const isSearchMatch = /* ... (toda a sua lógica de busca continua a mesma) ... */
                (item.querySelector('.ocorrencia-header h3')?.textContent || '').toLowerCase().includes(searchTerm) ||
                Array.from(item.querySelectorAll('.searchable')).some(span => (span.textContent || span.innerText).toLowerCase().includes(searchTerm));

                const isStatusMatch = currentStatus === 'todos' || item.classList.contains('status-' + currentStatus);

                if (isSearchMatch && isStatusMatch) {
                    // Se o filtro de cidade for 'todos' ou corresponder à cidade do grupo, o item é um candidato a ser visível
                    if (currentCity === 'todos' || group.dataset.city === currentCity) {
                        item.style.display = '';
                        hasVisibleItemsInGroup = true;
                    } else {
                        item.style.display = 'none';
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            if (hasVisibleItemsInGroup) {
                group.style.display = 'block';
                visibleCities.add(group.dataset.city); // Adiciona a cidade à lista de visíveis
            } else {
                group.style.display = 'none';
            }
        });
        
        // Ao final, recria os botões de filtro de cidade apenas com as cidades visíveis
        updateCityFilters(Array.from(visibleCities));
    }

    function adjustSearchSpacer() {
        if (clearFiltersBtn && searchSpacer) {
            searchSpacer.style.width = `${clearFiltersBtn.offsetWidth}px`;
        }
    }

    window.toggleOcorrencias = function (listId, buttonEl) {
        const listContainer = document.getElementById(listId);
        if (listContainer) {
            listContainer.classList.toggle('collapsed');
            if (listContainer.classList.contains('collapsed')) {
                buttonEl.textContent = 'Todas ocorrências';
            } else {
                buttonEl.textContent = 'Ocultar ocorrências';
            }
        }
    }

    // Função para abrir o novo modal de seleção de edição
    window.openGroupedEditModal = function (groupId) {
        // Busca os dados do grupo na variável global usando a chave
        const ocorrencias = groupedDataForEditing[groupId];

        if (!ocorrencias || !Array.isArray(ocorrencias) || ocorrencias.length === 0) {
            console.error("Dados não encontrados para o grupo de edição:", groupId);
            return;
        }

        const firstItem = ocorrencias[0];
        const container = document.getElementById('editSelectionContainer');

        document.getElementById('editSelectionEquipName').textContent = `${firstItem.nome_equip} - ${firstItem.referencia_equip}`;
        container.innerHTML = ''; // Limpa o conteúdo anterior

        ocorrencias.forEach(ocor => {
            const btn = document.createElement('button');
            btn.className = 'edit-selection-btn';
            btn.innerHTML = `<strong>${ocor.ocorrencia_reparo}</strong><br><small>Data: ${new Date(ocor.inicio_reparo).toLocaleDateString('pt-BR')}</small>`;
            btn.onclick = () => {
                closeModal('editSelectionModal');
                openEditModal(ocor.id, ocor.origem);
            };
            container.appendChild(btn);
        });

        openModal('editSelectionModal');
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

    window.openConcluirModal = (id, origem) => {

        // 1. Primeiro, busca os dados da ocorrência clicada e define o 'currentItem'
        currentItem = findOcorrenciaById(id, origem);
        if (!currentItem) {
            console.error("Ocorrência não encontrada com ID:", id, "e Origem:", origem);
            return;
        }

        // 2. AGORA, com o 'currentItem' correto, verifica o tipo da ocorrência
        if (currentItem.tipo_ocorrencia === 'Aguardando etiqueta') {
            openEtiquetaModal(); // Chama o modal correto
            return; // Interrompe para não abrir o modal padrão
        }

        // Comportamento padrão para outras ocorrências 
        reparoRealizadoTextarea.value = '';
        reparoRealizadoError.style.display = 'none';
        document.getElementById('concluirModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        document.getElementById('concluirOcorrenciaText').textContent = currentItem.ocorrencia_reparo;
        openModal('concluirModal');
    };

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

    function updateCityFilters(citiesToShow) {
        cityFilterContainer.innerHTML = '';

        // Usa a lista de cidades fornecida ou a lista completa como fallback
        const cities = citiesToShow || (allData ? allData.cidades : []) || [];

        if (cities.length > 0) {
            const allButton = document.createElement('button');
            // Mantém o botão 'Todas' ativo se essa for a seleção atual
            allButton.className = `filter-btn todos ${filters.city === 'todos' ? 'active' : ''}`;
            allButton.dataset.city = 'todos';
            allButton.textContent = 'Todas';
            cityFilterContainer.appendChild(allButton);

            cities.sort().forEach(cidade => {
                const button = document.createElement('button');
                // Mantém o botão da cidade ativo se essa for a seleção atual
                button.className = `filter-btn city ${filters.city === cidade ? 'active' : ''}`;
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

    // Função para abrir e configurar o modal da etiqueta
    function openEtiquetaModal() {
        if (!currentItem) return;

        // 1. Reseta o estado do modal para um novo uso
        document.getElementById('etiquetaModalEquipName').textContent = `${currentItem.nome_equip} - ${currentItem.referencia_equip}`;
        
        // Remove a seleção dos botões 'Sim' e 'Não'
        etiquetaSimBtn.classList.remove('btn-primary');
        etiquetaSimBtn.classList.add('btn-secondary');
        etiquetaNaoBtn.classList.remove('btn-primary');
        etiquetaNaoBtn.classList.add('btn-secondary');

        // Esconde os campos que dependem da seleção
        etiquetaDataGroup.classList.add('hidden');
        saveEtiquetaBtn.classList.add('hidden');
        
        // Limpa os campos e mensagens
        etiquetaDataInput.value = '';
        document.getElementById('etiquetaDataError').classList.add('hidden');
        document.getElementById('etiquetaMessage').classList.add('hidden');
        document.getElementById('etiquetaButtons').style.display = 'flex';
        
        // 2. Define a data máxima como hoje para o input de data
        const hoje = new Date().toISOString().split('T')[0];
        etiquetaDataInput.max = hoje;


        openModal('etiquetaModal');
    }

    // Ação do botão "Sim"
    etiquetaSimBtn.addEventListener('click', () => {
        // Estilo visual da seleção
        etiquetaSimBtn.classList.add('btn-primary');
        etiquetaSimBtn.classList.remove('btn-secondary');
        etiquetaNaoBtn.classList.remove('btn-primary');
        etiquetaNaoBtn.classList.add('btn-secondary');

        // Mostra os campos de data e o botão de confirmar
        etiquetaDataGroup.classList.remove('hidden');
        saveEtiquetaBtn.classList.remove('hidden');
    });

    // Ação do botão "Não"
    etiquetaNaoBtn.addEventListener('click', () => {
        // Estilo visual da seleção
        etiquetaNaoBtn.classList.add('btn-primary');
        etiquetaNaoBtn.classList.remove('btn-secondary');
        etiquetaSimBtn.classList.remove('btn-primary');
        etiquetaSimBtn.classList.add('btn-secondary');

        // Esconde os campos de data e o botão de confirmar
        etiquetaDataGroup.classList.add('hidden');
        saveEtiquetaBtn.classList.add('hidden');
        document.getElementById('etiquetaDataError').classList.add('hidden'); // Esconde erro se houver
    });

    // Ação do botão "Confirmar" do modal de etiqueta
    saveEtiquetaBtn.addEventListener('click', async () => {
        const dataFabricacao = etiquetaDataInput.value;
        const errorEl = document.getElementById('etiquetaDataError');

        if (!dataFabricacao) {
            errorEl.textContent = 'Data de fabricação obrigatoria para concluir';
            errorEl.classList.remove('hidden');
            return;
        }
        errorEl.classList.add('hidden');

        const payload = {
            action: 'concluir_etiqueta',
            id_ocorrencia_processamento: currentItem.id,
            id_equipamento: currentItem.id_equipamento,
            id_manutencao: currentItem.id_manutencao,
            dt_fabricacao: dataFabricacao
        };

        // Controla os elementos corretos do modal de etiqueta
        const button = saveEtiquetaBtn;
        const spinner = button.querySelector('.spinner');
        const messageEl = document.getElementById('etiquetaMessage');
        const buttonsDiv = document.getElementById('etiquetaButtons');

        button.disabled = true;
        if (spinner) spinner.classList.add('is-active');
        if (messageEl) messageEl.classList.add('hidden');

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Erro na comunicação com o servidor.');
            }

            if (buttonsDiv) buttonsDiv.style.display = 'none';
            if (messageEl) {
                messageEl.textContent = 'Etiqueta concluída com sucesso!';
                messageEl.className = 'message success';
                messageEl.classList.remove('hidden');
            }

            setTimeout(() => {
                closeModal('etiquetaModal');
                initialLoad(); // Recarrega a página para refletir as mudanças
            }, 2000);

        } catch (error) {
            if (messageEl) {
                messageEl.textContent = `Erro: ${error.message}`;
                messageEl.className = 'message error';
                messageEl.classList.remove('hidden');
            }
            if (button) button.disabled = false;
            if (spinner) spinner.classList.remove('is-active');
        }
    });

    etiquetaDataInput.addEventListener('input', () => {
        document.getElementById('etiquetaDataError').classList.add('hidden');
    });

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

    // --- Inicialização da Página ---
    initializeFilters();
    initialLoad().then(() => {
        console.log('Carga inicial de ocorrências de processamento completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });
    window.addEventListener('resize', adjustSearchSpacer);
});