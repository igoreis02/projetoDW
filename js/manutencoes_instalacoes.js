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
    const equipmentSearchInput = document.getElementById('equipmentSearchInput'); // Referência ao campo de pesquisa
    const problemDescriptionSection = document.getElementById('problemDescriptionSection');
    const problemDescriptionInput = document.getElementById('problemDescription');
    const realizadoPorSection = document.getElementById('realizadoPorSection');
    const btnProcessamento = document.getElementById('btnProcessamento');
    const btnProvedor = document.getElementById('btnProvedor');
    const tecnicoInLocoSection = document.getElementById('tecnicoInLocoSection');
    const btnTecnicoSim = document.getElementById('btnTecnicoSim');
    const btnTecnicoNao = document.getElementById('btnTecnicoNao');
    const repairDescriptionSection = document.getElementById('repairDescriptionSection');
    const repairDescriptionInput = document.getElementById('repairDescription');
    const confirmEquipmentSelectionBtn = document.getElementById('confirmEquipmentSelection');
    const equipmentSelectionErrorMessage = document.getElementById('equipmentSelectionErrorMessage');
    const installEquipmentAndAddressSection = document.getElementById('installEquipmentAndAddressSection');

    // Referências para o modal de confirmação
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmCityNameSpan = document.getElementById('confirmCityName');
    const confirmEquipmentNameSpan = document.getElementById('confirmEquipmentName');
    const confirmProblemDescriptionSpan = document.getElementById('confirmProblemDescription');
    const confirmRepairDescriptionContainer = document.getElementById('confirmRepairDescriptionContainer');
    const confirmRepairDescriptionSpan = document.getElementById('confirmRepairDescription');
    const confirmMaintenanceTypeSpan = document.getElementById('confirmMaintenanceType');
    const confirmRepairStatusSpan = document.getElementById('confirmRepairStatus');
    const confirmTecnicoInLocoContainer = document.getElementById('confirmTecnicoInLocoContainer');
    const confirmSaveButton = document.getElementById('confirmSaveButton');
    const cancelSaveButton = document.getElementById('cancelSaveButton');
    const confirmSpinner = document.getElementById('confirmSpinner');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmationButtonsDiv = confirmationModal.querySelector('.confirmation-buttons');
    const maintenanceConfirmationDetails = document.getElementById('maintenanceConfirmationDetails');

    const confirmProviderContainer = document.getElementById('confirmProviderContainer');
    const confirmProviderProblem = document.getElementById('confirmProviderProblem');
    const confirmProviderName = document.getElementById('confirmProviderName');

    // --- SEÇÃO DE VARIÁVEIS DE ESTADO ---
    let allEquipments = [];
    let selectedCityId = null, selectedCityName = '';
    let selectedEquipment = null;
    let selectedProblemDescription = '', selectedRepairDescription = '';
    let currentMaintenanceType = '', currentRepairStatus = '', currentFlow = '';
    let realizadoPor = '', tecnicoInLoco = null;

    // --- SEÇÃO DE FUNÇÕES ---

    function resetarBotoesDeEscolha() {
        document.querySelectorAll('.choice-buttons .page-button').forEach(btn => btn.classList.remove('selected'));
        realizadoPor = '';
        tecnicoInLoco = null;
    }

    async function openMaintenanceModal(type, status, flow) {
        currentMaintenanceType = type;
        currentRepairStatus = status;
        currentFlow = flow;
        document.getElementById('modalTitle').textContent = flow === 'installation' ? 'Cadastrar Instalação' : 'Cadastrar Manutenção';
        cadastroManutencaoModal.classList.add('is-active');
        citySelectionSection.style.display = 'block';
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';

        resetarBotoesDeEscolha();

        cityButtonsContainer.innerHTML = '<p id="loadingCitiesMessage">Carregando cidades...</p>';
        try {
            const response = await fetch('get_cidades.php');
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
        [realizadoPorSection, tecnicoInLocoSection, repairDescriptionSection, problemDescriptionSection].forEach(el => el.style.display = 'none');

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
            loadEquipamentos(selectedCityId, ''); // Carrega sem filtro inicialmente
        }
    }

    async function loadEquipamentos(cityId, searchTerm = '') {
        equipmentSelect.innerHTML = '<option>Carregando...</option>';
        try {
            const url = `get_equipamentos.php?city_id=${cityId}&search_term=${encodeURIComponent(searchTerm)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                // Atualiza a lista completa apenas se a busca estiver vazia
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
        [realizadoPorSection, repairDescriptionSection, tecnicoInLocoSection].forEach(el => el.style.display = 'none');
        equipmentSelectionErrorMessage.classList.add('hidden');
    });

    problemDescriptionInput.addEventListener('input', () => {
        equipmentSelectionErrorMessage.classList.add('hidden');
        if (currentMaintenanceType === 'preditiva' && problemDescriptionInput.value.trim() !== '') {
            realizadoPorSection.style.display = 'flex';
        } else {
            realizadoPorSection.style.display = 'none';
        }
        repairDescriptionSection.style.display = 'none';
        tecnicoInLocoSection.style.display = 'none';
    });

    function handleButtonClick(button, group) {
        equipmentSelectionErrorMessage.classList.add('hidden');
        document.querySelectorAll(`#${group} .page-button`).forEach(btn => btn.classList.remove('selected'));
        button.classList.add('selected');
        if (group === 'realizadoPorSection') realizadoPor = button.id === 'btnProcessamento' ? 'processamento' : 'provedor';
        if (group === 'tecnicoInLocoSection') tecnicoInLoco = button.id === 'btnTecnicoSim';
    }

    btnProcessamento.addEventListener('click', () => {
        handleButtonClick(btnProcessamento, 'realizadoPorSection');
        repairDescriptionSection.style.display = 'flex';
        tecnicoInLocoSection.style.display = 'none';
        tecnicoInLoco = null;
    });

    btnProvedor.addEventListener('click', () => {
        handleButtonClick(btnProvedor, 'realizadoPorSection');
        tecnicoInLocoSection.style.display = 'flex';
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

    confirmEquipmentSelectionBtn.addEventListener('click', () => {
        const equipId = equipmentSelect.value;
        const problemDesc = problemDescriptionInput.value.trim();
        const repairDesc = repairDescriptionInput.value.trim();
        let errorMessage = '';

        if (!equipId) errorMessage = 'Por favor, selecione um equipamento.';
        else if (!problemDesc) errorMessage = 'A descrição do problema é obrigatória.';

        if (currentMaintenanceType === 'preditiva') {
            if (!realizadoPor) errorMessage = 'Selecione quem realizou o reparo (Processamento ou Provedor).';
            else if (realizadoPor === 'processamento' && !repairDesc) errorMessage = 'Descreva o reparo realizado pelo Processamento.';
            else if (realizadoPor === 'provedor' && tecnicoInLoco === null) errorMessage = 'Informe se precisa de técnico em campo.';
            else if (realizadoPor === 'provedor' && !tecnicoInLoco && !repairDesc) errorMessage = 'Descreva o reparo realizado pelo Provedor.';
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

        confirmCityNameSpan.textContent = selectedCityName;
        confirmMaintenanceTypeSpan.textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
        
        let finalStatus = currentRepairStatus;
        
        // Esconde todas as seções de detalhes para começar do zero
        maintenanceConfirmationDetails.classList.add('hidden');
        confirmProviderContainer.classList.add('hidden');
        
        if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor' && tecnicoInLoco) {
            finalStatus = 'pendente';
            
            // Mostra a seção específica do provedor
            confirmProviderProblem.textContent = selectedProblemDescription;
            confirmProviderName.textContent = selectedEquipment.nome_prov || 'Não especificado';
            confirmProviderContainer.classList.remove('hidden');
        } else {
            // Mostra a seção de manutenção normal
            confirmEquipmentNameSpan.textContent = `${selectedEquipment.nome_equip} - ${selectedEquipment.referencia_equip}`;
            confirmProblemDescriptionSpan.textContent = selectedProblemDescription;
            if (selectedRepairDescription) {
                confirmRepairDescriptionSpan.textContent = selectedRepairDescription;
                confirmRepairDescriptionContainer.classList.remove('hidden');
            } else {
                 confirmRepairDescriptionContainer.classList.add('hidden');
            }
            maintenanceConfirmationDetails.classList.remove('hidden');
        }
        
        confirmRepairStatusSpan.textContent = finalStatus.charAt(0).toUpperCase() + finalStatus.slice(1);
        confirmationModal.classList.add('is-active');
    });

    confirmSaveButton.addEventListener('click', async function () {
        confirmSpinner.classList.remove('hidden');
        confirmSaveButton.disabled = true;
        cancelSaveButton.disabled = true;
        confirmMessage.classList.add('hidden');

        try {
            const payload = {
                city_id: selectedCityId,
                equipment_id: selectedEquipment.id_equipamento,
                id_provedor: selectedEquipment.id_provedor,
                problem_description: selectedProblemDescription,
                reparo_finalizado: repairDescriptionInput.value.trim(),
                tipo_manutencao: currentMaintenanceType,
                status_reparo: currentRepairStatus,
                realizado_por: realizadoPor,
                tecnico_in_loco: tecnicoInLoco
            };

            const response = await fetch('save_manutencao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Ocorreu um erro.');

            confirmMessage.textContent = "Cadastrada com sucesso!";
            confirmMessage.className = 'message success';
            confirmMessage.classList.remove('hidden');
            confirmSpinner.classList.add('hidden');
            confirmationButtonsDiv.style.display = 'none';

            setTimeout(() => {
                closeCadastroManutencaoModal();
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