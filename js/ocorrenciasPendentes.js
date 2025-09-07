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
    const btnSimplificado = document.getElementById('btnSimplificado');
    const simplifiedView = document.getElementById('simplifiedView');
    const mainControls = document.querySelector('.main-controls-container');
    const voltarBtnFooter = document.querySelector('.voltar-btn');

    // --- VARIÁVEIS DE ESTADO ---
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null;
    let currentItemsToAssign = [];
    let currentEditingItem = null;
    let updateInterval;
    let currentCardForSelection = null;
    let isSimplifiedViewActive = false;

    // --- LÓGICA DE BUSCA E RENDERIZAÇÃO ---
    async function fetchData(isUpdate = false) {
        if (!isUpdate) {
            pageLoader.style.display = 'flex';
            ocorrenciasContainer.innerHTML = '';
        }
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const params = new URLSearchParams({
            data_inicio: startDate,
            data_fim: endDate || startDate
        });

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
        updateInterval = setInterval(() => fetchData(true), 5000);  // Atualiza a cada 5 segundos
    }

    function applyFiltersAndRender() {
        if (isSimplifiedViewActive) {
            generateSimplifiedView(activeCity); // Se a visão simplificada estiver ativa, apenas a regenere com o filtro atual
            return;
        };
        if (!allData || !allData.ocorrencias) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada.</p>`;
            updateCityFilters([]);
            return;
        }

        const groupedOcorrenciasByCity = {};
        for (const cidade in allData.ocorrencias) {
            const cityItems = allData.ocorrencias[cidade];
            const itemsGroupedByEquip = {};

            cityItems.forEach(item => {
                if (item.tipo_manutencao === 'instalação') {
                    const uniqueKey = `inst-${item.id_manutencao}`;
                    itemsGroupedByEquip[uniqueKey] = { ...item, isGrouped: false, ocorrencias_detalhadas: [item] };
                    return;
                }
                const equipKey = `${item.nome_equip}|||${item.referencia_equip}`;
                if (!itemsGroupedByEquip[equipKey]) {
                    itemsGroupedByEquip[equipKey] = { ...item, isGrouped: true, ocorrencias_detalhadas: [] };
                }
                itemsGroupedByEquip[equipKey].ocorrencias_detalhadas.push(item);
            });
            groupedOcorrenciasByCity[cidade] = Object.values(itemsGroupedByEquip);
        }

        const searchTerm = searchInput.value.toLowerCase();
        let filteredOcorrencias = {};
        let citiesWithContent = new Set();

        for (const cidade in groupedOcorrenciasByCity) {
            const itemsInCity = groupedOcorrenciasByCity[cidade].filter(groupedItem => {
                const searchMatch = !searchTerm ||
                    (groupedItem.nome_equip && groupedItem.nome_equip.toLowerCase().includes(searchTerm)) ||
                    (groupedItem.referencia_equip && groupedItem.referencia_equip.toLowerCase().includes(searchTerm)) ||
                    (groupedItem.atribuido_por && groupedItem.atribuido_por.toLowerCase().includes(searchTerm)) ||
                    groupedItem.ocorrencias_detalhadas.some(detail => detail.ocorrencia_reparo && detail.ocorrencia_reparo.toLowerCase().includes(searchTerm));

                const typeMatch = (activeType === 'manutencao' && groupedItem.tipo_manutencao === 'corretiva') ||
                    (activeType === 'instalação' && groupedItem.tipo_manutencao === 'instalação');

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
                const cityGridHTML = ocorrencias[cidade].map(createOcorrenciaHTML).join('');

                cityGroup.innerHTML = `
                    <div class="city-group-header">
                        <h2 class="city-group-title">${cidade}</h2>
                        <button class="atribuir-cidade-btn hidden" data-city="${cidade}" onclick="handleMultiAssignClick(this)">Atribuir</button>
                    </div>
                    <div class="city-ocorrencias-grid">${cityGridHTML}</div>`;
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
            if (isVisible) hasVisibleContent = true;
        });

        if (!hasVisibleContent && cityGroups.length > 0) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência encontrada para a cidade "${activeCity}".</p>`;
        } else if (cityGroups.length === 0 && (searchInput.value || startDateInput.value)) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada para os filtros selecionados.</p>`;
        }
        checkSelectionAndToggleButtons();
    }
    // --- GERAÇÃO DA VISÃO SIMPLIFICADA ---
    function generateSimplifiedView(cityFilter = 'todos') {
        if (!allData || !allData.ocorrencias || Object.keys(allData.ocorrencias).length === 0) {
            simplifiedView.innerHTML = '<p>Não há dados para exibir no resumo.</p>';
            return;
        }

        let corretivasPorCidade = {};
        for (const cidade in allData.ocorrencias) {
            const corretivas = allData.ocorrencias[cidade].filter(item => item.tipo_manutencao === 'corretiva');
            if (corretivas.length > 0) {
                corretivasPorCidade[cidade] = corretivas;
            }
        }

        let html = '<h2>MANUTENÇÕES CORRETIVAS PENDENTES:</h2>';
        const sortedCities = Object.keys(corretivasPorCidade).sort();

        const citiesToRender = cityFilter === 'todos' ? sortedCities : [cityFilter];
        let hasContentForFilter = false;

        if (sortedCities.length === 0) {
            html += '<p>Nenhuma manutenção corretiva pendente encontrada.</p>';
        } else {
            for (const city of citiesToRender) {
                if (corretivasPorCidade[city]) {
                    hasContentForFilter = true;
                    html += `<h3>${city}</h3>`;
                    html += `<ul>`;

                    const itemsGroupedByEquip = {};
                    for (const item of corretivasPorCidade[city]) {
                        const key = `${item.nome_equip}|||${item.referencia_equip}`;
                        if (!itemsGroupedByEquip[key]) {
                            itemsGroupedByEquip[key] = [];
                        }
                        itemsGroupedByEquip[key].push(item);
                    }

                    for (const groupKey in itemsGroupedByEquip) {
                        const groupItems = itemsGroupedByEquip[groupKey];
                        const firstItem = groupItems[0];
                        let displayName = firstItem.nome_equip;
                        if (displayName && displayName.includes('-')) {
                            displayName = displayName.split('-')[0].trim();
                        }
                        const problemasConcatenados = groupItems.map(item => item.ocorrencia_reparo).join('; ');
                        html += `<li><strong>${displayName}</strong> - ${firstItem.referencia_equip}: ${problemasConcatenados}</li>`;
                    }
                    html += `</ul>`;
                }
            }
    
            if (!hasContentForFilter && cityFilter !== 'todos') {
                html += `<h3>${cityFilter}</h3><p>Nenhuma manutenção corretiva pendente para esta cidade.</p>`;
            }
        }
        simplifiedView.innerHTML = html;
    }

   
    function toggleView(showSimplified) {
        isSimplifiedViewActive = showSimplified;

        // Esconde tudo, exceto o filtro de cidade
        searchInput.parentElement.classList.toggle('hidden', showSimplified);
        mainControls.querySelector('.action-buttons').classList.toggle('hidden', showSimplified);
        mainControls.querySelector('.date-filter-container').classList.toggle('hidden', showSimplified);

        // Lógica de visualização principal vs. simplificada
        ocorrenciasContainer.classList.toggle('hidden', showSimplified);
        simplifiedView.classList.toggle('hidden', !showSimplified);
        voltarBtnFooter.classList.toggle('hidden', !showSimplified); // O botão de voltar aparece na visão de cards

        btnSimplificado.classList.toggle('active', showSimplified);

        if (showSimplified) {
            generateSimplifiedView(activeCity); // Gera a view simplificada respeitando o filtro atual
            document.getElementById('btnManutencoes').classList.remove('active');
            document.getElementById('btnInstalacoes').classList.remove('active');
        } else {
            document.getElementById('btnManutencoes').classList.add('active');
            activeType = 'manutencao';
            applyFiltersAndRender();
        }
    }

    // --- EVENT LISTENERS ---
    clearFiltersBtn.addEventListener('click', () => {
        if (isSimplifiedViewActive) toggleView(false);
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
            if (isSimplifiedViewActive && button.id !== 'btnSimplificado') toggleView(false);
            if (button.id !== 'btnSimplificado') {
                actionButtons.forEach(btn => { if (btn.id !== 'btnSimplificado') btn.classList.remove('active'); });
                button.classList.add('active');
                activeType = button.dataset.type;
                applyFiltersAndRender();
            }
        });
    });

    btnSimplificado.addEventListener('click', () => toggleView(!isSimplifiedViewActive));
    startDateInput.addEventListener('change', fetchData);
    endDateInput.addEventListener('change', fetchData);
    searchInput.addEventListener('input', applyFiltersAndRender);

    function addFilterListeners() {
        filterContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                filterContainer.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                activeCity = e.target.dataset.city;

                if (isSimplifiedViewActive) {
                    // Se estiver no modo simplificado, renderiza a lista filtrada
                    generateSimplifiedView(activeCity);
                } else {
                    // Senão, atualiza a exibição dos cards
                    updateDisplay();
                }
            }
        });
    }


    function createOcorrenciaHTML(item) {
        const firstOcorrencia = item.ocorrencias_detalhadas[0];
        let detailsHTML = '';
        const statusHTML = `<span class="status-tag status-pendente">Pendente</span>`;
        let atribuidoPorHTML = firstOcorrencia.atribuido_por ? `<div class="detail-item"><strong>Solicitado por</strong> <span>${firstOcorrencia.atribuido_por}</span></div>` : '';

        // --- LÓGICA PARA INSTALAÇÕES ---
        if (item.tipo_manutencao === 'instalação') {
            const statusMap = { inst_laco: 'Laço', inst_base: 'Base', inst_infra: 'Infra', inst_energia: 'Energia', inst_prov: 'Provedor' };
            const dateMap = { inst_laco: 'dt_laco', inst_base: 'dt_base', inst_infra: 'data_infra', inst_energia: 'dt_energia', inst_prov: 'data_provedor' };
            detailsHTML = Object.entries(statusMap).map(([key, label]) => {
                const status = firstOcorrencia[key] == 1 ?
                    `<span class="status-value instalado">Instalado ${formatDate(firstOcorrencia[dateMap[key]])}</span>` :
                    `<span class="status-value aguardando">Aguardando instalação</span>`;
                return `<div class="detail-item"><strong>${label}</strong> <span>${status}</span></div>`;
            }).join('');

            // --- LÓGICA PARA MÚLTIPLAS OCORRÊNCIAS ---
        } else if (item.ocorrencias_detalhadas.length > 1) {
            let ocorrenciasListHTML = item.ocorrencias_detalhadas.map((ocor, index) =>
                `<li>
                <strong>${index + 1}.</strong> <span class="status-tag status-pendente">${ocor.ocorrencia_reparo.toUpperCase() || 'Não especificada'}</span>
                <div style="font-size: 0.9em; color: #6b7280; padding-left: 20px;">
                    <strong>Data Ocorrência:</strong> ${new Date(ocor.inicio_reparo).toLocaleString('pt-BR')}
                </div>
            </li>`
            ).join('');
            detailsHTML = `<div class="detail-item"><strong>Ocorrências</strong><ul class="ocorrencia-list">${ocorrenciasListHTML}</ul></div>`;

            // --- LÓGICA PARA OCORRÊNCIA ÚNICA ---
        } else {
            detailsHTML = `
            <div class="detail-item">
                <strong>Ocorrência:</strong> <span class="status-tag status-pendente">${firstOcorrencia.ocorrencia_reparo.toUpperCase() || 'Não especificada'}</span>
                <div style="font-size: 0.9em; color: #6b7280; padding-left: 0px; margin-top: 5px;">
                    <strong>Data Ocorrência:</strong> ${new Date(firstOcorrencia.inicio_reparo).toLocaleString('pt-BR')}
                </div>
            </div>
        `;
        }

        // --- DETALHES COMUNS A AMBAS AS SITUAÇÕES ---
        const commonDetails = `
        ${atribuidoPorHTML}
        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
        <div class="detail-item"><strong>Local</strong> <span>${firstOcorrencia.local_completo || 'N/A'}</span></div>
        ${firstOcorrencia.motivo_devolucao ? `<div class="detail-item"><strong>Devolvida</strong> <span class="status-tag status-pendente">${firstOcorrencia.motivo_devolucao}</span></div>` : ''}
    `;

        // A lógica de adicionar os detalhes comuns 
        detailsHTML += commonDetails;

        // --- AÇÕES ---
        const allIdsInGroup = item.ocorrencias_detalhadas.map(o => o.id_manutencao).join(',');
        const isGrouped = item.tipo_manutencao !== 'instalação' && item.ocorrencias_detalhadas.length > 1;

        const actionsHTML = `
        <div class="item-actions">
            <p style="margin-right: auto; font-size: 0.9em; color: #6b7280;">Clique no card para selecionar</p>
            <button class="item-btn edit-btn" onclick="openEditOcorrenciaModal('${allIdsInGroup}', event)">Editar</button>
            <button class="item-btn cancel-btn" onclick="handleCancelClick('${allIdsInGroup}', ${isGrouped}, event)">Cancelar</button>
        </div>
    `;

        const groupKey = `${item.nome_equip}|||${item.referencia_equip}`;

        return `
        <div class="ocorrencia-item" data-type="${item.tipo_manutencao}" data-id="${allIdsInGroup}" data-group-key="${groupKey}" data-is-grouped="${isGrouped}">
            <div class="ocorrencia-header"><h3>${item.nome_equip} - ${item.referencia_equip}</h3></div>
            <div class="ocorrencia-details">${detailsHTML}</div>
            ${actionsHTML}
        </div>
    `;
    }

    function formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00') return '';
        const date = new Date(dateString);
        return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
    }

    window.openModal = (modalId) => document.getElementById(modalId).classList.add('is-active');

    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('is-active'); 

        if (modalId === 'assignModal') {
            const saveBtn = document.getElementById('saveAssignmentBtn');
            const btnText = saveBtn.querySelector('span');
            const btnSpinner = saveBtn.querySelector('.spinner');
            const assignErrorMessage = document.getElementById('assignErrorMessage');


            saveBtn.disabled = false;
            btnText.textContent = 'Salvar Atribuição';
            btnSpinner.classList.add('hidden');


            document.querySelector('#assignModal .modal-footer-buttons').classList.remove('hidden');
            assignErrorMessage.style.color = '#ef4444';


            assignErrorMessage.classList.add('hidden');
        }

        if (modalId === 'confirmationModal') {
            document.getElementById('confirmationFooter').classList.remove('hidden');
            document.getElementById('confirmationMessage').classList.add('hidden');
        }
        if (modalId === 'editOcorrenciaModal') {
            document.getElementById('editOcorrenciaTextarea').parentElement.classList.remove('hidden');
            const oldFormGroups = document.querySelectorAll('#editOcorrenciaModal .dynamic-form-group');
            oldFormGroups.forEach(group => group.remove());
        }
        if (modalId === 'cancelSelectionModal') {
            const btn = document.getElementById('confirmCancelBtn');
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Confirmar Cancelamento';
            btn.querySelector('.spinner').classList.add('hidden');
            document.getElementById('cancelFooterButtons').classList.remove('hidden');
            document.getElementById('cancelSelectionError').classList.add('hidden');
        }
    };

    function findOcorrenciaById(id) {
        if (!allData || !allData.ocorrencias) return null;
        for (const cidade in allData.ocorrencias) {
            const found = allData.ocorrencias[cidade].find(item => item.id_manutencao == id);
            if (found) return found;
        }
        return null;
    }

    function findGroupedItemByKey(key) {
        if (!allData || !allData.ocorrencias) return null;
        let allItems = [].concat(...Object.values(allData.ocorrencias));
        const parts = key.split('|||');
        if (parts.length !== 2) return null;
        const nome_equip = parts[0];
        const referencia_equip = parts[1];
        const occurrences = allItems.filter(item => item.nome_equip === nome_equip && item.referencia_equip === referencia_equip);
        if (occurrences.length > 0) {
            return {
                nome_equip: occurrences[0].nome_equip,
                referencia_equip: occurrences[0].referencia_equip,
                ocorrencias_detalhadas: occurrences
            };
        }
        return null;
    }

    ocorrenciasContainer.addEventListener('click', function (e) {
        if (e.target.closest('.item-btn')) return;
        const item = e.target.closest('.ocorrencia-item');
        if (item) {
            const isGrouped = item.dataset.isGrouped === 'true';
            const isSelected = item.classList.contains('selected');
            if (isSelected) {
                item.classList.remove('selected');
                item.removeAttribute('data-selected-ids');
            } else {
                if (isGrouped) {
                    currentCardForSelection = item;
                    openSelectOcorrenciasModal();
                } else {
                    item.classList.add('selected');
                    item.dataset.selectedIds = item.dataset.id;
                }
            }
            checkSelectionAndToggleButtons();
        }
    });

    function openSelectOcorrenciasModal() {
        if (!currentCardForSelection) return;
        const groupKey = currentCardForSelection.dataset.groupKey;
        const groupedItem = findGroupedItemByKey(groupKey);
        if (!groupedItem) {
            console.error("Não foi possível encontrar os dados do item agrupado.");
            return;
        }
        document.getElementById('selectOcorrenciasModalTitle').textContent = `Selecionar Ocorrências`;
        document.getElementById('selectOcorrenciasModalInfo').innerHTML = `<p><strong>Equipamento:</strong> ${groupedItem.nome_equip} - ${groupedItem.referencia_equip}</p>`;
        const container = document.getElementById('selectOcorrenciasContainer');
        container.innerHTML = '';
        groupedItem.ocorrencias_detalhadas.forEach(ocor => {
            const btn = document.createElement('button');
            btn.className = 'choice-btn';
            btn.dataset.id = ocor.id_manutencao;
            btn.textContent = `${ocor.ocorrencia_reparo} (Início: ${new Date(ocor.inicio_reparo).toLocaleDateString('pt-BR')})`;
            btn.onclick = () => btn.classList.toggle('selected');
            container.appendChild(btn);
        });
        document.getElementById('selectOcorrenciasError').classList.add('hidden');
        openModal('selectOcorrenciasModal');
    }

    window.confirmOcorrenciaSelection = function () {
        const selectedBtns = document.querySelectorAll('#selectOcorrenciasContainer .choice-btn.selected');
        const errorEl = document.getElementById('selectOcorrenciasError');
        if (selectedBtns.length === 0) {
            errorEl.textContent = 'Você deve selecionar pelo menos uma ocorrência.';
            errorEl.classList.remove('hidden');
            return;
        }
        const selectedIds = Array.from(selectedBtns).map(btn => btn.dataset.id);
        if (currentCardForSelection) {
            currentCardForSelection.dataset.selectedIds = selectedIds.join(',');
            currentCardForSelection.classList.add('selected');
            checkSelectionAndToggleButtons();
        }
        closeModal('selectOcorrenciasModal');
        currentCardForSelection = null;
    }

    window.closeAndCancelSelection = () => {
        closeModal('selectOcorrenciasModal');
        currentCardForSelection = null;
    };

    function checkSelectionAndToggleButtons() {
        document.querySelectorAll('.city-group').forEach(group => {
            const city = group.dataset.city;
            const selectedItems = group.querySelectorAll('.ocorrencia-item.selected:not(.hidden)');
            const btn = group.querySelector(`.atribuir-cidade-btn[data-city="${city}"]`);
            if (btn) btn.classList.toggle('hidden', selectedItems.length === 0);
        });
    }

    window.handleMultiAssignClick = async function (button) {
        const city = button.dataset.city;
        const group = document.querySelector(`.city-group[data-city="${city}"]`);
        const selectedItemsElements = group.querySelectorAll('.ocorrencia-item.selected:not(.hidden)');
        let finalIdsToAssign = [];
        selectedItemsElements.forEach(itemEl => {
            if (itemEl.dataset.selectedIds) {
                finalIdsToAssign.push(...itemEl.dataset.selectedIds.split(','));
            }
        });
        currentItemsToAssign = finalIdsToAssign.map(id => findOcorrenciaById(id)).filter(Boolean);
        if (currentItemsToAssign.length === 0) return;

        document.getElementById('assignModalInfo').innerHTML = `<p><strong>${currentItemsToAssign.length} ocorrência(s) selecionada(s) em ${city}.</strong></p>`;
        ['assignInicioReparo', 'assignFimReparo'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('assignErrorMessage').classList.add('hidden');

        const tecnicosContainer = document.getElementById('assignTecnicosContainer');
        const veiculosContainer = document.getElementById('assignVeiculosContainer');
        tecnicosContainer.innerHTML = 'A carregar...';
        veiculosContainer.innerHTML = 'A carregar...';
        openModal('assignModal');

        const [tecnicosData, veiculosData] = await Promise.all([
            fetch('API/get_tecnicos.php').then(res => res.json()),
            fetch('API/get_veiculos.php').then(res => res.json())
        ]);

        const createChoiceButtons = (container, data, idField, nameField, placaField = null) => {
            container.innerHTML = '';
            data.forEach(item => {
                const btn = document.createElement('button');
                btn.className = 'choice-btn';
                btn.dataset.id = item[idField];
                btn.textContent = placaField ? `${item[nameField]} (${item[placaField]})` : item[nameField];
                btn.onclick = () => btn.classList.toggle('selected');
                container.appendChild(btn);
            });
        };

        if (tecnicosData.success) createChoiceButtons(tecnicosContainer, tecnicosData.tecnicos, 'id_tecnico', 'nome');
        if (veiculosData.length > 0) createChoiceButtons(veiculosContainer, veiculosData, 'id_veiculo', 'nome', 'placa');
    }

    window.saveAssignment = async function () {
        const saveBtn = document.getElementById('saveAssignmentBtn');
        const assignErrorMessage = document.getElementById('assignErrorMessage');
        const inicioReparo = document.getElementById('assignInicioReparo').value;
        const fimReparo = document.getElementById('assignFimReparo').value;
        const selectedTecnicos = Array.from(document.querySelectorAll('#assignTecnicosContainer .choice-btn.selected')).map(btn => btn.dataset.id);
        const selectedVeiculos = Array.from(document.querySelectorAll('#assignVeiculosContainer .choice-btn.selected')).map(btn => btn.dataset.id);

        assignErrorMessage.classList.add('hidden');
        if (!inicioReparo || !fimReparo) { assignErrorMessage.textContent = 'As datas são obrigatórias.'; assignErrorMessage.classList.remove('hidden'); return; }
        if (selectedTecnicos.length === 0) { assignErrorMessage.textContent = 'Selecione um técnico.'; assignErrorMessage.classList.remove('hidden'); return; }
        if (selectedVeiculos.length === 0) { assignErrorMessage.textContent = 'Selecione um veículo.'; assignErrorMessage.classList.remove('hidden'); return; }

        saveBtn.disabled = true;
        saveBtn.querySelector('span').textContent = 'Salvando...';
        saveBtn.querySelector('.spinner').classList.remove('hidden');

        try {
            const response = await fetch('API/atribuir_tecnicos_manutencao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    idsManutencao: currentItemsToAssign.map(item => item.id_manutencao),
                    dataInicio: inicioReparo, dataFim: fimReparo,
                    idsTecnicos: selectedTecnicos, idsVeiculos: selectedVeiculos
                })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            assignErrorMessage.textContent = 'Atribuído com sucesso!';
            assignErrorMessage.style.color = '#155724';
            assignErrorMessage.classList.remove('hidden');
            setTimeout(() => { closeModal('assignModal'); fetchData(); }, 2000);
        } catch (error) {
            assignErrorMessage.textContent = error.message;
            assignErrorMessage.classList.remove('hidden');
            saveBtn.disabled = false;
            saveBtn.querySelector('span').textContent = 'Salvar Atribuição';
            saveBtn.querySelector('.spinner').classList.add('hidden');
        }
    }

    window.handleCancelClick = function (allIds, isGrouped, event) {
        event.stopPropagation();
        if (isGrouped) {
            openCancelSelectionModal(allIds);
        } else {
            openConfirmationModal(allIds, 'cancelado');
        }
    };

    function openCancelSelectionModal(allIds) {
        const ids = allIds.split(',');
        const ocorrencias = ids.map(id => findOcorrenciaById(id)).filter(Boolean);
        if (ocorrencias.length === 0) return;

        const firstItem = ocorrencias[0];
        document.getElementById('cancelSelectionModalInfo').innerHTML = `<p><strong>Equipamento:</strong> ${firstItem.nome_equip} - ${firstItem.referencia_equip}</p>`;

        const container = document.getElementById('cancelOcorrenciasContainer');
        container.innerHTML = '';
        ocorrencias.forEach(ocor => {
            const btn = document.createElement('button');
            btn.className = 'choice-btn';
            btn.dataset.id = ocor.id_manutencao;
            btn.textContent = ocor.ocorrencia_reparo;
            btn.onclick = () => btn.classList.toggle('selected');
            container.appendChild(btn);
        });

        openModal('cancelSelectionModal');
    }

    window.executeMultiCancel = async function () {
        const saveBtn = document.getElementById('confirmCancelBtn');
        const errorEl = document.getElementById('cancelSelectionError');
        const selectedBtns = document.querySelectorAll('#cancelOcorrenciasContainer .choice-btn.selected');

        if (selectedBtns.length === 0) {
            errorEl.textContent = 'Selecione pelo menos uma ocorrência para cancelar.';
            errorEl.classList.remove('hidden');
            return;
        }

        errorEl.classList.add('hidden');
        saveBtn.disabled = true;
        saveBtn.querySelector('span').textContent = 'Cancelando...';
        saveBtn.querySelector('.spinner').classList.remove('hidden');

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_status_batch',
                    ids: Array.from(selectedBtns).map(btn => btn.dataset.id),
                    status: 'cancelado'
                })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            document.getElementById('cancelFooterButtons').classList.add('hidden');
            errorEl.textContent = 'Cancelado(s) com sucesso!';
            errorEl.style.color = '#155724';
            errorEl.classList.remove('hidden');
            setTimeout(() => { closeModal('cancelSelectionModal'); fetchData(); }, 2000);

        } catch (error) {
            errorEl.textContent = `Erro: ${error.message}`;
            errorEl.classList.remove('hidden');
            saveBtn.disabled = false;
            saveBtn.querySelector('span').textContent = 'Confirmar Cancelamento';
            saveBtn.querySelector('.spinner').classList.add('hidden');
        }
    }

    window.openEditOcorrenciaModal = function (idsToEdit, event) {
        event.stopPropagation();
        const ids = idsToEdit.split(',');
        if (ids.length === 0) return;

        const ocorrencias = ids.map(id => findOcorrenciaById(id)).filter(Boolean);
        if (ocorrencias.length === 0) {
            console.error("Não foi possível encontrar dados para os IDs de edição.");
            return;
        }

        const firstItem = ocorrencias[0];
        currentEditingItem = {
            nome_equip: firstItem.nome_equip,
            referencia_equip: firstItem.referencia_equip,
            ocorrencias_detalhadas: ocorrencias
        };

        document.getElementById('editOcorrenciaModalInfo').innerHTML = `<p><strong>Equipamento:</strong> ${currentEditingItem.nome_equip} - ${currentEditingItem.referencia_equip}</p>`;
        const modalBody = document.querySelector('#editOcorrenciaModal .modal-body');
        modalBody.querySelectorAll('.dynamic-form-group').forEach(group => group.remove());

        const originalTextareaGroup = document.getElementById('editOcorrenciaTextarea').parentElement;

        if (currentEditingItem.ocorrencias_detalhadas.length > 1) {
            originalTextareaGroup.classList.add('hidden');
            currentEditingItem.ocorrencias_detalhadas.forEach((ocor, index) => {
                const formGroup = document.createElement('div');
                formGroup.className = 'form-group dynamic-form-group';
                formGroup.innerHTML = `
                    <label for="editOcorrenciaTextarea_${ocor.id_manutencao}">Ocorrência #${index + 1}</label>
                    <textarea id="editOcorrenciaTextarea_${ocor.id_manutencao}" data-id-manutencao="${ocor.id_manutencao}" rows="3">${ocor.ocorrencia_reparo}</textarea>
                `;
                modalBody.appendChild(formGroup);
            });
        } else {
            originalTextareaGroup.classList.remove('hidden');
            document.getElementById('editOcorrenciaTextarea').value = currentEditingItem.ocorrencias_detalhadas[0].ocorrencia_reparo;
        }

        openModal('editOcorrenciaModal');
    }

    window.saveOcorrenciaUpdate = async function () {
        const saveBtn = document.querySelector('#editOcorrenciaModal .btn-primary');
        const originalBtnText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Salvando...';

        let dataToSend;
        const dynamicTextareas = document.querySelectorAll('#editOcorrenciaModal .dynamic-form-group textarea');

        if (dynamicTextareas.length > 0) {
            let hasEmpty = false;
            const updates = Array.from(dynamicTextareas).map(ta => {
                if (!ta.value.trim()) hasEmpty = true;
                return { id_manutencao: ta.dataset.idManutencao, ocorrencia_reparo: ta.value };
            });
            if (hasEmpty) {
                alert('A descrição da ocorrência não pode ficar em branco.');
                saveBtn.disabled = false;
                saveBtn.textContent = originalBtnText;
                return;
            }
            dataToSend = { action: 'edit_ocorrencia_batch', updates: updates };
        } else {
            const newOcorrenciaText = document.getElementById('editOcorrenciaTextarea').value;
            if (!newOcorrenciaText.trim()) {
                alert('A descrição da ocorrência não pode ficar em branco.');
                saveBtn.disabled = false;
                saveBtn.textContent = originalBtnText;
                return;
            }
            dataToSend = {
                action: 'edit_ocorrencia',
                id_manutencao: currentEditingItem.ocorrencias_detalhadas[0].id_manutencao,
                ocorrencia_reparo: newOcorrenciaText
            };
        }

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                saveBtn.textContent = 'Salvo!';
                setTimeout(() => {
                    closeModal('editOcorrenciaModal');
                    fetchData();
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalBtnText;
                }, 1500);
            } else {
                throw new Error(result.message || 'Erro ao salvar alteração');
            }
        } catch (error) {
            alert('Erro: ' + error.message);
            saveBtn.disabled = false;
            saveBtn.textContent = originalBtnText;
        }
    }

    window.openConfirmationModal = function (id, status) {
        document.getElementById('confirmationModalTitle').textContent = 'Cancelar Ocorrência';
        document.getElementById('confirmationModalText').textContent = 'Tem certeza que deseja cancelar esta ocorrência?';
        document.getElementById('confirmActionButton').onclick = () => executeStatusChange(id, status);
        openModal('confirmationModal');
    };

    async function executeStatusChange(id, status) {
        const confirmFooter = document.getElementById('confirmationFooter');
        const confirmMessage = document.getElementById('confirmationMessage');
        const confirmBtn = document.getElementById('confirmActionButton');
        confirmBtn.disabled = true;

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', id: id, status: status, origem: 'manutencao' })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            confirmFooter.classList.add('hidden');
            confirmMessage.textContent = 'Ocorrência cancelada com sucesso!';
            confirmMessage.style.color = '#155724';
            confirmMessage.classList.remove('hidden');
            setTimeout(() => { closeModal('confirmationModal'); fetchData(); }, 2000);

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

    // Primeiro, eu pego a referência do botão que criei no HTML.
    const btnVoltarAoTopo = document.getElementById("btnVoltarAoTopo");

    // Agora, eu digo à janela do navegador para "escutar" o evento de rolagem (scroll).
    window.onscroll = function() {
        // Chamo a minha função que decide se o botão deve aparecer ou não.
        controlarVisibilidadeBotao();
    };

    function controlarVisibilidadeBotao() {
        // Se eu rolei mais de 20px para baixo a partir do topo da página...
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            // ...eu mostro o botão.
            btnVoltarAoTopo.style.display = "block";
        } else {
            // ...senão, eu escondo o botão.
            btnVoltarAoTopo.style.display = "none";
        }
    }

    // Por último, eu adiciono a ação que acontece quando eu clico no botão.
    btnVoltarAoTopo.addEventListener('click', function() {
        // Eu mando a página de volta para o topo de forma suave.
        window.scrollTo({top: 0, behavior: 'smooth'});
    });

});
