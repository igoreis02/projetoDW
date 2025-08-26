document.addEventListener('DOMContentLoaded', () => {

    // --- SEÇÃO DE ELEMENTOS DOM ---
    const addEquipmentBtn = document.getElementById('addEquipmentBtn');
    const addEquipmentModal = document.getElementById('addEquipmentModal');
    const closeAddEquipmentModal = document.getElementById('closeAddEquipmentModal');
    const cancelAddEquipmentButton = document.getElementById('cancelAddEquipmentButton');
    const addEquipmentForm = document.getElementById('addEquipmentForm');
    const addEquipmentMessage = document.getElementById('addEquipmentMessage');
    const addSpecificFieldsContainer = document.getElementById('add-specific-fields-container');
    const addFormButtonsContainer = document.getElementById('add-form-buttons');

    const editEquipmentModal = document.getElementById('editEquipmentModal');
    const closeEditEquipmentModal = document.getElementById('closeEditEquipmentModal');
    const cancelEditEquipmentButton = document.getElementById('cancelEditEquipmentButton');
    const editEquipmentForm = document.getElementById('editEquipmentForm');
    const editEquipmentMessage = document.getElementById('editEquipmentMessage');
    const editSpecificFieldsContainer = document.getElementById('edit-specific-fields-container');
    const editFormButtonsContainer = document.getElementById('edit-form-buttons');
    const equipmentListContainer = document.getElementById('containerListaEquipamentos');
    const mainLoadingState = document.getElementById('mainLoadingState');
    const campoPesquisa = document.getElementById('campoPesquisa');
    const cityButtonsContainer = document.getElementById('cityButtonsContainer');

    // --- VARIÁVEIS DE ESTADO ---
    let allEquipmentData = [];
    let activeCityFilter = 'all';

    // --- MAPA DE VALIDAÇÃO PARA MENSAGENS DE ERRO CUSTOMIZADAS ---
    const fieldsToValidate = {
        'equipmentType': 'Tipo de Equipamento',
        'equipmentName': 'Nome',
        'equipmentStatus': 'Status',
        'numInstrumento': 'Nº Instrumento',
        'dtAfericao': 'Data Aferição',
        'equipmentCity': 'Cidade',
        'equipmentLogradouro': 'Logradouro',
        'equipmentBairro': 'Bairro',
        'equipmentProvider': 'Provedor',
    };

    // --- FUNÇÕES UTILITÁRIAS ---
    window.showMessage = function (element, msg, type) {
        element.textContent = msg;
        element.className = `message ${type}`;
        element.classList.remove('hidden');
    };

    window.hideMessage = function (element) {
        element.classList.add('hidden');
        element.textContent = '';
    };
    
    // --- NOVA FUNÇÃO DE VALIDAÇÃO CUSTOMIZADA ---
    function validateForm(formElement) {
        for (const fieldId in fieldsToValidate) {
            const input = formElement.querySelector(`#${fieldId}`);
            if (input && !input.value.trim()) {
                const fieldName = fieldsToValidate[fieldId];
                return `O campo '${fieldName}' é obrigatório.`; // Retorna a primeira mensagem de erro encontrada
            }
        }
        return null; // Retorna null se todos os campos estiverem válidos
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

    function toggleSpecificFields(selectElement, containerElement) {
        const selectedType = selectElement.value;
        const typesWithOptions = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA', 'LAP'];
        if (typesWithOptions.includes(selectedType)) {
            containerElement.classList.remove('hidden');
        } else {
            containerElement.classList.add('hidden');
            containerElement.querySelector('input[name="qtd_faixa"]').value = '';
            containerElement.querySelector('input[name="km"]').value = '';
            containerElement.querySelector('input[name="sentido"]').value = '';
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
        mainLoadingState.style.display = 'flex';
        equipmentListContainer.innerHTML = '';

        try {
            const response = await fetch('API/get_equipamento-dados.php');
            const data = await response.json();
            if (data.success) {
                allEquipmentData = data.equipamentos;
                applyFilters();
            } else {
                equipmentListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
            }
        } catch (error) {
            console.error('Erro ao buscar equipamentos:', error);
            equipmentListContainer.innerHTML = `<p class="message error">Ocorreu um erro ao buscar os equipamentos. Tente novamente.</p>`;
        } finally {
            mainLoadingState.style.display = 'none';
        }
    }

    function getEquipmentTypeOrder(type) {
        const order = { 'CCO': 1, 'RADAR FIXO': 2, 'DOME': 3, 'EDUCATIVO': 4, 'LOMBADA': 5, 'LAP': 6 };
        return order[type] || 99;
    }

    function applyFilters() {
        const searchTerm = campoPesquisa.value.toLowerCase();
        let filteredData = allEquipmentData.filter(equip => {
            const matchesCity = activeCityFilter === 'all' || equip.cidade.toLowerCase() === activeCityFilter.toLowerCase();
            const matchesSearch = !searchTerm || (equip.nome_equip && equip.nome_equip.toLowerCase().includes(searchTerm)) ||
                (equip.referencia_equip && equip.referencia_equip.toLowerCase().includes(searchTerm)) ||
                (equip.logradouro && equip.logradouro.toLowerCase().includes(searchTerm));
            return matchesCity && matchesSearch;
        });

        filteredData.sort((a, b) => getEquipmentTypeOrder(a.tipo_equip) - getEquipmentTypeOrder(b.tipo_equip));
        renderEquipmentList(filteredData);
    }

    function renderEquipmentList(equipments) {
        equipmentListContainer.innerHTML = '';
        if (equipments.length === 0) {
            equipmentListContainer.innerHTML = '<p class="message">Nenhum equipamento encontrado.</p>';
            return;
        }

        const equipmentsByCity = equipments.reduce((acc, equip) => {
            (acc[equip.cidade] = acc[equip.cidade] || []).push(equip);
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

                let specificFieldsHTML = '';
                if (equip.num_instrumento) specificFieldsHTML += `<p><strong>Nº Instrumento:</strong> ${equip.num_instrumento}</p>`;
                if (equip.dt_afericao) specificFieldsHTML += `<p><strong>Data Aferição:</strong> ${new Date(equip.dt_afericao + 'T00:00:00').toLocaleDateString('pt-BR')}</p>`;
                if (equip.dt_vencimento) specificFieldsHTML += `<p><strong>Venc. Aferição:</strong> ${new Date(equip.dt_vencimento + 'T00:00:00').toLocaleDateString('pt-BR')}</p>`;

                if (['RADAR FIXO', 'EDUCATIVO', 'LOMBADA', 'LAP'].includes(equip.tipo_equip)) {
                    if (equip.qtd_faixa) specificFieldsHTML += `<p><strong>Qtd. Faixa:</strong> ${equip.qtd_faixa}</p>`;
                    if (equip.km) specificFieldsHTML += `<p><strong>KM da via:</strong> ${equip.km}</p>`;
                    if (equip.sentido) specificFieldsHTML += `<p><strong>Sentido:</strong> ${equip.sentido}</p>`;
                }
                
                const item = document.createElement('div');
                item.className = 'item-equipamento';
                item.innerHTML = `
                    <h3>${equip.nome_equip || 'N/A'}</h3>
                    <p><strong>Tipo:</strong> ${equip.tipo_equip || 'N/A'}</p>
                    <p><strong>Referência:</strong> ${equip.referencia_equip || 'N/A'}</p>
                    ${specificFieldsHTML}
                    <p><strong>Endereço:</strong> ${equip.logradouro || 'N/A'} - ${equip.bairro || 'N/A'}</p>
                    <p><strong>Provedor:</strong> ${equip.nome_prov || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-cell ${statusClass}">${statusDisplay}</span></p>
                    <button class="botao-editar" data-equipment-id="${equip.id_equipamento}">Editar</button>
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
        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
        addSpecificFieldsContainer.classList.add('hidden');
        addEquipmentModal.classList.add('is-active');
        addFormButtonsContainer.style.display = 'flex';
    }

    function openEditEquipmentModal(equipmentData) {
        if (!equipmentData) return;
        editEquipmentForm.reset();
        
        document.getElementById('editEquipmentId').value = equipmentData.id_equipamento;
        document.getElementById('editEnderecoId').value = equipmentData.id_endereco;
        document.getElementById('editEquipmentType').value = equipmentData.tipo_equip;
        document.getElementById('editEquipmentName').value = equipmentData.nome_equip;
        document.getElementById('editEquipmentReference').value = equipmentData.referencia_equip;
        document.getElementById('editEquipmentStatus').value = equipmentData.status;
        document.getElementById('editEquipmentQtdFaixa').value = equipmentData.qtd_faixa;
        document.getElementById('editEquipmentKm').value = equipmentData.km;
        document.getElementById('editEquipmentSentido').value = equipmentData.sentido;
        document.getElementById('editEquipmentProvider').value = equipmentData.id_provedor;
        document.getElementById('editEquipmentCity').value = equipmentData.id_cidade;
        document.getElementById('editEquipmentLogradouro').value = equipmentData.logradouro;
        document.getElementById('editEquipmentBairro').value = equipmentData.bairro;
        document.getElementById('editEquipmentCep').value = equipmentData.cep;
        document.getElementById('editNumInstrumento').value = equipmentData.num_instrumento;
        document.getElementById('editDtAfericao').value = equipmentData.dt_afericao;
        document.getElementById('editEquipmentLatitude').value = equipmentData.latitude;
        document.getElementById('editEquipmentLongitude').value = equipmentData.longitude;

        toggleSpecificFields(document.getElementById('editEquipmentType'), editSpecificFieldsContainer);
        hideMessage(editEquipmentMessage);
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        editEquipmentModal.classList.add('is-active');
        editFormButtonsContainer.style.display = 'flex';
    }
    
    // --- FUNÇÕES DE SETUP DA VALIDAÇÃO (PARA LIMPAR ERROS) ---
    function setupFormValidationListeners(formElement, messageElement, validationRules) {
        const inputs = formElement.querySelectorAll('input, select');
        const validateAndClear = () => {
            let allValid = true;
            for (const fieldId in validationRules) {
                const input = formElement.querySelector(`#${fieldId}`);
                if (input && !input.value.trim()) {
                    allValid = false;
                    break;
                }
            }
            if (allValid) hideMessage(messageElement);
        };
        inputs.forEach(input => {
            input.addEventListener('input', validateAndClear);
            input.addEventListener('change', validateAndClear);
        });
    }

    // --- EVENT LISTENERS ---
    addEquipmentBtn.addEventListener('click', openAddEquipmentModal);
    [closeAddEquipmentModal, cancelAddEquipmentButton].forEach(el => el.addEventListener('click', () => addEquipmentModal.classList.remove('is-active')));
    [closeEditEquipmentModal, cancelEditEquipmentButton].forEach(el => el.addEventListener('click', () => editEquipmentModal.classList.remove('is-active')));

    campoPesquisa.addEventListener('input', applyFilters);

    cityButtonsContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('city-button')) {
            document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            activeCityFilter = event.target.dataset.city;
            applyFilters();
        }
    });

    document.getElementById('equipmentType').addEventListener('change', (e) => toggleSpecificFields(e.target, addSpecificFieldsContainer));
    document.getElementById('editEquipmentType').addEventListener('change', (e) => toggleSpecificFields(e.target, editSpecificFieldsContainer));

    equipmentListContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('botao-editar')) {
            openEditEquipmentModal(findEquipmentById(event.target.dataset.equipmentId));
        }
    });

    window.addEventListener('click', (event) => {
        if (event.target === addEquipmentModal) addEquipmentModal.classList.remove('is-active');
        if (event.target === editEquipmentModal) editEquipmentModal.classList.remove('is-active');
    });

    addEquipmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const errorMessage = validateForm(addEquipmentForm);
        if (errorMessage) {
            showMessage(addEquipmentMessage, errorMessage, 'error');
            return;
        }

        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', true);
        try {
            const response = await fetch('API/add_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(addEquipmentForm)))
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
        
        // Validação condicional para campos de aferição
        const numInstrumento = document.getElementById('editNumInstrumento');
        const dtAfericao = document.getElementById('editDtAfericao');
        const originalData = findEquipmentById(document.getElementById('editEquipmentId').value);
        if (originalData && (originalData.num_instrumento || originalData.dt_afericao)) {
            if (!numInstrumento.value.trim() || !dtAfericao.value.trim()) {
                showMessage(editEquipmentMessage, 'Nº do Instrumento e Data de Aferição não podem ser esvaziados.', 'error');
                return;
            }
        }
        
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', true);
        try {
            const response = await fetch('API/update_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(editEquipmentForm)))
            });
            const result = await response.json();

            if (result.success) {
                showMessage(editEquipmentMessage, 'Equipamento atualizado com sucesso!', 'success');
                editFormButtonsContainer.style.display = 'none';
                setTimeout(() => {
                    editEquipmentModal.classList.remove('is-active');
                    fetchAndRenderEquipments();
                }, 1500);
            } else {
                showMessage(editEquipmentMessage, result.message || 'Erro ao atualizar equipamento.', 'error');
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
            }
        } catch (error) {
            console.error('Erro ao atualizar equipamento:', error);
            showMessage(editEquipmentMessage, 'Ocorreu um erro de conexão. Tente novamente.', 'error');
            toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        }
    });

    // --- INICIALIZAÇÃO ---
    fetchProvidersForSelect();
    fetchAndRenderEquipments();
    setupFormValidationListeners(addEquipmentForm, addEquipmentMessage, fieldsToValidate);
    // Para o modal de edição, a lógica de limpeza é um pouco diferente e já tratada no submit/input
    const editInputsToWatch = editEquipmentForm.querySelectorAll('input, select');
    editInputsToWatch.forEach(input => {
        input.addEventListener('input', () => hideMessage(editEquipmentMessage));
    });
});