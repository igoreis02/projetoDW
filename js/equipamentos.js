document.addEventListener('DOMContentLoaded', () => {

    // --- SEÇÃO DE ELEMENTOS DOM ---
    const addEquipmentBtn = document.getElementById('addEquipmentBtn');
    const addEquipmentModal = document.getElementById('addEquipmentModal');
    const closeAddEquipmentModal = document.getElementById('closeAddEquipmentModal');
    const cancelAddEquipmentButton = document.getElementById('cancelAddEquipmentButton');
    const addEquipmentForm = document.getElementById('addEquipmentForm');
    const addEquipmentMessage = document.getElementById('addEquipmentMessage');
    const addSpecificFieldsContainer = document.getElementById('add-specific-fields-container');
    const addAfericaoFieldsContainer = document.getElementById('add-afericao-fields-container');
    const addDateFieldsContainer = document.getElementById('add-date-fields-container');
    const addFormButtonsContainer = document.getElementById('add-form-buttons');

    const editEquipmentModal = document.getElementById('editEquipmentModal');
    const closeEditEquipmentModal = document.getElementById('closeEditEquipmentModal');
    const cancelEditEquipmentButton = document.getElementById('cancelEditEquipmentButton');
    const editEquipmentForm = document.getElementById('editEquipmentForm');
    const editEquipmentMessage = document.getElementById('editEquipmentMessage');
    const editSpecificFieldsContainer = document.getElementById('edit-specific-fields-container');
    const editAfericaoFieldsContainer = document.getElementById('edit-afericao-fields-container');
    const editDateFieldsContainer = document.getElementById('edit-date-fields-container');
    const editFormButtonsContainer = document.getElementById('edit-form-buttons');
    const equipmentListContainer = document.getElementById('containerListaEquipamentos');
    const mainLoadingState = document.getElementById('mainLoadingState');
    const campoPesquisa = document.getElementById('campoPesquisa');
    const cityButtonsContainer = document.getElementById('cityButtonsContainer');
    
    // **NOVO**: Referências para os novos botões
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const backToTopBtn = document.getElementById('backToTopBtn');
    


    // --- VARIÁVEIS DE ESTADO ---
    let allEquipmentData = [];
    let activeCityFilter = 'all';
    let equipmentCount = 0;

    // --- MAPA DE VALIDAÇÃO (SEU CÓDIGO ORIGINAL) ---
    const fieldsToValidate = {
        'equipmentType': 'Tipo de Equipamento',
        'equipmentName': 'Nome',
        'equipmentStatus': 'Status',
        'equipmentCity': 'Cidade',
        'equipmentLogradouro': 'Logradouro',
        'equipmentBairro': 'Bairro',
        'equipmentProvider': 'Provedor',
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

    function toggleConditionalFields(formId) {
        const form = document.getElementById(formId);
        const selectedTypes = Array.from(form.querySelectorAll('input[name="tipo_equip[]"]:checked')).map(cb => cb.value);

        const typesThatHideFields = ['CCO', 'DOME'];
        const shouldHideAll = selectedTypes.length > 0 && selectedTypes.every(type => typesThatHideFields.includes(type));

        const specificContainer = form.querySelector(`#${formId.includes('add') ? 'add' : 'edit'}-specific-fields-container`);
        const afericaoContainer = form.querySelector(`#${formId.includes('add') ? 'add' : 'edit'}-afericao-fields-container`);
        const dateContainer = form.querySelector(`#${formId.includes('add') ? 'add' : 'edit'}-date-fields-container`);

        if(specificContainer) specificContainer.classList.toggle('hidden', shouldHideAll);
        if(afericaoContainer) afericaoContainer.classList.toggle('hidden', shouldHideAll);
        if(dateContainer) dateContainer.classList.toggle('hidden', shouldHideAll);
    }
    async function checkForUpdates() {
        try {
            const response = await fetch('API/check_equipments_updates.php');
            const data = await response.json();
            if (data.success && data.total !== equipmentCount) {
                console.log('Novos dados detectados! Atualizando a lista...');
                fetchAndRenderEquipments(); // Se a contagem mudou, busca a lista completa
            }
        } catch (error) {
            console.error('Erro ao verificar atualizações:', error);
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
                equipmentCount = allEquipmentData.length;
                applyFilters();
            } else {
                equipmentListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                equipmentCount = 0;
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
        const order = { 'CCO': 1, 'RADAR FIXO': 2, 'LOMBADA ELETRONICA': 2, 'MONITOR DE SEMAFORO': 3, 'VIDEO MONITORAMENTO': 4, 'DOME': 5, 'LAP': 6, 'EDUCATIVO': 99 };
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
            const matchesCity = activeCityFilter === 'all' || equip.cidade.toLowerCase() === activeCityFilter.toLowerCase();
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
                    // Fallback para navegadores que não suportam showPicker()
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
                const formatDate = (date) => (!date || date === '0000-00-00') ? 'N/A' : new Date(date + 'T00:00:00').toLocaleDateString('pt-BR');
                
                const types = equip.tipo_equip ? equip.tipo_equip.split(',').map(t => t.trim()) : [];
                const typesThatHideFields = ['CCO', 'DOME'];
                const shouldShowDetails = types.length === 0 || !types.every(type => typesThatHideFields.includes(type));
                
                // **MODIFICADO**: Formatação do endereço
                const enderecoCompleto = `${equip.logradouro || 'N/A'} - ${equip.bairro || 'N/A'}`;

                const item = document.createElement('div');
                item.className = 'item-equipamento';
                item.innerHTML = `
                    <div class="item-equipamento-conteudo">
                        <h3>${equip.nome_equip || 'N/A'} - ${equip.referencia_equip || 'N/A'}</h3>
                        <p><strong>Tipo:</strong> ${equip.tipo_equip || 'N/A'}</p>
                        ${shouldShowDetails ? `
                            <p><strong>Qtd. Faixa:</strong> ${equip.qtd_faixa || 'N/A'}</p>
                            <p><strong>Sentido:</strong> ${equip.sentido || 'N/A'}</p>
                            <p><strong>Velocidade:</strong> ${equip.km ? equip.km + ' Km/h' : 'N/A'}</p>
                            <p><strong>Data Instalação:</strong> ${formatDate(equip.dt_instalacao)}</p>
                            <p><strong>Nº Instrumento:</strong> ${equip.num_instrumento || 'N/A'}</p>
                            <p><strong>Data Aferição:</strong> ${formatDate(equip.dt_afericao)}</p>
                            <p><strong>Data Vencimento:</strong> ${formatDate(equip.dt_vencimento)}</p>
                        ` : ''}
                        <p><strong>Endereço:</strong> ${enderecoCompleto}</p>
                        <p><strong>Provedor:</strong> ${equip.nome_prov || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="status-cell ${statusClass}">${statusDisplay}</span></p>
                    </div>
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
        addAfericaoFieldsContainer.classList.add('hidden');
        addDateFieldsContainer.classList.add('hidden');
        addEquipmentModal.classList.add('is-active');
        addFormButtonsContainer.style.display = 'flex';
    }
    
    function openEditEquipmentModal(equipmentData) {
        if (!equipmentData) return;
        editEquipmentForm.reset();
        
        document.getElementById('editEquipmentId').value = equipmentData.id_equipamento;
        document.getElementById('editEnderecoId').value = equipmentData.id_endereco;
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
        document.getElementById('edit_dt_instalacao').value = equipmentData.dt_instalacao;
        document.getElementById('edit_dt_estudoTec').value = equipmentData.dt_estudoTec;
        
        const coordenadas = (equipmentData.latitude && equipmentData.longitude) ? `${equipmentData.latitude}, ${equipmentData.longitude}` : '';
        document.getElementById('editCoordenadas').value = coordenadas;

        const selectedTypes = equipmentData.tipo_equip ? equipmentData.tipo_equip.split(',').map(t => t.trim()) : [];
        editEquipmentForm.querySelectorAll('input[name="tipo_equip[]"]').forEach(cb => {
            cb.checked = selectedTypes.includes(cb.value);
        });

        toggleConditionalFields('editEquipmentForm');
        hideMessage(editEquipmentMessage);
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        editEquipmentModal.classList.add('is-active');
        editFormButtonsContainer.style.display = 'flex';
    }
    
    function setupFormValidationListeners(formElement, messageElement, validationRules) {
        // Seleciona todos os inputs, selects e textareas
        const inputs = formElement.querySelectorAll('input, select, textarea');
        
        const validateAndClear = () => {
            let allValid = true;
           
            for (const fieldId in validationRules) {
                const input = formElement.querySelector(`#${fieldId}`);
                if (input) {
                    // Para checkboxes, verifica se pelo menos um está marcado
                    if (input.classList.contains('equipment-type-group')) {
                        if (input.querySelectorAll('input[type="checkbox"]:checked').length === 0) {
                            allValid = false;
                            break;
                        }
                    } 
                    // Para outros campos, usa a sua lógica original
                    else if (!input.value.trim()) {
                        allValid = false;
                        break;
                    }
                }
            }
            // Se tudo estiver válido (ou se o usuário estiver preenchendo), esconde a mensagem
            if (allValid) hideMessage(messageElement);
        };

        
        inputs.forEach(input => {
            input.addEventListener('input', () => hideMessage(messageElement)); // Esconde ao digitar/mudar
            input.addEventListener('change', () => hideMessage(messageElement)); // Esconde ao selecionar
        });
    }
    
    // **NOVO**: Função para controlar o botão "Voltar ao Topo"
    window.onscroll = function() {
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
    
    // **NOVO**: Event listener para o botão Limpar Filtros
    clearFiltersBtn.addEventListener('click', () => {
        campoPesquisa.value = '';
        activeCityFilter = 'all';
        document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.city-button[data-city="all"]').classList.add('active');
        applyFilters();
    });
    
    // **NOVO**: Event listener para o botão Voltar ao Topo
    backToTopBtn.addEventListener('click', () => {
        document.body.scrollTop = 0; // Para Safari
        document.documentElement.scrollTop = 0; // Para Chrome, Firefox, IE e Opera
    });

    cityButtonsContainer.addEventListener('click', (event) => {
        if (event.target.classList.contains('city-button')) {
            document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            activeCityFilter = event.target.dataset.city;
            applyFilters();
        }
    });

    document.getElementById('addEquipmentType').addEventListener('change', () => toggleConditionalFields('addEquipmentForm'));
    document.getElementById('editEquipmentType').addEventListener('change', () => toggleConditionalFields('editEquipmentForm'));
    
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
        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', true);
        try {
            const formData = new FormData(addEquipmentForm);
            const data = Object.fromEntries(formData.entries());
            data.tipo_equip = formData.getAll('tipo_equip[]');

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
        
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', true);
        try {
            const formData = new FormData(editEquipmentForm);
            const data = Object.fromEntries(formData.entries());
            data.tipo_equip = formData.getAll('tipo_equip[]');

            const response = await fetch('API/update_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
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
    setupFormValidationListeners(editEquipmentForm, editEquipmentMessage, fieldsToValidate);
    const editInputsToWatch = editEquipmentForm.querySelectorAll('input, select');
    editInputsToWatch.forEach(input => {
        input.addEventListener('input', () => hideMessage(editEquipmentMessage));
    });
    activateDatePickers();
    setInterval(checkForUpdates, 5000); // Verifica atualizações a cada 5 segundos
});