document.addEventListener('DOMContentLoaded', () => {

    // Declaração das variáveis no escopo correto
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


    let allEquipmentData = [];
    let currentFilteredData = [];
    let activeCityFilter = 'all';


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
        const spinner = document.getElementById(spinnerId);

        if (saveButton) {
            if (show) {
                saveButton.disabled = true;
                saveButton.textContent = 'Salvando...';
            } else {
                saveButton.disabled = false;
                if (saveButtonId === 'saveAddEquipmentButton') {
                    saveButton.textContent = 'Salvar Equipamento';
                } else if (saveButtonId === 'saveEditEquipmentButton') {
                    saveButton.textContent = 'Salvar Alterações';
                }
            }
        }

        if (cancelButton) {
            cancelButton.disabled = show;
        }

        if (spinner) {
            if (show) {
                spinner.classList.add('is-active');
            } else {
                spinner.classList.remove('is-active');
            }
        }
    }

    function toggleSpecificFields(selectElement, containerElement) {
        const selectedType = selectElement.value;
        const typesWithOptions = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA', 'LAP'];
        if (typesWithOptions.includes(selectedType)) {
            containerElement.classList.remove('hidden');
        } else {
            containerElement.classList.add('hidden');
            // Limpa os valores dos campos quando são ocultados
            containerElement.querySelector('input[name="qtd_faixa"]').value = '';
            containerElement.querySelector('input[name="km"]').value = '';
            containerElement.querySelector('input[name="sentido"]').value = '';
        }
    }


    async function fetchProvidersForSelect() {
        const selectProvider = document.getElementById('equipmentProvider');
        const editSelectProvider = document.getElementById('editEquipmentProvider');

        const loadingOption = '<option value="">Carregando provedores...</option>';
        selectProvider.innerHTML = loadingOption;
        editSelectProvider.innerHTML = loadingOption;

        try {
            const response = await fetch('API/get_provedores_select.php');
            const data = await response.json();

            if (data.success) {
                const defaultOption = '<option value="">Selecione o Provedor</option>';
                const providerOptions = data.provedores.map(p => `<option value="${p.id_provedor}">${p.nome_prov}</option>`).join('');
                selectProvider.innerHTML = defaultOption + providerOptions;
                editSelectProvider.innerHTML = defaultOption + providerOptions;
            } else {
                selectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
                editSelectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
            }
        } catch (error) {
            console.error('Erro ao buscar provedores:', error);
            selectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
            editSelectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
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
        switch (type) {
            case 'CCO': return 1;
            case 'RADAR FIXO': return 2;
            case 'DOME': return 3;
            case 'EDUCATIVO': return 4;
            case 'LOMBADA': return 5;
            case 'LAP': return 6;
            default: return 99;
        }
    }

    function applyFilters() {
        let filteredData = [...allEquipmentData];
        const searchTerm = campoPesquisa.value.toLowerCase();

        if (activeCityFilter !== 'all') {
            filteredData = filteredData.filter(equip => equip.cidade.toLowerCase() === activeCityFilter.toLowerCase());
        }

        if (searchTerm) {
            filteredData = filteredData.filter(equip =>
                (equip.nome_equip && equip.nome_equip.toLowerCase().includes(searchTerm)) ||
                (equip.referencia_equip && equip.referencia_equip.toLowerCase().includes(searchTerm)) ||
                (equip.logradouro && equip.logradouro.toLowerCase().includes(searchTerm))
            );
        }

        filteredData.sort((a, b) => getEquipmentTypeOrder(a.tipo_equip) - getEquipmentTypeOrder(b.tipo_equip));

        currentFilteredData = filteredData;
        renderEquipmentList(currentFilteredData);
    }

    function renderEquipmentList(equipments) {
        const container = document.getElementById('containerListaEquipamentos');
        container.innerHTML = '';

        if (equipments.length === 0) {
            container.innerHTML = '<p class="message">Nenhum equipamento encontrado.</p>';
            return;
        }

        const equipmentsByCity = equipments.reduce((acc, equip) => {
            const city = equip.cidade;
            if (!acc[city]) {
                acc[city] = [];
            }
            acc[city].push(equip);
            return acc;
        }, {});

        for (const city in equipmentsByCity) {
            const citySection = document.createElement('div');
            citySection.classList.add('city-section');

            const cityTitle = document.createElement('h3');
            cityTitle.textContent = city;
            citySection.appendChild(cityTitle);

            const equipmentGrid = document.createElement('div');
            equipmentGrid.classList.add('equipment-grid');
            citySection.appendChild(equipmentGrid);

            const htmlContent = equipmentsByCity[city].map(equip => {
                const statusClass = `status-${(equip.status || '').toLowerCase()}`;
                const statusDisplay = (equip.status || 'N/A').charAt(0).toUpperCase() + (equip.status || 'N/A').slice(1);

                let specificFieldsHTML = '';
                if (equip.tipo_equip !== 'CCO' && equip.tipo_equip !== 'DOME') {
                    if (equip.qtd_faixa) specificFieldsHTML += `<p><strong>Qtd. Faixa:</strong> ${equip.qtd_faixa}</p>`;
                    if (equip.km) specificFieldsHTML += `<p><strong>KM da via:</strong> ${equip.km}</p>`;
                    if (equip.sentido) specificFieldsHTML += `<p><strong>Sentido:</strong> ${equip.sentido}</p>`;
                }

                const enderecoDisplay = `<strong>Endereço:</strong> ${equip.logradouro || 'N/A'} - ${equip.bairro || 'N/A'}`;

                return `
                    <div class="item-equipamento">
                        <h3>${equip.nome_equip || 'N/A'}</h3>
                        <p><strong>Tipo:</strong> ${equip.tipo_equip || 'N/A'}</p>
                        <p><strong>Referência:</strong> ${equip.referencia_equip || 'N/A'}</p>
                        ${specificFieldsHTML}
                        <p>${enderecoDisplay}</p>
                        <p><strong>Cidade:</strong> ${equip.cidade || 'N/A'}</p>
                        <p><strong>Provedor:</strong> ${equip.nome_prov || 'N/A'}</p>
                        <p><strong>Status:</strong> <span class="status-cell ${statusClass}">${statusDisplay}</span></p>
                        <button class="botao-editar" data-equipment-id="${equip.id_equipamento}">Editar</button>
                    </div>
                `;
            }).join('');

            equipmentGrid.innerHTML = htmlContent;
            container.appendChild(citySection);
        }
    }

    function findEquipmentById(id) {
        return allEquipmentData.find(equip => equip.id_equipamento == id);
    }

    function openAddEquipmentModal() {
        addEquipmentForm.reset();
        window.hideMessage(addEquipmentMessage);
        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
        addSpecificFieldsContainer.classList.add('hidden');
        addEquipmentModal.classList.add('is-active');
        addFormButtonsContainer.style.display = 'flex';
    }

    function closeAddEquipmentModalFunc() {
        addEquipmentModal.classList.remove('is-active');
    }

    function openEditEquipmentModal(equipmentData) {
        if (!equipmentData) {
            window.showMessage(editEquipmentMessage, 'Dados do equipamento não encontrados.', 'error');
            return;
        }

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
        document.getElementById('editEquipmentLatitude').value = equipmentData.latitude;
        document.getElementById('editEquipmentLongitude').value = equipmentData.longitude;

        toggleSpecificFields(document.getElementById('editEquipmentType'), editSpecificFieldsContainer);

        window.hideMessage(editEquipmentMessage);
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        editEquipmentModal.classList.add('is-active');
        editFormButtonsContainer.style.display = 'flex';
    }

    function closeEditEquipmentModalFunc() {
        editEquipmentModal.classList.remove('is-active');
    }

    addEquipmentBtn.addEventListener('click', openAddEquipmentModal);
    closeAddEquipmentModal.addEventListener('click', closeAddEquipmentModalFunc);
    cancelAddEquipmentButton.addEventListener('click', closeAddEquipmentModalFunc);
    closeEditEquipmentModal.addEventListener('click', closeEditEquipmentModalFunc);
    cancelEditEquipmentButton.addEventListener('click', closeEditEquipmentModalFunc);

    campoPesquisa.addEventListener('input', () => {
        applyFilters();
    });

    cityButtonsContainer.addEventListener('click', (event) => {
        const target = event.target;
        if (target.classList.contains('city-button')) {
            document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
            target.classList.add('active');
            activeCityFilter = target.dataset.city;
            applyFilters();
        }
    });

    document.getElementById('equipmentType').addEventListener('change', (e) => {
        toggleSpecificFields(e.target, addSpecificFieldsContainer);
    });

    document.getElementById('editEquipmentType').addEventListener('change', (e) => {
        toggleSpecificFields(e.target, editSpecificFieldsContainer);
    });

    equipmentListContainer.addEventListener('click', function (event) {
        if (event.target.classList.contains('botao-editar')) {
            const equipmentId = event.target.dataset.equipmentId;
            const equipmentData = findEquipmentById(equipmentId);
            openEditEquipmentModal(equipmentData);
        }
    });

    window.onclick = function (event) {
        if (event.target === addEquipmentModal) {
            closeAddEquipmentModalFunc();
        }
        if (event.target === editEquipmentModal) {
            closeEditEquipmentModalFunc();
        }
    }

    addEquipmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        window.showMessage(addEquipmentMessage, 'Adicionando equipamento...', 'success');
        toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', true);

        const formData = new FormData(addEquipmentForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('API/add_equipment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                addFormButtonsContainer.style.display = 'none';
                window.showMessage(addEquipmentMessage, 'Equipamento adicionado com sucesso!', 'success');
                setTimeout(() => {
                    closeAddEquipmentModalFunc();
                    fetchAndRenderEquipments();
                }, 1500);
            } else {
                window.showMessage(addEquipmentMessage, result.message || 'Erro ao adicionar equipamento.', 'error');
                toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
            }
        } catch (error) {
            console.error('Erro ao adicionar equipamento:', error);
            window.showMessage(addEquipmentMessage, 'Ocorreu um erro ao adicionar o equipamento. Tente novamente.', 'error');
            toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
        }
    });

    editEquipmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        window.showMessage(editEquipmentMessage, 'Salvando alterações...', 'success');
        toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', true);

        const formData = new FormData(editEquipmentForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('API/update_equipment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                editFormButtonsContainer.style.display = 'none';
                window.showMessage(editEquipmentMessage, 'Equipamento atualizado com sucesso!', 'success');
                setTimeout(() => {
                    closeEditEquipmentModalFunc();
                    fetchAndRenderEquipments();
                }, 1500);
            } else {
                window.showMessage(editEquipmentMessage, result.message || 'Erro ao atualizar equipamento.', 'error');
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
            }
        } catch (error) {
            console.error('Erro ao atualizar equipamento:', error);
            window.showMessage(editEquipmentMessage, 'Ocorreu um erro ao atualizar o equipamento. Tente novamente.', 'error');
            toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
        }
    });

    fetchProvidersForSelect();
    fetchAndRenderEquipments();
});