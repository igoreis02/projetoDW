document.addEventListener('DOMContentLoaded', function () {
    // --- REFERÊNCIAS AOS ELEMENTOS ---
    const actionButtons = document.querySelectorAll('.action-btn');
    const filterContainer = document.getElementById('filterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const pageLoader = document.getElementById('pageLoader');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    // --- [NOVO] REFERÊNCIAS PARA A VISÃO SIMPLIFICADA ---
    const btnSimplificado = document.getElementById('btnSimplificado');
    const simplifiedView = document.getElementById('simplifiedView');
    const mainControls = document.querySelector('.main-controls-container');
    const voltarBtnFooter = document.querySelector('.voltar-btn');
    // --- FIM [NOVO] ---


    // --- VARIÁVEIS DE ESTADO ---
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null; 
    let currentItemsToAssign = [];
    let currentEditingItem = null;
    let updateInterval;
    
    // --- [NOVO] VARIÁVEL DE ESTADO PARA A VISÃO SIMPLIFICADA ---
    let isSimplifiedViewActive = false;
    // --- FIM [NOVO] ---


    // --- LÓGICA DE BUSCA E RENDERIZAÇÃO ---
    async function fetchData(isUpdate = false) {
        if (!isUpdate) {
            pageLoader.style.display = 'flex';
            ocorrenciasContainer.innerHTML = ''; 
        }
        
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const params = new URLSearchParams({ data_inicio: startDate, data_fim: endDate || startDate });

        try {
            const response = await fetch(`API/get_ocorrencias_pendentes.php?${params.toString()}`);
            const result = await response.json();
            const newSignature = JSON.stringify(result.data);
            const oldSignature = JSON.stringify(allData);
            
            if (isUpdate) {
                if (newSignature !== oldSignature) {
                    allData = result.data;
                    applyFiltersAndRender();
                }
            } else {
                if (result.success) {
                    allData = result.data;
                    applyFiltersAndRender();
                } else {
                    allData = null;
                    ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                    updateCityFilters([]);
                }
            }
        } catch (error) {
            if (!isUpdate) {
                console.error('Erro ao buscar dados:', error);
                ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
            }
        } finally {
            if (!isUpdate) {
                pageLoader.style.display = 'none'; 
            }
        }
    }
    
    function startAutoUpdate() {
        if (updateInterval) clearInterval(updateInterval);
        updateInterval = setInterval(() => fetchData(true), 30000); 
    }

    function applyFiltersAndRender() {
        if (!allData || !allData.ocorrencias) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada.</p>`;
            updateCityFilters([]);
            return;
        }

        const searchTerm = searchInput.value.toLowerCase();
        let filteredOcorrencias = {};
        let citiesWithContent = new Set();

        for (const cidade in allData.ocorrencias) {
            const itemsInCity = allData.ocorrencias[cidade].filter(item => {
                const searchMatch = !searchTerm ||
                    (item.nome_equip && item.nome_equip.toLowerCase().includes(searchTerm)) ||
                    (item.referencia_equip && item.referencia_equip.toLowerCase().includes(searchTerm)) ||
                    (item.ocorrencia_reparo && item.ocorrencia_reparo.toLowerCase().includes(searchTerm)) ||
                    (item.atribuido_por && item.atribuido_por.toLowerCase().includes(searchTerm));
                
                let typeMatch = false;
                if (activeType === 'manutencao') {
                    if (['corretiva', 'preventiva', 'preditiva'].includes(item.tipo_manutencao)) typeMatch = true;
                } else if (activeType === 'instalação') {
                    if (item.tipo_manutencao === 'instalação') typeMatch = true;
                }
                
                return searchMatch && typeMatch;
            });

            if (itemsInCity.length > 0) {
                filteredOcorrencias[cidade] = itemsInCity;
                citiesWithContent.add(cidade);
            }
        }

        renderAllOcorrencias({ ocorrencias: filteredOcorrencias });
        updateCityFilters(Array.from(citiesWithContent));
        updateDisplay();
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
                    <div class="city-group-header">
                        <h2 class="city-group-title">${cidade}</h2>
                        <button class="atribuir-cidade-btn hidden" data-city="${cidade}" onclick="handleMultiAssignClick(this)">Atribuir</button>
                    </div>
                    <div class="city-ocorrencias-grid">
                        ${cityGridHTML}
                    </div>
                `;
                ocorrenciasContainer.appendChild(cityGroup);
            });
        } else {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada para os filtros selecionados.</p>`;
        }
    }
    
    function updateCityFilters(cities) {
        filterContainer.innerHTML = '';
        
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
        
        activeCity = 'todos';
        addFilterListeners();
    }

    function updateDisplay() {
        const cityGroups = document.querySelectorAll('.city-group');
        let hasVisibleContent = false;
        
        cityGroups.forEach(group => {
            const isVisible = (activeCity === 'todos' || group.dataset.city === activeCity);
            group.classList.toggle('hidden', !isVisible);
            if(isVisible) hasVisibleContent = true;
        });

        if (!hasVisibleContent && cityGroups.length > 0) {
             ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência encontrada para a cidade "${activeCity}".</p>`;
        } else if (cityGroups.length === 0) {
             ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada para os filtros selecionados.</p>`;
        }

        checkSelectionAndToggleButtons();
    }

    // --- [MODIFICADO] LÓGICA PARA A VISÃO SIMPLIFICADA ---

    function generateSimplifiedView() {
        if (!allData || !allData.ocorrencias || Object.keys(allData.ocorrencias).length === 0) {
            simplifiedView.innerHTML = '<p>Não há dados para exibir no resumo.</p>';
            return;
        }

        let corretivasPorCidade = {};
        
        // 1. Filtra APENAS manutenções 'corretiva' e agrupa por cidade
        for (const cidade in allData.ocorrencias) {
            const corretivas = allData.ocorrencias[cidade].filter(item => 
                item.tipo_manutencao === 'corretiva'
            );
            if (corretivas.length > 0) {
                corretivasPorCidade[cidade] = corretivas;
            }
        }

        // Constrói o HTML do resumo
        let html = '<h2>MANUTENÇÕES CORRETIVAS PENDENTES:</h2>';
        const sortedCities = Object.keys(corretivasPorCidade).sort();
        
        if (sortedCities.length === 0) {
            html += '<p>Nenhuma manutenção corretiva pendente encontrada.</p>';
        } else {
            for (const city of sortedCities) {
                html += `<h3>${city}</h3>`;
                html += `<ul>`;
                for (const item of corretivasPorCidade[city]) {
                    // 2. Lógica para extrair o nome antes do "-"
                    let displayName = item.nome_equip;
                    if (displayName && displayName.includes('-')) {
                        displayName = displayName.split('-')[0].trim();
                    }
                    
                    // 3. Monta o novo formato do item da lista
                    html += `<li><strong>${displayName}</strong> - ${item.referencia_equip}: ${item.ocorrencia_reparo}</li>`;
                }
                html += `</ul>`;
            }
        }

        simplifiedView.innerHTML = html;
    }

    function toggleView(showSimplified) {
        isSimplifiedViewActive = showSimplified;

        // Elementos principais que são escondidos/mostrados
        searchInput.parentElement.classList.toggle('hidden', showSimplified); // O .search-container
        filterContainer.classList.toggle('hidden', showSimplified);
        ocorrenciasContainer.classList.toggle('hidden', showSimplified);
        voltarBtnFooter.classList.toggle('hidden', showSimplified);
        simplifiedView.classList.toggle('hidden', !showSimplified);
        
        // Esconde os botões de data e tipo (Manutenção/Instalação)
        mainControls.querySelector('.action-buttons').classList.toggle('hidden', showSimplified);
        mainControls.querySelector('.date-filter-container').classList.toggle('hidden', showSimplified);
        
        // Atualiza o estado visual do botão "Simplificado"
        btnSimplificado.classList.toggle('active', showSimplified);

        if (showSimplified) {
            generateSimplifiedView();
            // Desativa os outros botões de tipo
            document.getElementById('btnManutencoes').classList.remove('active');
            document.getElementById('btnInstalacoes').classList.remove('active');
        } else {
            // Garante que o botão de manutenção volte a ser o ativo padrão
            document.getElementById('btnManutencoes').classList.add('active');
            activeType = 'manutencao'; // Restaura o tipo ativo
            applyFiltersAndRender(); // Re-renderiza a visão detalhada
        }
    }

    // --- FIM DA MODIFICAÇÃO ---


    // --- EVENT LISTENERS E LÓGICA DE AÇÕES ---
    clearFiltersBtn.addEventListener('click', () => {
        if (isSimplifiedViewActive) {
            toggleView(false);
        }
        searchInput.value = '';
        startDateInput.value = '';
        endDateInput.value = '';
        activeType = 'manutencao';
        activeCity = 'todos';
        
        actionButtons.forEach(btn => btn.classList.remove('active'));
        document.getElementById('btnManutencoes').classList.add('active');
        
        fetchData();
    });

    actionButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (isSimplifiedViewActive && button.id !== 'btnSimplificado') {
                toggleView(false);
            }

            if (button.id !== 'btnSimplificado') {
                actionButtons.forEach(btn => {
                    if(btn.id !== 'btnSimplificado') btn.classList.remove('active');
                });
                button.classList.add('active');
                activeType = button.dataset.type;
                applyFiltersAndRender();
            }
        });
    });
    
    btnSimplificado.addEventListener('click', () => {
        toggleView(!isSimplifiedViewActive);
    });

    startDateInput.addEventListener('change', fetchData);
    endDateInput.addEventListener('change', fetchData);
    searchInput.addEventListener('input', applyFiltersAndRender);

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
                <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || 'N/A'}</span></div>
                ${item.observacao_instalacao ? `<div class="detail-item"><strong>Observação</strong> <span>${item.observacao_instalacao}</span></div>` : ''}
            `;
        } else {
            detailsHTML = `
                <div class="detail-item"><strong>Ocorrência</strong> <span class="status-tag status-pendente">${item.ocorrencia_reparo || 'Não especificada'}</span></div>
                <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                ${atribuidoPorHTML}
                <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || 'N/A'}</span></div>
                ${item.motivo_devolucao ? `<div class="detail-item"><strong>Devolvida</strong> <span class="status-tag status-pendente">${item.motivo_devolucao}</span></div>` : ''}
            `;
        }

        const actionsHTML = `
            <div class="item-actions">
                <p style="margin-right: auto; font-size: 0.9em; color: #6b7280;">Clique no card para selecionar e atribuir</p>
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

    window.openModal = function (modalId) { document.getElementById(modalId).classList.add('is-active'); }
    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('is-active');
        if (modalId === 'assignModal') {
            const saveBtn = document.getElementById('saveAssignmentBtn');
            const btnText = saveBtn.querySelector('span');
            const btnSpinner = saveBtn.querySelector('.spinner');
            saveBtn.disabled = false;
            btnText.textContent = 'Salvar Atribuição';
            btnSpinner.classList.add('hidden');
            document.getElementById('assignErrorMessage').classList.add('hidden');
            document.querySelector('.modal-footer-buttons').classList.remove('hidden');
        }
        if (modalId === 'confirmationModal') {
            document.getElementById('confirmationFooter').classList.remove('hidden');
            document.getElementById('confirmationMessage').classList.add('hidden');
        }
    }

    function findOcorrenciaById(id) {
        if (!allData || !allData.ocorrencias) return null;
        for (const cidade in allData.ocorrencias) {
            const found = allData.ocorrencias[cidade].find(item => item.id_manutencao == id);
            if (found) return found;
        }
        return null;
    }

    ocorrenciasContainer.addEventListener('click', function (e) {
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

    window.handleMultiAssignClick = async function (button) {
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
        document.getElementById('assignErrorMessage').classList.add('hidden');

        const tecnicosContainer = document.getElementById('assignTecnicosContainer');
        const veiculosContainer = document.getElementById('assignVeiculosContainer');
        tecnicosContainer.innerHTML = 'A carregar...';
        veiculosContainer.innerHTML = 'A carregar...';

        openModal('assignModal');

        const [tecnicosRes, veiculosRes] = await Promise.all([fetch('API/get_tecnicos.php'), fetch('API/get_veiculos.php')]);
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

    window.saveAssignment = async function () {
        const saveBtn = document.getElementById('saveAssignmentBtn');
        const btnText = saveBtn.querySelector('span');
        const btnSpinner = saveBtn.querySelector('.spinner');
        const assignErrorMessage = document.getElementById('assignErrorMessage');
        const inicioReparo = document.getElementById('assignInicioReparo').value;
        const fimReparo = document.getElementById('assignFimReparo').value;
        const selectedTecnicos = Array.from(document.querySelectorAll('#assignTecnicosContainer .choice-btn.selected')).map(btn => btn.dataset.id);
        const selectedVeiculos = Array.from(document.querySelectorAll('#assignVeiculosContainer .choice-btn.selected')).map(btn => btn.dataset.id);

        assignErrorMessage.classList.add('hidden');
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        if (!inicioReparo || !fimReparo) {
            assignErrorMessage.textContent = 'As datas de início e fim são obrigatórias.';
            assignErrorMessage.classList.remove('hidden');
            return;
        }

        const dataInicio = new Date(inicioReparo + 'T00:00:00');
        const dataFim = new Date(fimReparo + 'T00:00:00');

        if (dataInicio < hoje) {
            assignErrorMessage.textContent = 'A data de início não pode ser anterior à data de hoje.';
            assignErrorMessage.classList.remove('hidden');
            return;
        }

        if (dataFim < dataInicio) {
            assignErrorMessage.textContent = 'A data de fim não pode ser anterior à data de início.';
            assignErrorMessage.classList.remove('hidden');
            return;
        }

        if (selectedTecnicos.length === 0) {
            assignErrorMessage.textContent = 'Por favor, selecione pelo menos um técnico.';
            assignErrorMessage.classList.remove('hidden');
            return;
        }

        if (selectedVeiculos.length === 0) {
            assignErrorMessage.textContent = 'Por favor, selecione pelo menos um veículo.';
            assignErrorMessage.classList.remove('hidden');
            return;
        }

        saveBtn.disabled = true;
        btnText.textContent = 'Salvando...';
        btnSpinner.classList.remove('hidden');

        const dataToSend = {
            idsManutencao: currentItemsToAssign.map(item => item.id_manutencao),
            dataInicio: inicioReparo,
            dataFim: fimReparo,
            idsTecnicos: selectedTecnicos,
            idsVeiculos: selectedVeiculos
        };

        try {
            const response = await fetch('API/atribuir_tecnicos_manutencao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                document.querySelector('.modal-footer-buttons').classList.add('hidden');
                assignErrorMessage.textContent = 'Técnico atribuído com sucesso!';
                assignErrorMessage.style.color = '#155724';
                assignErrorMessage.classList.remove('hidden');
                setTimeout(() => {
                    closeModal('assignModal');
                    fetchData();
                }, 2000);
            } else {
                 throw new Error(result.message || 'Erro desconhecido ao salvar.');
            }
        } catch (error) {
            assignErrorMessage.textContent = error.message;
            assignErrorMessage.classList.remove('hidden');
            saveBtn.disabled = false;
            btnText.textContent = 'Salvar Atribuição';
            btnSpinner.classList.add('hidden');
        }
    }

    window.openEditOcorrenciaModal = function (id, event) {
        event.stopPropagation();
        currentEditingItem = findOcorrenciaById(id);
        if (!currentEditingItem) return;

        document.getElementById('editOcorrenciaModalInfo').innerHTML = `<p><strong>Equipamento:</strong> ${currentEditingItem.nome_equip} - ${currentEditingItem.referencia_equip}</p>`;
        document.getElementById('editOcorrenciaTextarea').value = currentEditingItem.ocorrencia_reparo;
        openModal('editOcorrenciaModal');
    }

    window.saveOcorrenciaUpdate = async function () {
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
            const response = await fetch('API/update_ocorrencia.php', {
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

    window.openConfirmationModal = function (id, status, event) {
        if (event) event.stopPropagation();
        const title = 'Cancelar Ocorrência';
        const text = 'Tem a certeza de que deseja cancelar esta ocorrência? Esta ação não pode ser desfeita.';

        document.getElementById('confirmationModalTitle').textContent = title;
        document.getElementById('confirmationModalText').textContent = text;
        const confirmBtn = document.getElementById('confirmActionButton');
        confirmBtn.onclick = () => executeStatusChange(id, status);
        openModal('confirmationModal');
    }

    async function executeStatusChange(id, status) {
        const confirmFooter = document.getElementById('confirmationFooter');
        const confirmMessage = document.getElementById('confirmationMessage');
        const confirmBtn = document.getElementById('confirmActionButton');
        confirmBtn.disabled = true;

        const dataToSend = {
            action: 'update_status',
            id: id,
            status: status,
            origem: 'manutencao'
        };

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                confirmFooter.classList.add('hidden');
                confirmMessage.textContent = 'Ocorrência cancelada com sucesso!';
                confirmMessage.style.color = '#155724';
                confirmMessage.classList.remove('hidden');
                setTimeout(() => {
                    closeModal('confirmationModal');
                    fetchData();
                }, 2000);
            } else {
                throw new Error(result.message || 'Erro desconhecido ao alterar o status.');
            }
        } catch (error) {
            console.error("Erro ao alterar status:", error);
            confirmFooter.classList.add('hidden');
            confirmMessage.textContent = `Erro: ${error.message}`;
            confirmMessage.style.color = '#ef4444';
            confirmMessage.classList.remove('hidden');
        } finally {
            if (document.getElementById('confirmationModal').classList.contains('is-active')) {
                confirmBtn.disabled = false;
            }
        }
    }

    const assignErrorMessage = document.getElementById('assignErrorMessage');
    document.getElementById('assignInicioReparo').addEventListener('input', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignFimReparo').addEventListener('input', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignTecnicosContainer').addEventListener('click', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignVeiculosContainer').addEventListener('click', () => assignErrorMessage.classList.add('hidden'));

    // --- INICIALIZAÇÃO ---
    fetchData(); 
    startAutoUpdate();
});