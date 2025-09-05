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

    // --- SEÇÃO DE VARIÁVEIS DE ESTADO ---
    let allEquipments = [];
    let selectedCityId = null, selectedCityName = '';
    let selectedEquipment = null;
    let selectedProblemDescription = '', selectedRepairDescription = '';
    let currentMaintenanceType = '', currentRepairStatus = '', currentFlow = '';
    let realizadoPor = '', tecnicoInLoco = null;
    let reparoConcluido = null; // true para Sim, false para Não
    let existingMaintenanceData = null;

    // --- SEÇÃO DE FUNÇÕES ---
    newEquipmentTypeSelect.addEventListener('change', function () {
        const selectedType = this.value;
        const typesWithOptions = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA'];
        if (typesWithOptions.includes(selectedType)) {
            quantitySection.classList.remove('hidden');
        } else {
            quantitySection.classList.add('hidden');
            document.getElementById('newEquipmentQuantity').value = '';
        }
    });

    function resetarBotoesDeEscolha() {
        document.querySelectorAll('.choice-buttons .page-button').forEach(btn => btn.classList.remove('selected'));
        realizadoPor = '';
        tecnicoInLoco = null;
        reparoConcluido = null; // Reseta a nova variável
    }

    async function openMaintenanceModal(type, status, flow) {
        currentMaintenanceType = type;
        currentRepairStatus = status;
        currentFlow = flow;
        document.getElementById('modalTitle').textContent = flow === 'installation' ? 'Cadastrar Instalação' : 'Cadastrar Ocorrência';
        cadastroManutencaoModal.classList.add('is-active');
        citySelectionSection.style.display = 'block';
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';

        resetarBotoesDeEscolha();

        cityButtonsContainer.innerHTML = '<p id="loadingCitiesMessage">Carregando cidades...</p>';
        try {
            const response = await fetch('API/get_cidades.php');
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
            // Limpa todos os campos do formulário de instalação para um novo cadastro
            installEquipmentAndAddressSection.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
            quantitySection.classList.add('hidden');
            document.getElementById('newEquipmentType').value = '';

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
        citySelectionSection.style.display = 'block';
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';
    }

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
        // A descrição do reparo do input só é relevante para outros fluxos agora
        const repairDesc = repairDescriptionInput.value.trim();
        let errorMessage = '';

        // --- BLOCO DE VALIDAÇÃO ATUALIZADO ---
        if (!equipId) errorMessage = 'Por favor, selecione um equipamento.';
        else if (!problemDesc) errorMessage = 'A descrição do problema é obrigatória.';

        if (currentMaintenanceType === 'preditiva') {
            if (!realizadoPor) errorMessage = 'Selecione quem realizou o reparo (Processamento ou Provedor).';
            else if (realizadoPor === 'processamento' && reparoConcluido === null) errorMessage = 'Informe se o reparo foi concluído (Sim ou Não).';
            else if (realizadoPor === 'processamento' && reparoConcluido === true && !repairDesc) errorMessage = 'Descreva o reparo realizado.';
            else if (realizadoPor === 'provedor' && tecnicoInLoco === null) errorMessage = 'Informe se precisa de técnico em campo.';
            // A validação que pedia para descrever o reparo do provedor foi REMOVIDA,
            // pois a descrição agora é gerada automaticamente no caso de "Não precisar de técnico".
        }

        if (errorMessage) {
            equipmentSelectionErrorMessage.textContent = errorMessage;
            equipmentSelectionErrorMessage.classList.remove('hidden');
            return;
        }

        equipmentSelectionErrorMessage.classList.add('hidden');
        selectedEquipment = allEquipments.find(e => e.id_equipamento == equipId);
        selectedProblemDescription = problemDesc;
        selectedRepairDescription = repairDesc; // Armazena para outros usos, se houver

        document.getElementById('confirmCityName').textContent = selectedCityName;
        document.getElementById('confirmMaintenanceType').textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);

        let finalStatus = currentRepairStatus;
        if ((realizadoPor === 'processamento' && reparoConcluido === false) || (realizadoPor === 'provedor' && tecnicoInLoco === true)) {
            finalStatus = 'pendente';
        } else {
            finalStatus = 'concluido';
        }

        // Reseta a visibilidade dos containers do modal
        document.getElementById('installConfirmationDetails').classList.add('hidden');
        document.getElementById('maintenanceConfirmationDetails').classList.add('hidden');
        document.getElementById('confirmProviderContainer').classList.add('hidden');
        // Garante que os sub-containers do provedor também estejam resetados
        if (document.getElementById('confirmProviderAcao')) document.getElementById('confirmProviderAcao').classList.add('hidden');
        if (document.getElementById('confirmProviderReparo')) document.getElementById('confirmProviderReparo').classList.add('hidden');


        // --- LÓGICA DE EXIBIÇÃO CORRIGIDA ---
        if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor') {
            document.getElementById('confirmProviderProblem').textContent = selectedProblemDescription;

            if (tecnicoInLoco) {
                // Cenário: SIM, precisa de técnico -> Mostra AÇÃO
                const providerNameEl = document.getElementById('confirmProviderName');
                const providerAcaoEl = document.getElementById('confirmProviderAcao');
                if (providerNameEl) providerNameEl.textContent = selectedEquipment.nome_prov || 'Não especificado';
                if (providerAcaoEl) providerAcaoEl.classList.remove('hidden');

            } else {
                // Cenário: NÃO, não precisa de técnico -> Mostra REPARO REALIZADO
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

                if (data.found && data.tipo_manutencao_existente === 'corretiva') {
                    existingMaintenanceData = {
                        id: data.id_manutencao,
                        ocorrencia: data.ocorrencia_existente
                    };
                    pendingMaintenanceModal.classList.add('is-active');
                    return;
                }
            } catch (error) {
                console.error("Erro ao verificar manutenção pendente:", error);
            }
        }

        proceedToConfirmation();
    });

    confirmInstallEquipmentBtn.addEventListener('click', () => {
        const newEquipmentType = document.getElementById('newEquipmentType').value;
        const newEquipmentName = document.getElementById('newEquipmentName').value.trim();
        const newEquipmentRef = document.getElementById('newEquipmentReference').value.trim();
        const addressLogradouro = document.getElementById('addressLogradouro').value.trim();
        const addressBairro = document.getElementById('addressBairro').value.trim();
        const addressCep = document.getElementById('addressCep').value.trim();
        const installationNotes = document.getElementById('installationNotes').value.trim();
        const newEquipmentQuantity = document.getElementById('newEquipmentQuantity').value;

        if (!newEquipmentType || !newEquipmentName || !newEquipmentRef || !addressLogradouro || !addressBairro || !addressCep) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        maintenanceConfirmationDetails.classList.add('hidden');
        installConfirmationDetails.classList.remove('hidden');

        document.getElementById('confirmCityName').textContent = selectedCityName;
        document.getElementById('confirmEquipmentType').textContent = newEquipmentType;
        document.getElementById('confirmNewEquipmentName').textContent = newEquipmentName;
        document.getElementById('confirmNewEquipmentRef').textContent = newEquipmentRef;
        document.getElementById('confirmAddressLogradouro').textContent = addressLogradouro;
        document.getElementById('confirmAddressBairro').textContent = addressBairro;
        document.getElementById('confirmAddressCep').textContent = addressCep;
        document.getElementById('confirmInstallationNotes').textContent = installationNotes || 'Nenhuma.';
        document.getElementById('confirmMaintenanceType').textContent = 'Instalação';
        document.getElementById('confirmRepairStatus').textContent = 'Pendente';

        const confirmQuantityContainer = document.getElementById('confirmQuantityContainer');
        if (newEquipmentQuantity && newEquipmentQuantity > 0) {
            document.getElementById('confirmEquipmentQuantity').textContent = newEquipmentQuantity;
            confirmQuantityContainer.classList.remove('hidden');
        } else {
            confirmQuantityContainer.classList.add('hidden');
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
        confirmSpinner.classList.remove('hidden');
        confirmSaveButton.disabled = true;
        cancelSaveButton.disabled = true;
        confirmMessage.classList.add('hidden');

        try {
            if (currentFlow === 'installation') {
                const addressPayload = {
                    logradouro: document.getElementById('addressLogradouro').value.trim(),
                    bairro: document.getElementById('addressBairro').value.trim(),
                    cep: document.getElementById('addressCep').value.trim(),
                    latitude: document.getElementById('addressLatitude').value || null,
                    longitude: document.getElementById('addressLongitude').value || null,
                };
                const addressResponse = await fetch('API/save_endereco.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(addressPayload) });
                const addressData = await addressResponse.json();
                if (!addressData.success) throw new Error(addressData.message || 'Falha ao salvar endereço.');

                const equipmentPayload = {
                    nome_equip: document.getElementById('newEquipmentName').value.trim(),
                    referencia_equip: document.getElementById('newEquipmentReference').value.trim(),
                    tipo_equip: document.getElementById('newEquipmentType').value,
                    qtd_faixa: document.getElementById('newEquipmentQuantity').value || null,
                    id_cidade: selectedCityId,
                    id_endereco: addressData.id_endereco
                };
                const equipmentResponse = await fetch('API/save_equipamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(equipmentPayload) });
                const equipmentData = await equipmentResponse.json();
                if (!equipmentData.success) throw new Error(equipmentData.message || 'Falha ao salvar equipamento.');

                const maintenancePayload = {
                    city_id: selectedCityId,
                    equipment_id: equipmentData.id_equipamento,
                    problem_description: "Instalação de novo equipamento",
                    tipo_manutencao: 'instalação',
                    status_reparo: 'pendente',
                    observacao_instalacao: document.getElementById('installationNotes').value.trim()
                };

                const maintenanceResponse = await fetch('API/save_manutencao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(maintenancePayload) });
                const maintenanceData = await maintenanceResponse.json();
                if (!maintenanceData.success) throw new Error(maintenanceData.message || 'Falha ao criar registro de instalação.');

            } else {
                // ### INÍCIO DA LÓGICA DE DIRECIONAMENTO ###

                // FLUXO 1: SE FOR "CONTROLE DE OCORRÊNCIA" (preditiva) E "PROVEDOR"
                if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor') {

                    const payload = {
                        city_id: selectedCityId,
                        equipment_id: selectedEquipment.id_equipamento,
                        id_provedor: selectedEquipment.id_provedor,
                        problem_description: selectedProblemDescription,
                        reparo_finalizado: selectedRepairDescription,
                        tecnico_in_loco: tecnicoInLoco,
                        tipo_ocorrencia: 'manutencao'
                    };

                    const response = await fetch('API/save_ocorrencia_provedor.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro ao salvar a ocorrência do provedor.');

                } else {
                    // FLUXO 2: PARA TODOS OS OUTROS CASOS (MATRIZ TÉCNICA E PROCESSAMENTO)

                    let endpoint = 'API/save_manutencao.php';
                    let payload = {};

                    if (existingMaintenanceData) {
                        payload = {
                            id_manutencao_existente: existingMaintenanceData.id,
                            ocorrencia_concatenada: `${existingMaintenanceData.ocorrencia}, ${problemDescriptionInput.value.trim()}`,
                            equipment_id: selectedEquipment.id_equipamento,
                            city_id: selectedCityId,
                            problem_description: problemDescriptionInput.value.trim(),
                            tipo_manutencao: 'corretiva'
                        };
                    } else {
                        let statusParaSalvar;
                        if (realizadoPor === 'processamento') {
                            statusParaSalvar = reparoConcluido ? 'concluido' : 'pendente';
                        } else {
                            statusParaSalvar = currentRepairStatus; // Fallback para corretiva
                        }

                        payload = {
                            city_id: selectedCityId,
                            equipment_id: selectedEquipment.id_equipamento,
                            id_provedor: selectedEquipment.id_provedor,
                            problem_description: selectedProblemDescription,
                            reparo_finalizado: selectedRepairDescription,
                            tipo_manutencao: currentMaintenanceType,
                            status_reparo: statusParaSalvar,
                            realizado_por: realizadoPor
                        };
                    }

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro.');

                    // Se for "Processamento" e o reparo NÃO foi concluído, salva na tabela de processamento
                    if (realizadoPor === 'processamento' && !reparoConcluido) {
                        const idManutencaoSalva = data.id_manutencao;
                        if (!idManutencaoSalva) {
                            throw new Error('Não foi possível obter o ID da manutenção para registrar o processamento.');
                        }

                        const processamentoPayload = {
                            id_manutencao: idManutencaoSalva,
                            tipo_ocorrencia: 'preditiva',
                            descricao: selectedProblemDescription
                        };

                        const processamentoResponse = await fetch('API/save_ocorrencia_processamento.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(processamentoPayload)
                        });
                        const processamentoData = await processamentoResponse.json();
                        if (!processamentoData.success) throw new Error(processamentoData.message || 'Falha ao salvar na tabela de processamento.');
                    }
                }
            }

            confirmMessage.textContent = 'Operação realizada com sucesso!';
            confirmMessage.className = 'message success';
            confirmMessage.classList.remove('hidden');
            confirmSpinner.classList.add('hidden');
            confirmationButtonsDiv.style.display = 'none';

            setTimeout(() => {
                 resetForNewOperationInSameCity();
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

    if (matrizManutencaoBtn) matrizManutencaoBtn.addEventListener('click', () => openMaintenanceModal('corretiva', 'pendente', 'maintenance'));
    if (matrizSemaforicaBtn) matrizSemaforicaBtn.addEventListener('click', () => alert('Funcionalidade ainda não implementada.'));
    if (controleOcorrenciaBtn) controleOcorrenciaBtn.addEventListener('click', () => openMaintenanceModal('preditiva', 'concluido', 'maintenance'));
    if (instalarEquipamentoBtn) instalarEquipamentoBtn.addEventListener('click', () => openMaintenanceModal('instalação', 'pendente', 'installation'));
});