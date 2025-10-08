document.addEventListener('DOMContentLoaded', () => {
    // --- SEÇÃO DE REFERÊNCIAS DOM ---
    const matrizManutencaoBtn = document.getElementById('matrizManutencaoBtn');
    const matrizSemaforicaBtn = document.getElementById('matrizSemaforicaBtn');
    const controleOcorrenciaBtn = document.getElementById('controleOcorrenciaBtn');
    const instalarEquipamentoBtn = document.getElementById('instalarEquipamentoBtn');
    const cadastroManutencaoModal = document.getElementById('cadastroManutencaoModal');
    const citySelectionSection = document.getElementById('citySelectionSection');
    const cityButtonsContainer = document.getElementById('cityButtonsContainer');
    const equipmentSelectionSection = document.getElementById('equipmentSelectionSection');
    const equipmentSelect = document.getElementById('equipmentSelect');
    const equipmentSearchInput = document.getElementById('equipmentSearchInput');
    const problemDescriptionSection = document.getElementById('problemDescriptionSection');
    const problemDescriptionInput = document.getElementById('problemDescription');
    const realizadoPorSection = document.getElementById('realizadoPorSection');
    const btnProcessamento = document.getElementById('btnProcessamento');
    const btnProvedor = document.getElementById('btnProvedor');
    const installEquipmentTypeContainer = document.getElementById('installEquipmentType');
    const installErrorMessage = document.getElementById('installErrorMessage');
    const installProviderSelect = document.getElementById('installProvider');

    // --- NOVAS REFERÊNCIAS ---
    const reparoConcluidoSection = document.getElementById('reparoConcluidoSection');
    const btnReparoSim = document.getElementById('btnReparoSim');
    const btnReparoNao = document.getElementById('btnReparoNao');

    const tecnicoInLocoSection = document.getElementById('tecnicoInLocoSection');
    const btnTecnicoSim = document.getElementById('btnTecnicoSim');
    const btnTecnicoNao = document.getElementById('btnTecnicoNao');
    const repairDescriptionSection = document.getElementById('repairDescriptionSection');
    const repairDescriptionInput = document.getElementById('repairDescription');
    const confirmEquipmentSelectionBtn = document.getElementById('confirmEquipmentSelection');
    const equipmentSelectionErrorMessage = document.getElementById('equipmentSelectionErrorMessage');
    const installEquipmentAndAddressSection = document.getElementById('installEquipmentAndAddressSection');
    const confirmInstallEquipmentBtn = document.getElementById('confirmInstallEquipment');

    const newEquipmentTypeSelect = document.getElementById('newEquipmentType');
    const quantitySection = document.getElementById('quantitySection');

    // Referências para o modal de confirmação
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmCityNameSpan = document.getElementById('confirmCityName');
    const confirmEquipmentNameSpan = document.getElementById('confirmEquipmentName');
    const confirmProblemDescriptionSpan = document.getElementById('confirmProblemDescription');
    const confirmRepairDescriptionContainer = document.getElementById('confirmRepairDescriptionContainer');
    const confirmRepairDescription = document.getElementById('confirmRepairDescription');
    const confirmMaintenanceTypeSpan = document.getElementById('confirmMaintenanceType');
    const confirmRepairStatusSpan = document.getElementById('confirmRepairStatus');
    const confirmTecnicoInLocoContainer = document.getElementById('confirmTecnicoInLocoContainer');
    const confirmSaveButton = document.getElementById('confirmSaveButton');
    const cancelSaveButton = document.getElementById('cancelSaveButton');
    const confirmSpinner = document.getElementById('confirmSpinner');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmationButtonsDiv = confirmationModal.querySelector('.confirmation-buttons');
    const maintenanceConfirmationDetails = document.getElementById('maintenanceConfirmationDetails');
    const installConfirmationDetails = document.getElementById('installConfirmationDetails');
    const confirmProviderContainer = document.getElementById('confirmProviderContainer');
    const confirmProviderProblem = document.getElementById('confirmProviderProblem');
    const confirmProviderName = document.getElementById('confirmProviderName');
    const pendingMaintenanceModal = document.getElementById('pendingMaintenanceModal');
    const confirmAppendProblemBtn = document.getElementById('confirmAppendProblem');
    const cancelAppendProblemBtn = document.getElementById('cancelAppendProblem');
    const existingMaintenanceText = document.getElementById('existingMaintenanceText');


    const semaforicaSection = document.getElementById('semaforicaSection');
    const semaforicaForm = document.getElementById('semaforicaForm');
    const confirmSemaforicaBtn = document.getElementById('confirmSemaforicaBtn');
    const semaforicaErrorMessage = document.getElementById('semaforicaErrorMessage');
    const semaforicaConfirmationDetails = document.getElementById('semaforicaConfirmationDetails');

    const assignTecnicoSemaforicaBtn = document.getElementById('assignTecnicoSemaforicaBtn');
    const assignSemaforicaModal = document.getElementById('assignSemaforicaModal');

    // --- SEÇÃO DE VARIÁVEIS DE ESTADO ---
    let allEquipments = [];
    let selectedCityId = null, selectedCityName = '';
    let selectedEquipment = null;
    let selectedProblemDescription = '', selectedRepairDescription = '';
    let currentMaintenanceType = '', currentRepairStatus = '', currentFlow = '';
    let realizadoPor = '', tecnicoInLoco = null;
    let reparoConcluido = null; // true para Sim, false para Não
    let existingMaintenanceData = null;
    let semaforicaData = {};
    let allProvidersData = [];
    let firstSelectedType = null;

    // --- SEÇÃO DE FUNÇÕES ---

    async function fetchProvidersForSelect() {
        installProviderSelect.innerHTML = '<option value="">Carregando...</option>';
        try {
            const response = await fetch('API/get_provedores_select.php');
            const data = await response.json();
            if (data.success) {
                allProvidersData = data.provedores;
                const defaultOption = '<option value="">Selecione o Provedor</option>';
                const providerOptions = data.provedores.map(p => `<option value="${p.id_provedor}">${p.nome_prov}</option>`).join('');
                installProviderSelect.innerHTML = defaultOption + providerOptions;
            } else {
                installProviderSelect.innerHTML = '<option value="">Falha ao carregar</option>';
            }
        } catch (error) {
            installProviderSelect.innerHTML = '<option value="">Erro de conexão</option>';
        }
    }

    function handleCityChangeForInstallation(cityId) {
        if (!cityId || allProvidersData.length === 0) {
            installProviderSelect.value = '';
            return;
        }
        const matchingProvider = allProvidersData.find(p => p.id_cidade == cityId);
        installProviderSelect.value = matchingProvider ? matchingProvider.id_provedor : '';
    }

    function toggleInstallConditionalFields() {
        const form = installEquipmentAndAddressSection;
        const checkedBoxes = form.querySelectorAll('input[name="new_tipo_equip[]"]:checked');
        const selectedTypes = Array.from(checkedBoxes).map(cb => cb.value);

        // O tipo principal é o primeiro que o usuário marcou
        if (checkedBoxes.length > 0 && !firstSelectedType) {
            firstSelectedType = checkedBoxes[0].value;
        } else if (checkedBoxes.length === 0) {
            firstSelectedType = null; // Reseta se desmarcar todos
        }

        const specificContainer = document.getElementById('install-specific-fields-container');
        const quantityInput = document.getElementById('newEquipmentQuantity');
        const sentidoInput = document.getElementById('newEquipmentSentido');
        const velocidadeInput = document.getElementById('newEquipmentVelocidade');

        // Oculta todos os campos condicionais para começar
        specificContainer.classList.add('hidden');
        quantityInput.parentElement.classList.add('hidden');
        sentidoInput.parentElement.classList.add('hidden');
        velocidadeInput.parentElement.classList.add('hidden');

        if (!firstSelectedType) return; // Se nada estiver selecionado, não mostra nada

        // Grupo 1: CC0, DOME, VIDEO MONITORAMENTO
        const group1 = ['CCO', 'DOME', 'VÍDEO MONITORAMENTO'];
        // Grupo 2: LAP, MONITOR DE SEMÁFORO
        const group2 = ['LAP', 'MONITOR DE SEMÁFORO'];
        // Grupo 3: LOMBADA ELETRÔNICA, RADAR FIXO, EDUCATIVO
        const group3 = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'EDUCATIVO'];

        if (group1.includes(firstSelectedType)) {
            // Nenhum campo específico aparece
        } else if (group2.includes(firstSelectedType)) {
            specificContainer.classList.remove('hidden');
            quantityInput.parentElement.classList.remove('hidden');
            sentidoInput.parentElement.classList.remove('hidden');
        } else if (group3.includes(firstSelectedType)) {
            specificContainer.classList.remove('hidden');
            quantityInput.parentElement.classList.remove('hidden');
            sentidoInput.parentElement.classList.remove('hidden');
            velocidadeInput.parentElement.classList.remove('hidden');
        }
    }

    function createInstallValidationMap() {
        const isEducativo = Array.from(installEquipmentTypeContainer.querySelectorAll('input:checked'))
            .map(cb => cb.value).includes('EDUCATIVO');

        const validationMap = {
            'new_tipo_equip[]': 'Tipo de Equipamento',
            'newEquipmentName': 'Nome / Identificador',
            'newEquipmentReference': 'Referência / Local',
            'addressLogradouro': 'Logradouro',
            'addressBairro': 'Bairro',
            'installProvider': 'Provedor',
            'addressCep': 'CEP',
        };

        if (!document.getElementById('install-specific-fields-container').classList.contains('hidden')) {
            const isQuantityVisible = !document.getElementById('newEquipmentQuantity').parentElement.classList.contains('hidden');
            const isSentidoVisible = !document.getElementById('newEquipmentSentido').parentElement.classList.contains('hidden');
            const isVelocidadeVisible = !document.getElementById('newEquipmentVelocidade').parentElement.classList.contains('hidden');

            if (isQuantityVisible && !isEducativo) validationMap['newEquipmentQuantity'] = 'Quantidade de Faixas';
            if (isSentidoVisible && !isEducativo) validationMap['newEquipmentSentido'] = 'Sentido';
            if (isVelocidadeVisible && !isEducativo) validationMap['newEquipmentVelocidade'] = 'Velocidade (KM/h)';
        }

        return validationMap;
    }

    function validateInstallForm() {
        const validationMap = createInstallValidationMap();
        for (const id in validationMap) {
            if (id === 'new_tipo_equip[]') {
                if (installEquipmentTypeContainer.querySelectorAll('input:checked').length === 0) {
                    return `O campo '${validationMap[id]}' é obrigatório.`;
                }
            } else {
                const field = document.getElementById(id);
                if (field && !field.closest('.hidden') && (!field.value || field.value.trim() === '')) {
                    return `O campo '${validationMap[id]}' é obrigatório.`;
                }
            }
        }
        return true;
    }

    function setupInstallValidationListeners() {
        installEquipmentAndAddressSection.querySelectorAll('input, select, textarea').forEach(input => {
            const eventType = (input.type === 'checkbox' || input.tagName.toLowerCase() === 'select') ? 'change' : 'input';
            input.addEventListener(eventType, () => installErrorMessage.classList.add('hidden'));
        });
    }

    function resetarBotoesDeEscolha() {
        document.querySelectorAll('.choice-buttons .page-button').forEach(btn => btn.classList.remove('selected'));
        realizadoPor = '';
        tecnicoInLoco = null;
        reparoConcluido = null; // Reseta a nova variável
    }


    async function openMaintenanceModal(type, status, flow) {
        document.getElementById('confirmSaveButton').innerHTML = 'Confirmar <span id="confirmSpinner" class="loading-spinner hidden"></span>';
        const assignBtn = document.getElementById('assignTecnicoSemaforicaBtn');
        if (assignBtn) assignBtn.classList.add('hidden');
        const semaforicaDetails = document.getElementById('semaforicaConfirmationDetails');
        if (semaforicaDetails) semaforicaDetails.classList.add('hidden');

        currentMaintenanceType = type; // Armazena o tipo interno (ex: 'preditiva')
        currentRepairStatus = status;
        currentFlow = flow;

        // --- LÓGICA DO TÍTULO CORRIGIDA ---
        // Define um título amigável baseado no tipo interno
        let modalTitleText = `Cadastrar ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        if (type === 'corretiva') modalTitleText = 'Cadastrar Ocorrência Técnica';
        if (type === 'preditiva') modalTitleText = 'Cadastrar Ocorrência de Controle';
        document.getElementById('modalTitle').textContent = flow === 'installation' ? 'Cadastrar Instalação' : modalTitleText;
        // --- FIM DA CORREÇÃO DO TÍTULO ---

        if (equipmentSelectionErrorMessage) equipmentSelectionErrorMessage.classList.add('hidden');
        if (semaforicaErrorMessage) semaforicaErrorMessage.classList.add('hidden');

        cadastroManutencaoModal.classList.add('is-active');
        citySelectionSection.style.display = 'block';
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';
        if (document.getElementById('semaforicaSection')) {
            document.getElementById('semaforicaSection').style.display = 'none';
        }

        resetarBotoesDeEscolha();

        cityButtonsContainer.innerHTML = '<p id="loadingCitiesMessage">Carregando cidades...</p>';
        try {
            let fetchUrl = 'API/get_cidades.php';
            if (flow === 'semaforica') {
                fetchUrl += '?context=semaforica';
            } else {
                fetchUrl += '?context=manutencao';
            }
            const response = await fetch(fetchUrl);
            const data = await response.json();
            if (data.success && data.cidades.length > 0) {
                cityButtonsContainer.innerHTML = '';
                data.cidades.forEach(city => {
                    const button = document.createElement('button');
                    button.classList.add('city-button');
                    button.textContent = city.nome;
                    button.addEventListener('click', () => handleCitySelection(city.id_cidade, city.nome));
                    cityButtonsContainer.appendChild(button);
                });
            } else {
                cityButtonsContainer.innerHTML = `<p class="selection-error-message">${data.message || 'Nenhuma cidade encontrada.'}</p>`;
            }
        } catch (error) {
            cityButtonsContainer.innerHTML = `<p class="selection-error-message">Erro de conexão ao carregar cidades.</p>`;
        }
    }

    window.closeCadastroManutencaoModal = function () {
        cadastroManutencaoModal.classList.remove('is-active');
        confirmationModal.classList.remove('is-active');

        [installEquipmentAndAddressSection, equipmentSelectionSection].forEach(section => {
            if (section) section.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
        });

        [realizadoPorSection, tecnicoInLocoSection, repairDescriptionSection, problemDescriptionSection, reparoConcluidoSection].forEach(el => {
            if (el) el.style.display = 'none';
        });

        quantitySection.classList.add('hidden');
        equipmentSelectionErrorMessage.classList.add('hidden');
        confirmMessage.classList.add('hidden');
        confirmationButtonsDiv.style.display = 'flex';
        confirmSaveButton.disabled = false;
        cancelSaveButton.disabled = false;
        confirmSpinner.classList.add('hidden');
    }

    function handleCitySelection(cityId, cityName) {
        selectedCityId = cityId;
        selectedCityName = cityName;
        citySelectionSection.style.display = 'none';
        if (currentFlow === 'installation') {
            installEquipmentAndAddressSection.style.display = 'flex';
            // Limpa e reseta o formulário de instalação para um novo uso
            installEquipmentAndAddressSection.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.type === 'checkbox') el.checked = false;
                else el.value = '';
            });
            firstSelectedType = null; // Reseta o tipo principal
            toggleInstallConditionalFields(); // Esconde todos os campos condicionais
            fetchProvidersForSelect().then(() => { // Busca provedores e depois seleciona o da cidade
                handleCityChangeForInstallation(selectedCityId);
            });
        } else if (currentFlow === 'semaforica') {
            semaforicaSection.style.display = 'flex';
            semaforicaForm.reset();
        } else {
            equipmentSelectionSection.style.display = 'flex';
            loadEquipamentos(selectedCityId, '');
        }
    }

    async function loadEquipamentos(cityId, searchTerm = '') {
        equipmentSelect.innerHTML = '<option>Carregando...</option>';
        try {
            const url = `API/get_equipamentos.php?city_id=${cityId}&search_term=${encodeURIComponent(searchTerm)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                if (searchTerm === '') {
                    allEquipments = data.equipamentos;
                }
                equipmentSelect.innerHTML = '';
                data.equipamentos.forEach(equip => {
                    const option = document.createElement('option');
                    option.value = equip.id_equipamento;
                    option.textContent = `${equip.nome_equip} - ${equip.referencia_equip}`;
                    equipmentSelect.appendChild(option);
                });
            } else {
                equipmentSelect.innerHTML = `<option>${data.message || 'Nenhum equipamento'}</option>`;
            }
        } catch (error) {
            equipmentSelect.innerHTML = '<option>Erro de conexão</option>';
        }
    }


    function resetAndGoBackToCitySelection() {
        // 1. Reseta e esconde o modal de confirmação
        confirmationModal.classList.remove('is-active');
        confirmMessage.classList.add('hidden');
        confirmationButtonsDiv.style.display = 'flex';
        confirmSaveButton.disabled = false;
        cancelSaveButton.disabled = false;
        confirmSpinner.classList.add('hidden');

        // 2. Garante que os detalhes da atribuição e o botão de atribuir fiquem escondidos
        const assignmentDetails = document.getElementById('confirmSemaforicaAssignmentDetails');
        if (assignmentDetails) assignmentDetails.classList.add('hidden');
        const assignBtn = document.getElementById('assignTecnicoSemaforicaBtn');
        if (assignBtn) assignBtn.classList.add('hidden');

        // 3. Limpa os dados e o formulário
        semaforicaData = {};
        if (semaforicaForm) semaforicaForm.reset();

        // 4. Mostra a seção de cidades e recarrega a lista
        showModalSection('citySelectionSection');
        openMaintenanceModal('Ocorrência Semafórica', 'pendente', 'semaforica'); // Recarrega o fluxo do início
    }
    function resetForNewOperationInSameCity() {
        // 1. Reseta e esconde o modal de confirmação
        confirmationModal.classList.remove('is-active');
        confirmMessage.classList.add('hidden');
        confirmationButtonsDiv.style.display = 'flex';
        confirmSaveButton.disabled = false;
        cancelSaveButton.disabled = false;
        confirmSpinner.classList.add('hidden');

        // 2. Limpa variáveis de estado da última operação
        selectedEquipment = null;
        selectedProblemDescription = '';
        selectedRepairDescription = '';
        existingMaintenanceData = null;
        resetarBotoesDeEscolha(); // Reutiliza sua função para limpar os botões

        // 3. Verifica o fluxo atual para exibir a tela correta
        if (currentFlow === 'installation') {
             // Limpa todos os campos do formulário de instalação
            installEquipmentAndAddressSection.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.type === 'checkbox') el.checked = false;
                else el.value = '';
            });
            
            firstSelectedType = null;
            toggleInstallConditionalFields(); //função para resetar a visibilidade dos campos

            // Garante que a tela de instalação continue visível
            installEquipmentAndAddressSection.style.display = 'flex';
            equipmentSelectionSection.style.display = 'none';

        } else { // Fluxo de 'maintenance'
            // Limpa os campos específicos da manutenção
            problemDescriptionInput.value = '';
            repairDescriptionInput.value = '';
            equipmentSearchInput.value = '';

            // Esconde as seções condicionais para um formulário limpo
            problemDescriptionSection.style.display = 'none';
            realizadoPorSection.style.display = 'none';
            reparoConcluidoSection.style.display = 'none';
            tecnicoInLocoSection.style.display = 'none';
            repairDescriptionSection.style.display = 'none';

            // Garante que a tela de seleção de equipamento continue visível
            equipmentSelectionSection.style.display = 'flex';
            installEquipmentAndAddressSection.style.display = 'none';

            // 4. Recarrega a lista de equipamentos da cidade que já estava selecionada
            loadEquipamentos(selectedCityId, '');
        }
    }
    
    window.goBackToCitySelection = function () {
        // Mostra a seção de cidades
        citySelectionSection.style.display = 'block';

        // Garante que TODAS as outras seções de conteúdo estejam escondidas
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';

        // --- LINHA ADICIONADA: Esconde o formulário da Matriz Semafórica ---
        if (semaforicaSection) {
            semaforicaSection.style.display = 'none';
        }

        // --- LINHA ADICIONADA: Limpa ("formata") os dados do formulário semafórico ---
        if (semaforicaForm) {
            semaforicaForm.reset();
        }

        // Reseta o estado da atribuição de técnicos para evitar vazamento para outros fluxos
        if (typeof semaforicaData !== 'undefined') {
            semaforicaData = {};
        }
        const assignBtn = document.getElementById('assignTecnicoSemaforicaBtn');
        if (assignBtn) {
            assignBtn.textContent = 'Atribuir Técnico';
            assignBtn.classList.add('hidden');
        }
        const confirmBtn = document.getElementById('confirmSaveButton');
        if (confirmBtn) {
            confirmBtn.innerHTML = 'Confirmar <span id="confirmSpinner" class="loading-spinner hidden"></span>';
        }
        const assignmentDetails = document.getElementById('confirmSemaforicaAssignmentDetails');
        if (assignmentDetails) {
            assignmentDetails.classList.add('hidden');
        }
    };

    equipmentSearchInput.addEventListener('input', () => {
        if (selectedCityId) {
            loadEquipamentos(selectedCityId, equipmentSearchInput.value.trim());
        }
    });

    equipmentSelect.addEventListener('change', () => {
        problemDescriptionSection.style.display = 'flex';
        problemDescriptionInput.value = '';
        resetarBotoesDeEscolha();
        [realizadoPorSection, repairDescriptionSection, tecnicoInLocoSection, reparoConcluidoSection].forEach(el => {
            if (el) el.style.display = 'none';
        });
        equipmentSelectionErrorMessage.classList.add('hidden');
    });

    problemDescriptionInput.addEventListener('input', () => {
        equipmentSelectionErrorMessage.classList.add('hidden');
        if (currentMaintenanceType === 'preditiva' && problemDescriptionInput.value.trim() !== '') {
            realizadoPorSection.style.display = 'flex';
        } else {
            realizadoPorSection.style.display = 'none';
            reparoConcluidoSection.style.display = 'none';
            tecnicoInLocoSection.style.display = 'none';
            repairDescriptionSection.style.display = 'none';
            resetarBotoesDeEscolha();
        }
    });

    function handleButtonClick(button, group) {
        equipmentSelectionErrorMessage.classList.add('hidden');
        document.querySelectorAll(`#${group} .page-button`).forEach(btn => btn.classList.remove('selected'));
        button.classList.add('selected');
        if (group === 'realizadoPorSection') realizadoPor = button.id === 'btnProcessamento' ? 'processamento' : 'provedor';
        if (group === 'tecnicoInLocoSection') tecnicoInLoco = button.id === 'btnTecnicoSim';
        if (group === 'reparoConcluidoSection') reparoConcluido = button.id === 'btnReparoSim';
    }

    btnProcessamento.addEventListener('click', () => {
        handleButtonClick(btnProcessamento, 'realizadoPorSection');
        reparoConcluidoSection.style.display = 'flex';
        tecnicoInLocoSection.style.display = 'none';
        repairDescriptionSection.style.display = 'none';
        tecnicoInLoco = null;
        reparoConcluido = null;
        btnReparoSim.classList.remove('selected');
        btnReparoNao.classList.remove('selected');
    });

    btnProvedor.addEventListener('click', () => {
        handleButtonClick(btnProvedor, 'realizadoPorSection');
        tecnicoInLocoSection.style.display = 'flex';
        reparoConcluidoSection.style.display = 'none';
        repairDescriptionSection.style.display = 'none';
        repairDescriptionInput.value = '';
    });

    btnTecnicoSim.addEventListener('click', () => {
        handleButtonClick(btnTecnicoSim, 'tecnicoInLocoSection');
        repairDescriptionSection.style.display = 'none';
    });

    btnTecnicoNao.addEventListener('click', () => {
        handleButtonClick(btnTecnicoNao, 'tecnicoInLocoSection');
        repairDescriptionSection.style.display = 'flex';
    });

    btnReparoSim.addEventListener('click', () => {
        handleButtonClick(btnReparoSim, 'reparoConcluidoSection');
        repairDescriptionSection.style.display = 'flex';
    });

    btnReparoNao.addEventListener('click', () => {
        handleButtonClick(btnReparoNao, 'reparoConcluidoSection');
        repairDescriptionSection.style.display = 'none';
        repairDescriptionInput.value = '';
    });

    async function proceedToConfirmation() {
        const equipId = equipmentSelect.value;
        const problemDesc = problemDescriptionInput.value.trim();

        const repairDesc = repairDescriptionInput.value.trim();
        let errorMessage = '';


        if (!equipId) errorMessage = 'Por favor, selecione um equipamento.';
        else if (!problemDesc) errorMessage = 'A descrição do problema é obrigatória.';

        if (currentMaintenanceType === 'preditiva') {
            if (!realizadoPor) errorMessage = 'Selecione quem realizou o reparo (Processamento ou Provedor).';
            else if (realizadoPor === 'processamento' && reparoConcluido === null) errorMessage = 'Informe se o reparo foi concluído (Sim ou Não).';
            else if (realizadoPor === 'processamento' && reparoConcluido === true && !repairDesc) errorMessage = 'Descreva o reparo realizado.';
            else if (realizadoPor === 'provedor' && tecnicoInLoco === null) errorMessage = 'Informe se precisa de técnico em campo.';

        }

        if (errorMessage) {
            equipmentSelectionErrorMessage.textContent = errorMessage;
            equipmentSelectionErrorMessage.classList.remove('hidden');
            return;
        }

        equipmentSelectionErrorMessage.classList.add('hidden');
        selectedEquipment = allEquipments.find(e => e.id_equipamento == equipId);
        selectedProblemDescription = problemDesc;
        selectedRepairDescription = repairDesc;

        document.getElementById('confirmCityName').textContent = selectedCityName;
        document.getElementById('confirmMaintenanceType').textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);

        let finalStatus = currentRepairStatus;
        if (currentMaintenanceType === 'preditiva') {
            if ((realizadoPor === 'processamento' && reparoConcluido === false) || (realizadoPor === 'provedor' && tecnicoInLoco === true)) {
                finalStatus = 'pendente';
            } else {
                finalStatus = 'concluido';
            }
        }


        document.getElementById('installConfirmationDetails').classList.add('hidden');
        document.getElementById('maintenanceConfirmationDetails').classList.add('hidden');
        document.getElementById('confirmProviderContainer').classList.add('hidden');

        if (document.getElementById('confirmProviderAcao')) document.getElementById('confirmProviderAcao').classList.add('hidden');
        if (document.getElementById('confirmProviderReparo')) document.getElementById('confirmProviderReparo').classList.add('hidden');



        if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor') {
            document.getElementById('confirmProviderProblem').textContent = selectedProblemDescription;

            if (tecnicoInLoco) {
                const providerNameEl = document.getElementById('confirmProviderName');
                const providerAcaoEl = document.getElementById('confirmProviderAcao');
                if (providerNameEl) providerNameEl.textContent = selectedEquipment.nome_prov || 'Não especificado';
                if (providerAcaoEl) providerAcaoEl.classList.remove('hidden');

            } else {

                const providerReparoTextEl = document.getElementById('confirmProviderReparoText');
                const providerReparoEl = document.getElementById('confirmProviderReparo');
                if (providerReparoTextEl) {
                    providerReparoTextEl.textContent = selectedRepairDescription;
                }
                if (providerReparoEl) providerReparoEl.classList.remove('hidden');
            }

            document.getElementById('confirmProviderContainer').classList.remove('hidden');

        } else {
            // Lógica para todos os outros tipos de manutenção (continua a mesma)
            document.getElementById('confirmEquipmentName').textContent = `${selectedEquipment.nome_equip} - ${selectedEquipment.referencia_equip}`;
            document.getElementById('confirmProblemDescription').textContent = selectedProblemDescription;
            if (repairDesc) {
                document.getElementById('confirmRepairDescription').textContent = repairDesc;
                document.getElementById('confirmRepairDescriptionContainer').classList.remove('hidden');
            } else {
                document.getElementById('confirmRepairDescriptionContainer').classList.add('hidden');
            }
            document.getElementById('maintenanceConfirmationDetails').classList.remove('hidden');
        }

        document.getElementById('confirmRepairStatus').textContent = finalStatus.charAt(0).toUpperCase() + finalStatus.slice(1);
        confirmationModal.classList.add('is-active');
    }

    confirmEquipmentSelectionBtn.addEventListener('click', async () => {
        const equipId = equipmentSelect.value;
        const problemDesc = problemDescriptionInput.value.trim();

        if (!equipId) {
            equipmentSelectionErrorMessage.textContent = 'Por favor, selecione um equipamento.';
            equipmentSelectionErrorMessage.classList.remove('hidden');
            return;
        }
        equipmentSelectionErrorMessage.classList.add('hidden');

        if (currentFlow === 'maintenance' && currentMaintenanceType === 'corretiva') {
            try {
                const response = await fetch(`API/check_pending_maintenance.php?equipment_id=${equipId}`);
                const data = await response.json();

                if (data.found && Array.isArray(data.maintenances) && data.maintenances.length > 0) {
                    // Pega o ID da primeira ocorrência para a lógica de "juntar"
                    existingMaintenanceData = {
                        id: data.maintenances[0].id_manutencao,
                        ocorrencia: data.maintenances[0].ocorrencia_reparo
                    };

                    // 1. Filtra as ocorrências por status
                    const pendingMaintenances = data.maintenances.filter(m => m.status_reparo === 'pendente');
                    const inProgressMaintenances = data.maintenances.filter(m => m.status_reparo === 'em andamento');

                    let dynamicHTML = ''; // String para construir o HTML

                    // 2. Cria o bloco de HTML para ocorrências pendentes, se existirem
                    if (pendingMaintenances.length > 0) {
                        dynamicHTML += '<p><strong>Ocorrência(s) pendente(s):</strong></p>';
                        dynamicHTML += '<ol style="margin: 0; padding-left: 20px;">';
                        pendingMaintenances.forEach(maint => {
                            dynamicHTML += `<li style="margin-bottom: 5px;">${maint.ocorrencia_reparo}</li>`;
                        });
                        dynamicHTML += '</ol>';
                    }

                    // 3. Cria o bloco de HTML para ocorrências em andamento, se existirem
                    if (inProgressMaintenances.length > 0) {
                        dynamicHTML += `<p style="margin-top: 1rem;"><strong>Ocorrência(s) em andamento:</strong></p>`;
                        dynamicHTML += '<ol style="margin: 0; padding-left: 20px;">';
                        inProgressMaintenances.forEach(maint => {
                            dynamicHTML += `<li style="margin-bottom: 5px;">${maint.ocorrencia_reparo}</li>`;
                        });
                        dynamicHTML += '</ol>';
                    }

                    // 4. Injeta o HTML gerado no contêiner
                    const container = document.getElementById('existingMaintenanceContainer');
                    container.innerHTML = dynamicHTML;

                    pendingMaintenanceModal.classList.add('is-active');
                    return; // Interrompe a execução para esperar a decisão do usuário
                }
            } catch (error) {
                console.error("Erro ao verificar manutenção pendente:", error);
            }
        }

        // Se não encontrou pendências (ou não era o fluxo correto), continua para a confirmação
        proceedToConfirmation();
    });

    confirmInstallEquipmentBtn.addEventListener('click', () => {
        const validationResult = validateInstallForm();
        if (validationResult !== true) {
            installErrorMessage.textContent = validationResult;
            installErrorMessage.classList.remove('hidden');
            return;
        }
        installErrorMessage.classList.add('hidden');

        // Coleta de dados
        const selectedTypes = Array.from(document.querySelectorAll('input[name="new_tipo_equip[]"]:checked')).map(cb => cb.value).join(', ');
        const newEquipmentName = document.getElementById('newEquipmentName').value.trim();
        const newEquipmentRef = document.getElementById('newEquipmentReference').value.trim();
        const addressLogradouro = document.getElementById('addressLogradouro').value.trim();
        const addressBairro = document.getElementById('addressBairro').value.trim();
        const addressCep = document.getElementById('addressCep').value.trim();
        const installationNotes = document.getElementById('installationNotes').value.trim();
        const providerSelect = document.getElementById('installProvider');
        const providerName = providerSelect.selectedIndex > 0 ? providerSelect.options[providerSelect.selectedIndex].text : 'N/A';

        // 1. Defina 'selectedTypesArray' PRIMEIRO
        const selectedTypesArray = Array.from(document.querySelectorAll('input[name="new_tipo_equip[]"]:checked')).map(cb => cb.value);
        
        // 2. A lógica de status agora funciona, pois a variável existe
        const tiposParaEtiqueta = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'MONITOR DE SEMÁFORO'];
        let statusFinal = 'pendente'; 
        if (selectedTypesArray.some(tipo => tiposParaEtiqueta.includes(tipo))) {
            statusFinal = 'Aguardando etiqueta';    
        }
        currentRepairStatus = statusFinal; // Atualiza o status atual para a confirmação

        // Preenche o modal de confirmação
        maintenanceConfirmationDetails.classList.add('hidden');
        installConfirmationDetails.classList.remove('hidden');

        document.getElementById('confirmCityName').textContent = selectedCityName;
        document.getElementById('confirmEquipmentType').textContent = selectedTypes;
        document.getElementById('confirmNewEquipmentName').textContent = newEquipmentName;
        document.getElementById('confirmNewEquipmentRef').textContent = newEquipmentRef;
        document.getElementById('confirmAddressLogradouro').textContent = addressLogradouro;
        document.getElementById('confirmAddressBairro').textContent = addressBairro;
        document.getElementById('confirmAddressCep').textContent = addressCep || 'N/A';
        document.getElementById('confirmProvider').textContent = providerName;
        document.getElementById('confirmInstallationNotes').textContent = installationNotes || 'Nenhuma.';
        document.getElementById('confirmMaintenanceType').textContent = 'Instalação';
        document.getElementById('confirmRepairStatus').textContent = currentRepairStatus.charAt(0).toUpperCase() + currentRepairStatus.slice(1);

        // Lógica para campos específicos na confirmação
        const confirmSpecificsContainer = document.getElementById('confirm-specific-fields-container');
        const confirmQuantity = document.getElementById('confirmEquipmentQuantity');
        const confirmSentido = document.getElementById('confirmEquipmentSentido');
        const confirmVelocidade = document.getElementById('confirmEquipmentVelocidade');

        const quantityValue = document.getElementById('newEquipmentQuantity').value;
        const sentidoValue = document.getElementById('newEquipmentSentido').value.trim();
        const velocidadeValue = document.getElementById('newEquipmentVelocidade').value;

        const isSpecificsVisible = !document.getElementById('install-specific-fields-container').classList.contains('hidden');

        if (isSpecificsVisible) {
            confirmSpecificsContainer.classList.remove('hidden');

            const isQuantityVisible = !document.getElementById('newEquipmentQuantity').parentElement.classList.contains('hidden');
            const isSentidoVisible = !document.getElementById('newEquipmentSentido').parentElement.classList.contains('hidden');
            const isVelocidadeVisible = !document.getElementById('newEquipmentVelocidade').parentElement.classList.contains('hidden');

            confirmQuantity.parentElement.style.display = isQuantityVisible ? 'block' : 'none';
            confirmQuantity.textContent = quantityValue || 'N/A';

            confirmSentido.parentElement.style.display = isSentidoVisible ? 'block' : 'none';
            confirmSentido.textContent = sentidoValue || 'N/A';

            confirmVelocidade.parentElement.style.display = isVelocidadeVisible ? 'block' : 'none';
            confirmVelocidade.textContent = velocidadeValue ? `${velocidadeValue} km/h` : 'N/A';

        } else {
            confirmSpecificsContainer.classList.add('hidden');
        }

        confirmationModal.classList.add('is-active');
    });

    confirmAppendProblemBtn.addEventListener('click', () => {
        pendingMaintenanceModal.classList.remove('is-active');
        proceedToConfirmation();
    });

    cancelAppendProblemBtn.addEventListener('click', () => {
        pendingMaintenanceModal.classList.remove('is-active');
        existingMaintenanceData = null;
    });

    confirmSaveButton.addEventListener('click', async function () {
        if (currentFlow !== 'semaforica') assignTecnicoSemaforicaBtn.classList.add('hidden');

        confirmSpinner.classList.remove('hidden');
        confirmSpinner.classList.remove('hidden');
        confirmSaveButton.disabled = true;
        cancelSaveButton.disabled = true;
        confirmMessage.classList.add('hidden');

        try {
            if (currentFlow === 'semaforica') {
                const response = await fetch('API/save_ocorrencia_semaforica.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(semaforicaData)
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Falha ao salvar ocorrência semafórica.');

            } else if (currentFlow === 'installation') {
                // 1. Salvar Endereço
                const coordenadasValue = document.getElementById('addressCoordenadas').value.trim();
                let latitude = null;
                let longitude = null;

                // Processa o campo de coordenadas para extrair lat/lon
                if (coordenadasValue && coordenadasValue.includes(',')) {
                    const parts = coordenadasValue.split(',');
                    if (parts.length === 2) {
                        // Converte para número e remove espaços em branco; se a conversão falhar, se torna null
                        latitude = parseFloat(parts[0].trim()) || null;
                        longitude = parseFloat(parts[1].trim()) || null;
                    }
                }

                const addressPayload = {
                    logradouro: document.getElementById('addressLogradouro').value.trim(),
                    bairro: document.getElementById('addressBairro').value.trim(),
                    cep: document.getElementById('addressCep').value.trim(),
                    latitude: latitude,
                    longitude: longitude,
                };
                const addressResponse = await fetch('API/save_endereco.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(addressPayload) });
                const addressData = await addressResponse.json();
                if (!addressData.success) throw new Error(addressData.message || 'Falha ao salvar endereço.');

                // 2. Salvar Equipamento
                const selectedTypesArray = Array.from(document.querySelectorAll('input[name="new_tipo_equip[]"]:checked')).map(cb => cb.value);

                const equipmentPayload = {
                    nome_equip: document.getElementById('newEquipmentName').value.trim(),
                    referencia_equip: document.getElementById('newEquipmentReference').value.trim(),
                    // O backend espera uma string, então unimos o array
                    tipo_equip: selectedTypesArray.join(','),
                    qtd_faixa: document.getElementById('newEquipmentQuantity').value || null,
                    sentido: document.getElementById('newEquipmentSentido').value.trim() || null,
                    km: document.getElementById('newEquipmentVelocidade').value || null,
                    id_provedor: document.getElementById('installProvider').value || null,
                    id_cidade: selectedCityId,
                    id_endereco: addressData.id_endereco
                };
                const equipmentResponse = await fetch('API/save_equipamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(equipmentPayload) });
                const equipmentData = await equipmentResponse.json();
                if (!equipmentData.success) throw new Error(equipmentData.message || 'Falha ao salvar equipamento.');

                // 3. Salvar Manutenção (Instalação)
                const maintenancePayload = {
                    city_id: selectedCityId,
                    equipment_id: equipmentData.id_equipamento,
                    problem_description: "Instalação de novo equipamento",
                    tipo_manutencao: 'instalação',
                    status_reparo: currentRepairStatus,
                    observacao_instalacao: document.getElementById('installationNotes').value.trim(),
                    equipment_name: document.getElementById('newEquipmentName').value.trim(),
                    equipment_ref: document.getElementById('newEquipmentReference').value.trim()
                };

                const maintenanceResponse = await fetch('API/save_manutencao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(maintenancePayload) });
                const maintenanceData = await maintenanceResponse.json();
                if (!maintenanceData.success) throw new Error(maintenanceData.message || 'Falha ao criar registro de instalação.');

            } else {

                if (currentMaintenanceType === 'preditiva' && realizadoPor === 'processamento') {

                    const payload = {
                        city_id: selectedCityId,
                        equipment_id: selectedEquipment.id_equipamento,
                        problem_description: selectedProblemDescription,
                        reparo_finalizado: selectedRepairDescription,
                        reparo_concluido: reparoConcluido // true para 'Sim', false para 'Não'
                    };
                    const response = await fetch('API/save_ocorrencia_processamento.php', { // <-- CHAMA O SCRIPT CORRETO
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro ao salvar o controle de ocorrência.');

                } else if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor') {

                    const payload = { city_id: selectedCityId, equipment_id: selectedEquipment.id_equipamento, id_provedor: selectedEquipment.id_provedor, problem_description: selectedProblemDescription, reparo_finalizado: selectedRepairDescription, tecnico_in_loco: tecnicoInLoco, tipo_ocorrencia: 'manutencao' };
                    const response = await fetch('API/save_ocorrencia_provedor.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro ao salvar a ocorrência do provedor.');

                } else {

                    const payload = {
                        city_id: selectedCityId,
                        equipment_id: selectedEquipment.id_equipamento,
                        problem_description: selectedProblemDescription,
                        tipo_manutencao: currentMaintenanceType,
                        status_reparo: currentRepairStatus,
                        id_manutencao_existente: existingMaintenanceData ? existingMaintenanceData.id : null
                    };
                    const response = await fetch('API/save_manutencao.php', { // <-- CHAMA O SCRIPT PADRÃO
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro ao salvar a manutenção.');
                }
            }

            confirmMessage.textContent = 'Operação realizada com sucesso!';
            confirmMessage.className = 'message success';
            confirmMessage.classList.remove('hidden');
            confirmSpinner.classList.add('hidden');
            confirmationButtonsDiv.style.display = 'none';

            setTimeout(() => {
                if (currentFlow === 'semaforica') {
                    resetAndGoBackToCitySelection();
                } else {
                    resetForNewOperationInSameCity();
                }
            }, 2000);

        } catch (error) {
            confirmMessage.textContent = error.message;
            confirmMessage.className = 'message error';
            confirmMessage.classList.remove('hidden');
            confirmSpinner.classList.add('hidden');
            confirmSaveButton.disabled = false;
            cancelSaveButton.disabled = false;
        }
    });

    window.closeConfirmationModal = function () {
        if (confirmationModal) confirmationModal.classList.remove('is-active');
    }

    window.cancelSaveManutencao = function () {
        closeConfirmationModal();
    }


    if (confirmSemaforicaBtn) {
        confirmSemaforicaBtn.addEventListener('click', () => {
            const tipoSelect = document.getElementById('semaforicaTipo');
            const tipo = tipoSelect.value;
            const tipoTexto = tipoSelect.options[tipoSelect.selectedIndex].text;
            const endereco = document.getElementById('semaforicaEndereco').value.trim();
            const referencia = document.getElementById('semaforicaReferencia').value.trim();
            const qtd = document.getElementById('semaforicaQtd').value;
            const unidade = document.getElementById('semaforicaUnidade').value;
            const descricao = document.getElementById('semaforicaDescricao').value.trim();
            const geo = document.getElementById('semaforicaGeo').value.trim();
            const observacao = document.getElementById('semaforicaObservacao').value.trim();

            let errorMessage = '';

            if (!endereco) errorMessage = 'O campo "Endereço" é obrigatório.';
            else if (!qtd || qtd < 1) errorMessage = 'A "Quantidade" deve ser pelo menos 1.';
            else if (!descricao) errorMessage = 'A "Descrição do Problema" é obrigatória.';

            if (errorMessage) {
                semaforicaErrorMessage.textContent = errorMessage;
                semaforicaErrorMessage.classList.remove('hidden');
                return;
            }
            semaforicaErrorMessage.classList.add('hidden');

            semaforicaData = { id_cidade: selectedCityId, tipo, endereco, referencia, qtd, unidade, descricao, geo, observacao };

            maintenanceConfirmationDetails.classList.add('hidden');
            installConfirmationDetails.classList.add('hidden');
            confirmProviderContainer.classList.add('hidden');
            semaforicaConfirmationDetails.classList.remove('hidden');

            document.getElementById('confirmSemaforicaTipo').textContent = tipo || 'Não especificado';
            document.getElementById('confirmSemaforicaEndereco').textContent = endereco;
            document.getElementById('confirmSemaforicaReferencia').textContent = referencia || 'Nenhuma';
            document.getElementById('confirmSemaforicaQtd').textContent = qtd;
            document.getElementById('confirmSemaforicaUnidade').textContent = unidade;
            document.getElementById('confirmSemaforicaDescricao').textContent = descricao;
            document.getElementById('confirmSemaforicaGeo').textContent = geo || 'Não informada';
            document.getElementById('confirmSemaforicaObservacao').textContent = observacao || 'Nenhuma';

            confirmCityNameSpan.textContent = selectedCityName;
            confirmMaintenanceTypeSpan.textContent = 'Ocorrência Semafórica';
            confirmRepairStatusSpan.textContent = 'Pendente';

            assignTecnicoSemaforicaBtn.classList.remove('hidden');

            confirmationModal.classList.add('is-active');
        });
    }
    // Função para fechar qualquer modal
    window.closeModal = function (modalId) {
        document.getElementById(modalId).classList.remove('is-active');
    }

    // Abre o modal de atribuição
    async function openAssignSemaforicaModal() {
        const tecnicosContainer = document.getElementById('assignTecnicosContainer');
        const veiculosContainer = document.getElementById('assignVeiculosContainer');

        tecnicosContainer.innerHTML = 'Carregando...';
        veiculosContainer.innerHTML = 'Carregando...';
        document.getElementById('assignErrorMessage').classList.add('hidden');

        assignSemaforicaModal.classList.add('is-active');

        try {
            // Busca técnicos e veículos em paralelo
            const [tecnicosRes, veiculosRes] = await Promise.all([
                fetch('API/get_tecnicos.php'),
                fetch('API/get_veiculos.php')
            ]);
            const tecnicosData = await tecnicosRes.json();
            const veiculosData = await veiculosRes.json();

            // Popula os checkboxes de técnicos
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

            // Popula os checkboxes de veículos
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
        } catch (error) {
            tecnicosContainer.innerHTML = 'Erro ao carregar técnicos.';
            veiculosContainer.innerHTML = 'Erro ao carregar veículos.';
        }
    }

    // Salva a atribuição (ainda não envia, apenas anexa os dados)
    window.saveSemaforicaAssignment = function () {
        const assignErrorMessage = document.getElementById('assignErrorMessage');

        const selectedTecnicosNodes = Array.from(document.querySelectorAll('#assignTecnicosContainer .choice-btn.selected'));
        const selectedVeiculosNodes = Array.from(document.querySelectorAll('#assignVeiculosContainer .choice-btn.selected'));

        const selectedTecnicosIds = selectedTecnicosNodes.map(btn => btn.dataset.id);
        const selectedVeiculosIds = selectedVeiculosNodes.map(btn => btn.dataset.id);

        if (selectedTecnicosIds.length === 0) {
            assignErrorMessage.textContent = 'Por favor, selecione o(s) técnico(s).';
            assignErrorMessage.classList.remove('hidden');
            return;
        }
        if (selectedVeiculosIds.length === 0) {
            assignErrorMessage.textContent = 'Por favor, selecione o(s) veículo(s).';
            assignErrorMessage.classList.remove('hidden');
            return;
        }
        assignErrorMessage.classList.add('hidden');

        const hoje = new Date().toISOString().slice(0, 10);

        semaforicaData.assignmentDetails = {
            inicio_reparo: hoje,
            fim_reparo: hoje,
            tecnicos: selectedTecnicosIds,
            veiculos: selectedVeiculosIds
        };

        // LÓGICA ADICIONADA PARA MOSTRAR OS SELECIONADOS
        const nomesTecnicos = selectedTecnicosNodes.map(btn => btn.textContent.trim()).join(', ');
        const nomesVeiculos = selectedVeiculosNodes.map(btn => btn.textContent.trim()).join(', ');

        document.getElementById('confirmSemaforicaTecnicos').textContent = nomesTecnicos;
        document.getElementById('confirmSemaforicaVeiculos').textContent = nomesVeiculos;
        document.getElementById('confirmSemaforicaAssignmentDetails').classList.remove('hidden');

        const confirmBtn = document.getElementById('confirmSaveButton');
        confirmBtn.innerHTML = 'Confirmar C/Técnico <span id="confirmSpinner" class="loading-spinner hidden"></span>';
        document.getElementById('assignTecnicoSemaforicaBtn').textContent = 'Substituir Técnico';


        closeModal('assignSemaforicaModal');
    }

    // Adiciona o event listener para o novo botão
    if (assignTecnicoSemaforicaBtn) {
        assignTecnicoSemaforicaBtn.addEventListener('click', openAssignSemaforicaModal);
    }


    if (matrizManutencaoBtn) matrizManutencaoBtn.addEventListener('click', () => openMaintenanceModal('corretiva', 'pendente', 'maintenance'));
    if (matrizSemaforicaBtn) matrizSemaforicaBtn.addEventListener('click', () => openMaintenanceModal('Ocorrência Semafórica', 'pendente', 'semaforica'));
    if (controleOcorrenciaBtn) controleOcorrenciaBtn.addEventListener('click', () => openMaintenanceModal('preditiva', 'concluido', 'maintenance'));
    if (instalarEquipamentoBtn) instalarEquipamentoBtn.addEventListener('click', () => openMaintenanceModal('instalação', 'pendente', 'installation'));

    const semaforicaInputs = [
        document.getElementById('semaforicaTipo'),
        document.getElementById('semaforicaEndereco'),
        document.getElementById('semaforicaQtd'),
        document.getElementById('semaforicaDescricao')
    ];

    semaforicaInputs.forEach(input => {
        if (input) {
            input.addEventListener('input', () => {
                if (semaforicaErrorMessage) semaforicaErrorMessage.classList.add('hidden');
            });
        }
    });

    if (installEquipmentTypeContainer) {
        installEquipmentTypeContainer.addEventListener('change', (e) => {
            // Lógica para redefinir o tipo principal se o usuário desmarcar o primeiro
            const checkedBoxes = installEquipmentTypeContainer.querySelectorAll('input:checked');
            if (e.target.value === firstSelectedType && !e.target.checked) {
                firstSelectedType = checkedBoxes.length > 0 ? checkedBoxes[0].value : null;
            }
            toggleInstallConditionalFields();
        });
    }
    setupInstallValidationListeners();
});