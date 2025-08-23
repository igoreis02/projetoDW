document.addEventListener('DOMContentLoaded', function () {
    // --- REFERÊNCIAS AOS ELEMENTOS ---
    const actionButtons = document.querySelectorAll('.action-btn');
    const filterContainer = document.getElementById('filterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const loadingMessage = document.getElementById('loadingMessage');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const searchInput = document.getElementById('searchInput');

    // --- VARIÁVEIS DE ESTADO ---
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null; // Armazena todos os dados vindos do backend
    let currentItemsToAssign = [];
    let currentEditingItem = null;
    let updateInterval; // Variável para o intervalo de atualização

    // --- LÓGICA DE BUSCA E RENDERIZAÇÃO ---

    /**
     * Busca os dados no backend. Pode ser uma carga inicial ou uma verificação de atualização.
     * @param {boolean} isUpdate - Se true, a função age como uma verificação silenciosa.
     */
    async function fetchData(isUpdate = false) {
        // Mostra o spinner apenas na carga inicial, não nas atualizações em segundo plano
        if (!isUpdate) {
            loadingMessage.classList.remove('hidden');
        }
        
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Validação para garantir que a data final não é anterior à inicial
        if (endDate && startDate && endDate < startDate) {
            console.warn("A data final não pode ser anterior à data de início. Ajustando...");
            endDateInput.value = startDate;
            // A busca de dados continuará com a data corrigida na próxima chamada.
            // Para uma resposta imediata, poderíamos chamar fetchData() novamente aqui, mas vamos deixar o fluxo natural.
        }

        const params = new URLSearchParams({
            data_inicio: startDate,
            data_fim: endDate || startDate // Garante que a data fim seja enviada mesmo que vazia
        });

        try {
            const response = await fetch(`get_ocorrencias_pendentes.php?${params.toString()}`);
            const result = await response.json();

            // Gera uma "assinatura" dos dados para comparar se houve mudança
            const newSignature = JSON.stringify(result.data);
            const oldSignature = JSON.stringify(allData);
            
            // Se for uma atualização e os dados mudaram, atualiza a tela
            if (isUpdate) {
                if (newSignature !== oldSignature) {
                    console.log("Novas ocorrências detectadas. Atualizando a lista.");
                    allData = result.data;
                    applyFiltersAndRender(); // Re-renderiza com os novos dados e filtros atuais
                }
            } else { // Se for a carga inicial
                loadingMessage.classList.add('hidden');
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
                loadingMessage.classList.add('hidden');
                console.error('Erro ao buscar dados:', error);
                ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
            }
        }
    }

    /**
     * NOVA FUNÇÃO: Inicia a verificação automática de novas ocorrências.
     */
    function startAutoUpdate() {
        if (updateInterval) clearInterval(updateInterval); // Limpa qualquer intervalo anterior
        // A cada 30 segundos, chama fetchData no modo de atualização (silencioso)
        updateInterval = setInterval(() => fetchData(true), 30000); 
    }

    /**
     * Aplica os filtros de tipo, pesquisa e cidade sobre os dados já carregados.
     */
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
        } else if (cityGroups.length === 0 && !loadingMessage.classList.contains('hidden')) {
             // Não faz nada se ainda estiver carregando
        } else if (cityGroups.length === 0) {
             ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência pendente encontrada para os filtros selecionados.</p>`;
        }

        checkSelectionAndToggleButtons();
    }

    // --- EVENT LISTENERS ---

    actionButtons.forEach(button => {
        button.addEventListener('click', () => {
            actionButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            activeType = button.dataset.type;
            applyFiltersAndRender();
        });
    });

    // --- LÓGICA DE VALIDAÇÃO DE DATA ---
    startDateInput.addEventListener('change', () => {
        // Define a data mínima para o campo de data final
        endDateInput.min = startDateInput.value;
        // Se a data final for anterior à nova data de início, ajusta
        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            endDateInput.value = startDateInput.value;
        }
        fetchData();
    });

    endDateInput.addEventListener('change', () => {
         // Validação extra caso o usuário digite a data manualmente
        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            alert("A data final não pode ser anterior à data de início.");
            endDateInput.value = startDateInput.value;
        }
        fetchData();
    });
    
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
    
    // --- O RESTANTE DO SEU CÓDIGO (LÓGICA DOS MODAIS) ---

    // (O código para `createOcorrenciaHTML`, `formatDate`, modais, etc., permanece o mesmo)
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
            const response = await fetch('atribuir_tecnicos_manutencao.php', {
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
                confirmFooter.classList.add('hidden');
                confirmMessage.textContent = 'Ocorrência cancelada com sucesso!';
                confirmMessage.style.color = '#155724';
                confirmMessage.classList.remove('hidden');
                setTimeout(() => {
                    closeModal('confirmationModal');
                    fetchData();
                }, 2000);
            } else {
                alert('Erro ao alterar o status: ' + result.message);
            }
        } catch (error) {
            alert('Erro de comunicação com o servidor.');
        }finally { 
             confirmBtn.disabled = false;
        }
    }
     const assignErrorMessage = document.getElementById('assignErrorMessage');
    document.getElementById('assignInicioReparo').addEventListener('input', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignFimReparo').addEventListener('input', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignTecnicosContainer').addEventListener('click', () => assignErrorMessage.classList.add('hidden'));
    document.getElementById('assignVeiculosContainer').addEventListener('click', () => assignErrorMessage.classList.add('hidden'));

    // --- INICIALIZAÇÃO ---
    fetchData(); // Carga inicial dos dados
    startAutoUpdate(); // Inicia a verificação automática
});