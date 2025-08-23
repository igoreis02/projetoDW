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
    const tecnicoInLocoSection = document.getElementById('tecnicoInLocoSection');
    const btnTecnicoSim = document.getElementById('btnTecnicoSim');
    const btnTecnicoNao = document.getElementById('btnTecnicoNao');
    const repairDescriptionSection = document.getElementById('repairDescriptionSection');
    const repairDescriptionInput = document.getElementById('repairDescription');
    const confirmEquipmentSelectionBtn = document.getElementById('confirmEquipmentSelection');
    const equipmentSelectionErrorMessage = document.getElementById('equipmentSelectionErrorMessage');
    const installEquipmentAndAddressSection = document.getElementById('installEquipmentAndAddressSection');
    const confirmInstallEquipmentBtn = document.getElementById('confirmInstallEquipment');
    
    // --- INÍCIO DA CORREÇÃO ---
    const newEquipmentTypeSelect = document.getElementById('newEquipmentType');
    const quantitySection = document.getElementById('quantitySection');
    // --- FIM DA CORREÇÃO ---

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
    let existingMaintenanceData = null; 

    // --- SEÇÃO DE FUNÇÕES ---

    // --- INÍCIO DA CORREÇÃO ---
    // Adiciona o event listener para mostrar/ocultar a seção de quantidade de faixas
    newEquipmentTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        const typesWithOptions = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA'];
        if (typesWithOptions.includes(selectedType)) {
            quantitySection.classList.remove('hidden');
        } else {
            quantitySection.classList.add('hidden');
            document.getElementById('newEquipmentQuantity').value = ''; // Limpa o valor se o campo for ocultado
        }
    });
    // --- FIM DA CORREÇÃO ---

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
        
        quantitySection.classList.add('hidden'); // Garante que a seção de faixas seja ocultada ao fechar

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
            const url = `get_equipamentos.php?city_id=${cityId}&search_term=${encodeURIComponent(searchTerm)}`;
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
             resetarBotoesDeEscolha(); 
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

     async function proceedToConfirmation() {
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
        
        document.getElementById('confirmCityName').textContent = selectedCityName;
        document.getElementById('confirmMaintenanceType').textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
        
        let finalStatus = currentRepairStatus;
        
        document.getElementById('installConfirmationDetails').classList.add('hidden');
        document.getElementById('maintenanceConfirmationDetails').classList.add('hidden');
        document.getElementById('confirmProviderContainer').classList.add('hidden');
        
        if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor' && tecnicoInLoco) {
            finalStatus = 'pendente';
            document.getElementById('confirmProviderProblem').textContent = selectedProblemDescription;
            document.getElementById('confirmProviderName').textContent = selectedEquipment.nome_prov || 'Não especificado';
            document.getElementById('confirmProviderContainer').classList.remove('hidden');
        } else {
            document.getElementById('confirmEquipmentName').textContent = `${selectedEquipment.nome_equip} - ${selectedEquipment.referencia_equip}`;
            document.getElementById('confirmProblemDescription').textContent = selectedProblemDescription;
            if (repairDesc) {
                document.getElementById('confirmRepairDescriptionSpan').textContent = repairDesc;
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
        
        if (!equipId || !problemDesc) {
            equipmentSelectionErrorMessage.textContent = 'Por favor, selecione um equipamento e descreva o problema.';
            equipmentSelectionErrorMessage.classList.remove('hidden');
            return;
        }
        equipmentSelectionErrorMessage.classList.add('hidden');

        if (currentFlow === 'maintenance' && currentMaintenanceType === 'corretiva') {
            try {
                const response = await fetch(`check_pending_maintenance.php?equipment_id=${equipId}`);
                const data = await response.json();

                if (data.found) {
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
        
        // --- INÍCIO DA CORREÇÃO ---
        // Lógica para exibir a quantidade de faixas na confirmação
        const confirmQuantityContainer = document.getElementById('confirmQuantityContainer');
        if (newEquipmentQuantity && newEquipmentQuantity > 0) {
            document.getElementById('confirmEquipmentQuantity').textContent = newEquipmentQuantity;
            confirmQuantityContainer.classList.remove('hidden');
        } else {
            confirmQuantityContainer.classList.add('hidden');
        }
        // --- FIM DA CORREÇÃO ---
        
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

    confirmSaveButton.addEventListener('click', async function() {
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
                const addressResponse = await fetch('save_endereco.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(addressPayload) });
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
                const equipmentResponse = await fetch('save_equipamento.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(equipmentPayload) });
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

                 const maintenanceResponse = await fetch('save_manutencao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(maintenancePayload) });
                 const maintenanceData = await maintenanceResponse.json();
                 if (!maintenanceData.success) throw new Error(maintenanceData.message || 'Falha ao criar registro de instalação.');

            } else {
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
                     payload = {
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
                }
    
                const response = await fetch('save_manutencao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Ocorreu um erro.');
            }
            
            confirmMessage.textContent = 'Operação realizada com sucesso!';
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