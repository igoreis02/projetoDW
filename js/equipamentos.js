document.addEventListener('DOMContentLoaded', () => {

    // --- SEÇÃO DE ELEMENTOS DOM ---
    const addEquipmentBtn = document.getElementById('addEquipmentBtn');
    const addEquipmentModal = document.getElementById('addEquipmentModal');
    const closeAddEquipmentModal = document.getElementById('closeAddEquipmentModal');
    const cancelAddEquipmentButton = document.getElementById('cancelAddEquipmentButton');
    const addEquipmentForm = document.getElementById('addEquipmentForm');
    const addEquipmentMessage = document.getElementById('addEquipmentMessage');
    const addFormButtonsContainer = document.getElementById('add-form-buttons');

    const editEquipmentModal = document.getElementById('editEquipmentModal');
    const closeEditEquipmentModal = document.getElementById('closeEditEquipmentModal');
    const cancelEditEquipmentButton = document.getElementById('cancelEditEquipmentButton');
    const editEquipmentForm = document.getElementById('editEquipmentForm');
    const editEquipmentMessage = document.getElementById('editEquipmentMessage');
    const equipmentListContainer = document.getElementById('containerListaEquipamentos');
    const mainLoadingState = document.getElementById('mainLoadingState');
    const campoPesquisa = document.getElementById('campoPesquisa');
    const cityButtonsContainer = document.getElementById('cityButtonsContainer');


    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const backToTopBtn = document.getElementById('backToTopBtn');



    // --- VARIÁVEIS DE ESTADO ---
    let allEquipmentData = [];
    let allProvidersData = [];
    let activeCityFilter = 'all';
    // VARIÁVEIS PARA AUTO-UPDATE
    let currentChecksum = null;
    let updateTimeoutId = null;
    const BASE_INTERVAL = 15000; // 15 segundos
    const MAX_INTERVAL = 120000; // 2 minutos
    let currentInterval = BASE_INTERVAL;


    // --- MAPA DE VALIDAÇÃO (SEU CÓDIGO ORIGINAL) ---
    const validationMap = {
        'tipo_equip[]': 'Tipo de Equipamento',
        'nome_equip': 'Nome',
        'referencia_equip': 'Referência',
        'status': 'Status',
        'qtd_faixa': 'Quantidade de Faixas',
        'km': 'Velocidade (KM/h)',
        'sentido': 'Sentido',
        'num_instrumento': 'Nº Instrumento',
        'dt_afericao': 'Data Aferição',
        'id_cidade': 'Cidade',
        'logradouro': 'Logradouro',
        'bairro': 'Bairro',
        'id_provedor': 'Provedor',
        'cep': 'CEP'
    };

    // --- FUNÇÕES UTILITÁRIAS (SEU CÓDIGO ORIGINAL) ---
    window.showMessage = function (element, msg, type) {
        element.textContent = msg;
        element.className = `message ${type}`;
        element.classList.remove('hidden');
    };

    window.hideMessage = function (element) {
        element.classList.add('hidden');
        element.textContent = '';
    };
    function handleCityChange(event) {
        const cityId = event.target.value;
        const form = event.target.closest('form');
        const providerSelect = form.querySelector('select[name="id_provedor"]');

        if (!cityId || allProvidersData.length === 0) {
            providerSelect.value = '';
            return;
        }

        // Encontra o primeiro provedor que corresponde à cidade selecionada
        const matchingProvider = allProvidersData.find(p => p.id_cidade == cityId);

        if (matchingProvider) {
            providerSelect.value = matchingProvider.id_provedor;
        } else {
            providerSelect.value = ''; // Se não encontrar, reseta o seletor
        }
    }

    function toggleLoadingState(spinnerId, saveButtonId, cancelButtonId, show) {
        const saveButton = document.getElementById(saveButtonId);
        const cancelButton = document.getElementById(cancelButtonId);
        if (saveButton) {
            saveButton.disabled = show;
            if (show) {
                if (saveButtonId === 'saveAddEquipmentButton') {
                    saveButton.innerHTML = 'Salvando Equipamento <span id="addEquipmentSpinner" class="loading-spinner is-active"></span>';
                } else if (saveButtonId === 'saveEditEquipmentButton') {
                    saveButton.innerHTML = 'Salvando Alterações <span id="editEquipmentSpinner" class="loading-spinner is-active"></span>';
                }
            } else {
                if (saveButtonId === 'saveAddEquipmentButton') {
                    saveButton.innerHTML = 'Salvar Equipamento <span id="addEquipmentSpinner" class="loading-spinner"></span>';
                } else if (saveButtonId === 'saveEditEquipmentButton') {
                    saveButton.innerHTML = 'Salvar Alterações <span id="editEquipmentSpinner" class="loading-spinner"></span>';
                }
            }
        }
        if (cancelButton) cancelButton.disabled = show;
    }

    // --- FUNÇÕES DE LÓGICA DE NEGÓCIO ---

    function populateCitySelect(selectElement, cities) {
        const currentVal = selectElement.value; // Salva o valor atual para tentar manter a seleção
        selectElement.innerHTML = '<option value="">Selecione a Cidade</option>';
        if (cities) {
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id_cidade;
                option.textContent = city.nome;
                if (city.id_cidade == currentVal) {
                    option.selected = true;
                }
                selectElement.appendChild(option);
            });
        }
    }

    function updateCityDropdownForForm(formId) {
        const form = document.getElementById(formId);
        const citySelect = form.querySelector('select[name="id_cidade"]');
        const selectedTypes = Array.from(form.querySelectorAll('input[name="tipo_equip[]"]:checked')).map(cb => cb.value);

        // Se "SEMÁFORO" estiver selecionado, usa a lista de cidades para semáforos
        if (selectedTypes.includes('SEMÁFORO')) {
            populateCitySelect(citySelect, CIDADES_SEMAFORO);
        } else {
            // Caso contrário, usa a lista padrão (com radares)
            populateCitySelect(citySelect, CIDADES_PADRAO);
        }
    }
    
    // FUNÇÃO ATUALIZADA COM AS NOVAS REGRAS
    function toggleConditionalFields(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const selectedTypes = Array.from(form.querySelectorAll('input[name="tipo_equip[]"]:checked')).map(cb => cb.value);
        const prefix = formId.includes('add') ? 'add' : 'edit';

        // Containers e Elementos
        const specificContainer = form.querySelector(`#${prefix}-specific-fields-container`);
        const afericaoContainer = form.querySelector(`#${prefix}-afericao-fields-container`);
        const radarLombadaContainer = form.querySelector(`#${prefix}-radar-lombada-fields`);
        const dateFieldsContainer = form.querySelector(`#${prefix}-date-fields-container`);
        const providerSelect = form.querySelector(`[name="id_provedor"]`);
        const providerLabel = providerSelect ? form.querySelector(`label[for="${providerSelect.id}"]`) : null;
        const cepInput = form.querySelector(`[name="cep"]`);
        const cepLabel = cepInput ? form.querySelector(`label[for="${cepInput.id}"]`) : null;
        const kmInput = form.querySelector(`[name="km"]`);
        const kmLabel = kmInput ? form.querySelector(`label[for="${kmInput.id}"]`) : null;

        // Flags de condição
        const isSemaforo = selectedTypes.includes('SEMÁFORO');
        const needsAfericao = selectedTypes.includes('RADAR FIXO') || selectedTypes.includes('LOMBADA ELETRÔNICA');
        const needsSpecifics = selectedTypes.some(t => ['RADAR FIXO', 'LOMBADA ELETRÔNICA', 'MONITOR DE SEMÁFORO', 'LAP', 'EDUCATIVO'].includes(t));
        const hidesKm = selectedTypes.includes('LAP') || selectedTypes.includes('MONITOR DE SEMÁFORO');

        // Lógica de visibilidade
        specificContainer.classList.toggle('hidden', !needsSpecifics);
        afericaoContainer.classList.toggle('hidden', !needsAfericao);
        radarLombadaContainer.classList.toggle('hidden', !needsAfericao);
        dateFieldsContainer.classList.toggle('hidden', !needsSpecifics && !needsAfericao);

        if (providerSelect && providerLabel) {
            providerSelect.classList.toggle('hidden', isSemaforo);
            providerLabel.classList.toggle('hidden', isSemaforo);
        }
        if (cepInput && cepLabel) {
            cepInput.classList.toggle('hidden', isSemaforo);
            cepLabel.classList.toggle('hidden', isSemaforo);
        }

        if (kmInput && kmLabel) {
            kmLabel.classList.toggle('hidden', hidesKm || !needsSpecifics);
            kmInput.classList.toggle('hidden', hidesKm || !needsSpecifics);
        }
    }

    // NOVA FUNÇÃO para o status no modal de edição
    function toggleStatusDateFields() {
        const statusSelect = document.getElementById('editEquipmentStatus');
        const remanejadoContainer = document.getElementById('edit-remanejado-date-container');
        const inativoContainer = document.getElementById('edit-inativo-date-container');
        
        const selectedStatus = statusSelect.value;
        
        remanejadoContainer.classList.toggle('hidden', selectedStatus !== 'remanejado');
        inativoContainer.classList.toggle('hidden', selectedStatus !== 'inativo');
    }

    function createDynamicValidationMap(form) {
        const dynaValidationMap = { ...validationMap };
        const selectedTypes = Array.from(form.querySelectorAll('input[name="tipo_equip[]"]:checked')).map(cb => cb.value);
    
        const needsAfericao = selectedTypes.includes('RADAR FIXO') || selectedTypes.includes('LOMBADA ELETRÔNICA');
        const requiresSpecifics = selectedTypes.some(t => ['RADAR FIXO', 'LOMBADA ELETRÔNICA', 'MONITOR DE SEMÁFORO', 'LAP'].includes(t));
        const hidesKm = selectedTypes.includes('LAP') || selectedTypes.includes('MONITOR DE SEMÁFORO');
        const isSemaforo = selectedTypes.includes('SEMÁFORO');
    
        if (!requiresSpecifics) {
            delete dynaValidationMap['qtd_faixa'];
            delete dynaValidationMap['sentido'];
            delete dynaValidationMap['km'];
        } else if (hidesKm) {
            delete dynaValidationMap['km'];
        }
    
        if (!needsAfericao) {
            delete dynaValidationMap['num_instrumento'];
            delete dynaValidationMap['dt_afericao'];
        }
        
        if (isSemaforo) {
            delete dynaValidationMap['id_provedor'];
            delete dynaValidationMap['cep'];
        }
    
        return dynaValidationMap;
    }
    
    // --- FUNÇÃO DE VALIDAÇÃO DE FORMULÁRIO ---
    function validateForm(form, currentValidationMap) {
        for (const name in currentValidationMap) {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field) continue;

            if (field.closest('.hidden')) {
                continue;
            }

            const label = currentValidationMap[name];

            if (name === 'tipo_equip[]') {
                if (form.querySelectorAll('input[name="tipo_equip[]"]:checked').length === 0) {
                    return `O campo '${label}' é obrigatório.`;
                }
            } else {
                if (!field.value || field.value.trim() === '') {
                    return `O campo '${label}' é obrigatório.`;
                }
            }
        }
        return true; 
    }

    // --- FUNÇÃO PARA LIMPAR MENSAGENS DE ERRO ---
    function setupValidationListeners(form, messageElement) {
        form.querySelectorAll('input, select, textarea').forEach(input => {
            const eventType = (input.type === 'checkbox' || input.tagName.toLowerCase() === 'select') ? 'change' : 'input';
            input.addEventListener(eventType, () => {
                if (messageElement.classList.contains('error')) {
                    hideMessage(messageElement);
                }
            });
        });
    }

    async function scheduleNextCheck() {
        if (updateTimeoutId) {
            clearTimeout(updateTimeoutId);
        }
        try {
            const checkResponse = await fetch('API/check_updates.php?context=equipamentos');
            const checkResult = await checkResponse.json();

            if (checkResult.success && checkResult.checksum !== currentChecksum) {
                console.log('Novas atualizações de equipamentos detectadas. Recarregando...');
                await fetchAndRenderEquipments();
                currentInterval = BASE_INTERVAL;
                console.log('Intervalo de verificação de equipamentos resetado.');
            } else {
                currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
                console.log(`Nenhuma atualização. Próxima verificação de equipamentos em ${currentInterval / 1000}s.`);
            }
        } catch (error) {
            console.error('Erro no ciclo de verificação de atualizações:', error);
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
        } finally {
            updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
        }
    }

    // NOVA FUNÇÃO para buscar técnicos
    async function fetchTecnicosForSelect() {
        const addSelect = document.getElementById('add_id_tecnico_instalacao');
        const editSelect = document.getElementById('edit_id_tecnico_instalacao');
        
        try {
            const response = await fetch('API/get_tecnicos.php');
            const data = await response.json();
            if (data.success) {
                const defaultOption = '<option value="" disabled>Selecione...</option>';
                const options = data.tecnicos.map(tec => `<option value="${tec.id_usuario}">${tec.nome}</option>`).join('');
                addSelect.innerHTML = defaultOption + options;
                editSelect.innerHTML = defaultOption + options;
            }
        } catch (error) {
            console.error("Erro ao buscar técnicos:", error);
            addSelect.innerHTML = '<option value="">Erro ao carregar</option>';
            editSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        }
    }

    async function fetchProvidersForSelect() {
        const selectProvider = document.getElementById('equipmentProvider');
        const editSelectProvider = document.getElementById('editEquipmentProvider');
        const loadingOption = '<option value="">Carregando...</option>';
        selectProvider.innerHTML = loadingOption;
        editSelectProvider.innerHTML = loadingOption;

        try {
            const response = await fetch('API/get_provedores_select.php');
            const data = await response.json();
            const defaultOption = '<option value="">Selecione o Provedor</option>';
            if (data.success) {
                allProvidersData = data.provedores;
                const providerOptions = data.provedores.map(p => `<option value="${p.id_provedor}">${p.nome_prov}</option>`).join('');
                selectProvider.innerHTML = defaultOption + providerOptions;
                editSelectProvider.innerHTML = defaultOption + providerOptions;
            } else {
                throw new Error('Falha ao carregar provedores');
            }
        } catch (error) {
            console.error('Erro ao buscar provedores:', error);
            const errorOption = '<option value="">Erro ao carregar</option>';
            selectProvider.innerHTML = errorOption;
            editSelectProvider.innerHTML = errorOption;
        }
    }

    async function fetchAndRenderEquipments() {
        if (!currentChecksum) {
            mainLoadingState.style.display = 'flex';
            equipmentListContainer.innerHTML = '';
        }
        try {
            const response = await fetch('API/get_equipamento-dados.php');
            const data = await response.json();
            if (data.success) {
                currentChecksum = data.checksum;
                allEquipmentData = data.equipamentos;
                applyFilters();
            } else {
                equipmentListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                currentChecksum = data.checksum;
            }
        } catch (error) {
            console.error('Erro ao buscar equipamentos:', error);
            equipmentListContainer.innerHTML = `<p class="message error">Ocorreu um erro. Tente novamente.</p>`;
        } finally {
            mainLoadingState.style.display = 'none';
        }
    }

    function getEquipmentTypeOrder(typeString) {
        if (!typeString) return 99;
        const types = typeString.split(',').map(t => t.trim());
        const order = { 'CCO': 1, 'RADAR FIXO': 2, 'LOMBADA ELETRÔNICA': 2, 'MONITOR DE SEMÁFORO': 3, 'VÍDEO MONITORAMENTO': 4, 'DOME': 5, 'LAP': 6, 'EDUCATIVO': 7, 'SEMÁFORO': 98 };
        return Math.min(...types.map(t => order[t] || 99));
    }

    function customSort(a, b) {
        const typeOrderA = getEquipmentTypeOrder(a.tipo_equip);
        const typeOrderB = getEquipmentTypeOrder(b.tipo_equip);
        if (typeOrderA !== typeOrderB) {
            return typeOrderA - typeOrderB;
        }

        return a.nome_equip.localeCompare(b.nome_equip, undefined, { numeric: true });
    }

    function applyFilters() {
        const searchTerm = campoPesquisa.value.toLowerCase();
        let filteredData = allEquipmentData.filter(equip => {
            const matchesCity = activeCityFilter === 'all' || (equip.cidade && equip.cidade.toLowerCase() === activeCityFilter.toLowerCase());
            const matchesSearch = !searchTerm || (equip.nome_equip && equip.nome_equip.toLowerCase().includes(searchTerm)) ||
                (equip.referencia_equip && equip.referencia_equip.toLowerCase().includes(searchTerm)) ||
                (equip.logradouro && equip.logradouro.toLowerCase().includes(searchTerm));
            return matchesCity && matchesSearch;
        });

        filteredData.sort(customSort);
        renderEquipmentList(filteredData);
    }

    function activateDatePickers() {
        document.querySelectorAll('.custom-date-input').forEach(wrapper => {
            wrapper.addEventListener('click', () => {
                const input = wrapper.querySelector('input[type="date"]');
                try {
                    input.showPicker();
                } catch (error) {
                    console.log("Seu navegador não suporta showPicker(), clique diretamente no campo.");
                }
            });
        });
    }

    function renderEquipmentList(equipments) {
        equipmentListContainer.innerHTML = '';
        if (equipments.length === 0) {
            equipmentListContainer.innerHTML = '<p class="message">Nenhum equipamento encontrado.</p>';
            return;
        }
        const equipmentsByCity = equipments.reduce((acc, equip) => {
            const city = equip.cidade || 'Sem Cidade';
            (acc[city] = acc[city] || []).push(equip);
            return acc;
        }, {});

        Object.keys(equipmentsByCity).sort().forEach(city => {
            const citySection = document.createElement('div');
            citySection.classList.add('city-section');
            citySection.innerHTML = `<h3>${city}</h3><div class="equipment-grid"></div>`;
            const equipmentGrid = citySection.querySelector('.equipment-grid');
            equipmentsByCity[city].forEach(equip => {
                const statusClass = `status-${(equip.status || '').toLowerCase()}`;
                const statusDisplay = (equip.status || 'N/A').charAt(0).toUpperCase() + (equip.status || 'N/A').slice(1);
                const formatDate = (date) => (!date || date === '0000-00-00') ? 'N/A' : new Date(date + 'T00:00:00').toLocaleDateString('pt-BR');

                const types = equip.tipo_equip ? equip.tipo_equip.split(',').map(t => t.trim()) : [];
                const typesThatHideDetails = ['CCO', 'DOME', 'VÍDEO MONITORAMENTO', 'SEMÁFORO'];
                const shouldShowDetails = types.length > 0 && !types.some(t => typesThatHideDetails.includes(t));

                const enderecoCompleto = `${equip.logradouro || 'N/A'} - ${equip.bairro || 'N/A'}`;

                let locationButton = '';
                if (equip.latitude && equip.longitude) {
                     const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${equip.latitude},${equip.longitude}`;
                    locationButton = `<a href="${mapsUrl}" target="_blank" class="botao-localizacao" title="Ver no mapa">Localização</a>`;
                }

                let tecnicoInfo = '';
                if (equip.nome_tecnico_instalacao) {
                    tecnicoInfo = `<p><strong>Téc. Instalação:</strong> ${equip.nome_tecnico_instalacao}</p>`;
                }

                const item = document.createElement('div');
                item.className = 'item-equipamento';

                item.innerHTML = `
                <div class="item-equipamento-conteudo">
                    <h3>${equip.nome_equip || 'N/A'} - ${equip.referencia_equip || 'N/A'}</h3>
                    <p><strong>Tipo:</strong> ${equip.tipo_equip || 'N/A'}</p>
                    <p><strong>Data Instalação:</strong> ${formatDate(equip.data_instalacao)}</p>
                    ${shouldShowDetails ? `
                        <p><strong>Qtd. Faixa:</strong> ${equip.qtd_faixa || 'N/A'}</p>
                        <p><strong>Sentido:</strong> ${equip.sentido || 'N/A'}</p>
                        <p><strong>Velocidade:</strong> ${equip.Km ? equip.Km + ' km/h' : 'N/A'}</p>
                        ${tecnicoInfo}
                        <p><strong>Nº Instrumento:</strong> ${equip.num_instrumento || 'N/A'}</p>
                        <p><strong>Data Aferição:</strong> ${formatDate(equip.dt_afericao)}</p>
                        <p><strong>Data Vencimento:</strong> ${formatDate(equip.dt_vencimento)}</p>
                    ` : (types.includes('SEMÁFORO') ? tecnicoInfo : '')}
                    <p><strong>Endereço:</strong> ${enderecoCompleto}</p>
                    ${!types.includes('SEMÁFORO') ? `<p><strong>Provedor:</strong> ${equip.nome_prov || 'N/A'}</p>` : ''}
                    <p><strong>Status:</strong> <span class="status-cell ${statusClass}">${statusDisplay}</span></p>
                </div>
                <div class="item-botoes-container">
                    ${locationButton}
                    <button class="botao-editar" data-equipment-id="${equip.id_equipamento}">Editar</button>
                </div>
            `;
                equipmentGrid.appendChild(item);
            });
            equipmentListContainer.appendChild(citySection);
        });
    }

    function findEquipmentById(id) {
        return allEquipmentData.find(equip => equip.id_equipamento == id);
    }

    function openAddEquipmentModal() {
        addEquipmentForm.reset();
        hideMessage(addEquipmentMessage);
        // CORREÇÃO: Botão é resetado para o estado inicial toda vez que o modal abre
        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
        updateCityDropdownForForm('addEquipmentForm'); // Popula com a lista padrão
        toggleConditionalFields('addEquipmentForm');
        addEquipmentModal.classList.add('is-active');
        addFormButtonsContainer.style.display = 'flex';
    }

    function openEditEquipmentModal(equipmentData) {
        if (!equipmentData) return;
        editEquipmentForm.reset();
        editEquipmentForm.querySelectorAll('input[name="tipo_equip[]"]').forEach(cb => { cb.checked = false; });

        // Preenche os campos
        document.getElementById('editEquipmentId').value = equipmentData.id_equipamento;
        document.getElementById('editEnderecoId').value = equipmentData.id_endereco;
        document.getElementById('editEquipmentName').value = equipmentData.nome_equip;
        document.getElementById('editEquipmentReference').value = equipmentData.referencia_equip;
        document.getElementById('editEquipmentStatus').value = equipmentData.status;
        document.getElementById('editEquipmentQtdFaixa').value = equipmentData.qtd_faixa;
        document.getElementById('editEquipmentKm').value = equipmentData.Km;
        document.getElementById('editEquipmentSentido').value = equipmentData.sentido;
        document.getElementById('editEquipmentProvider').value = equipmentData.id_provedor;
        document.getElementById('editEquipmentLogradouro').value = equipmentData.logradouro;
        document.getElementById('editEquipmentBairro').value = equipmentData.bairro;
        document.getElementById('editEquipmentCep').value = equipmentData.cep;
        document.getElementById('editNumInstrumento').value = equipmentData.num_instrumento;
        document.getElementById('editDtAfericao').value = equipmentData.dt_afericao;
        document.getElementById('edit_dt_estudoTec').value = equipmentData.dt_estudoTec;
        document.getElementById('edit_dt_instalacao').value = equipmentData.data_instalacao;
        document.getElementById('edit_dt_fabricacao').value = equipmentData.dt_fabricacao;
        document.getElementById('edit_dt_sinalizacao_adicional').value = equipmentData.dt_sinalizacao_adicional;
        document.getElementById('edit_dt_inicio_processamento').value = equipmentData.dt_inicio_processamento;
        document.getElementById('edit_num_certificado').value = equipmentData.num_certificado;
        document.getElementById('edit_dt_remanejado').value = equipmentData.dt_remanejado;
        document.getElementById('edit_dt_desativado').value = equipmentData.dt_desativado;
        
        const tecnicosIds = equipmentData.id_tecnico_instalacao ? equipmentData.id_tecnico_instalacao.split(',') : [];
        const tecnicosSelect = document.getElementById('edit_id_tecnico_instalacao');
        Array.from(tecnicosSelect.options).forEach(option => {
            option.selected = tecnicosIds.includes(option.value);
        });

        const coordenadas = (equipmentData.latitude && equipmentData.longitude) ? `${equipmentData.latitude}, ${equipmentData.longitude}` : '';
        document.getElementById('editCoordenadas').value = coordenadas;

        const selectedTypes = equipmentData.tipo_equip ? equipmentData.tipo_equip.split(',').map(t => t.trim()) : [];
        editEquipmentForm.querySelectorAll('input[name="tipo_equip[]"]').forEach(cb => {
            cb.checked = selectedTypes.includes(cb.value);
        });
        
        updateCityDropdownForForm('editEquipmentForm'); // Popula a lista de cidades correta
        document.getElementById('editEquipmentCity').value = equipmentData.id_cidade; // Seleciona a cidade atual

        toggleConditionalFields('editEquipmentForm');
        toggleStatusDateFields();
        hideMessage(editEquipmentMessage);
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        editEquipmentModal.classList.add('is-active');
        document.getElementById('edit-form-buttons').style.display = 'flex';
    }

    window.onscroll = function () {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            backToTopBtn.style.display = "block";
        } else {
            backToTopBtn.style.display = "none";
        }
    };

    // --- EVENT LISTENERS ---
    addEquipmentBtn.addEventListener('click', openAddEquipmentModal);
    [closeAddEquipmentModal, cancelAddEquipmentButton].forEach(el => el.addEventListener('click', () => addEquipmentModal.classList.remove('is-active')));
    [closeEditEquipmentModal, cancelEditEquipmentButton].forEach(el => el.addEventListener('click', () => editEquipmentModal.classList.remove('is-active')));

    campoPesquisa.addEventListener('input', applyFilters);

    clearFiltersBtn.addEventListener('click', () => {
        campoPesquisa.value = '';
        activeCityFilter = 'all';
        document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.city-button[data-city="all"]').classList.add('active');
        applyFilters();
    });

    backToTopBtn.addEventListener('click', () => {
        document.body.scrollTop = 0;
        document.documentElement.scrollTop = 0;
    });

    cityButtonsContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('city-button')) {
            document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            activeCityFilter = event.target.dataset.city;
            applyFilters();
        }
    });

    document.getElementById('addEquipmentType').addEventListener('change', () => {
        toggleConditionalFields('addEquipmentForm');
        updateCityDropdownForForm('addEquipmentForm');
    });
    document.getElementById('editEquipmentType').addEventListener('change', () => {
        toggleConditionalFields('editEquipmentForm');
        updateCityDropdownForForm('editEquipmentForm');
    });

    document.getElementById('editEquipmentStatus').addEventListener('change', toggleStatusDateFields);

    equipmentListContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('botao-editar')) {
            openEditEquipmentModal(findEquipmentById(event.target.dataset.equipmentId));
        }
    });

    /*window.addEventListener('click', (event) => {
        if (event.target === addEquipmentModal) addEquipmentModal.classList.remove('is-active');
        if (event.target === editEquipmentModal) editEquipmentModal.classList.remove('is-active');
    });*/

    addEquipmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const dynaMap = createDynamicValidationMap(addEquipmentForm);
        const validationResult = validateForm(addEquipmentForm, dynaMap);
        if (validationResult !== true) {
            showMessage(addEquipmentMessage, validationResult, 'error');
            return;
        }

        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', true);
        try {
            const formData = new FormData(addEquipmentForm);
            const data = Object.fromEntries(formData.entries());
            
            data.tipo_equip = formData.getAll('tipo_equip[]');
            delete data['tipo_equip[]'];
            
            const selectedTecnicos = Array.from(addEquipmentForm.querySelector('#add_id_tecnico_instalacao').selectedOptions).map(option => option.value);
            data.id_tecnico_instalacao = selectedTecnicos;

            const response = await fetch('API/add_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                showMessage(addEquipmentMessage, 'Equipamento adicionado com sucesso!', 'success');
                addFormButtonsContainer.style.display = 'none';
                setTimeout(() => {
                    addEquipmentModal.classList.remove('is-active');
                    fetchAndRenderEquipments();
                }, 1500);
            } else {
                showMessage(addEquipmentMessage, result.message || 'Erro ao adicionar equipamento.', 'error');
                toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
            }
        } catch (error) {
            console.error('Erro ao adicionar equipamento:', error);
            showMessage(addEquipmentMessage, 'Ocorreu um erro de conexão. Tente novamente.', 'error');
            toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
        }
    });

    editEquipmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        
        const dynaMap = createDynamicValidationMap(editEquipmentForm);
        const validationResult = validateForm(editEquipmentForm, dynaMap);
        if (validationResult !== true) {
            showMessage(editEquipmentMessage, validationResult, 'error');
            return;
        }

        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', true);
        try {
            const formData = new FormData(editEquipmentForm);
            const data = Object.fromEntries(formData.entries());
            
            data.tipo_equip = formData.getAll('tipo_equip[]');
            delete data['tipo_equip[]'];
            
            const selectedTecnicos = Array.from(editEquipmentForm.querySelector('#edit_id_tecnico_instalacao').selectedOptions).map(option => option.value);
            data.id_tecnico_instalacao = selectedTecnicos;

            const response = await fetch('API/update_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                showMessage(editEquipmentMessage, 'Equipamento atualizado com sucesso!', 'success');
                document.getElementById('edit-form-buttons').style.display = 'none';
                setTimeout(() => {
                    editEquipmentModal.classList.remove('is-active');
                    fetchAndRenderEquipments();
                }, 1500);
            } else {
                showMessage(editEquipmentMessage, result.message || 'Erro ao atualizar equipamento.', 'error');
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
            }
        } catch (error) {
            showMessage(editEquipmentMessage, 'Ocorreu um erro de conexão. Tente novamente.', 'error');
            toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        }
    });

    document.getElementById('equipmentCity').addEventListener('change', handleCityChange);
    document.getElementById('editEquipmentCity').addEventListener('change', handleCityChange);


    // --- INICIALIZAÇÃO ---
    populateCitySelect(document.getElementById('equipmentCity'), CIDADES_PADRAO);
    populateCitySelect(document.getElementById('editEquipmentCity'), CIDADES_PADRAO);
    fetchProvidersForSelect();
    fetchTecnicosForSelect(); // Busca os técnicos
    fetchAndRenderEquipments().then(() => {
        console.log('Carga inicial de equipamentos completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });

    setupValidationListeners(addEquipmentForm, addEquipmentMessage);
    setupValidationListeners(editEquipmentForm, editEquipmentMessage);
    activateDatePickers();
});