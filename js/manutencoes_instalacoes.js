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
    
    // Referências para Instalação (Reintegradas)
    const installEquipmentAndAddressSection = document.getElementById('installEquipmentAndAddressSection');
    const newEquipmentType = document.getElementById('newEquipmentType');
    const newEquipmentNameInput = document.getElementById('newEquipmentName');
    const newEquipmentReferenceInput = document.getElementById('newEquipmentReference');
    const quantitySection = document.getElementById('quantitySection');
    const newEquipmentQuantity = document.getElementById('newEquipmentQuantity');
    const addressLogradouroInput = document.getElementById('addressLogradouro');
    const addressBairroInput = document.getElementById('addressBairro');
    const addressCepInput = document.getElementById('addressCep');
    const addressLatitudeInput = document.getElementById('addressLatitude');
    const addressLongitudeInput = document.getElementById('addressLongitude');
    const installationNotes = document.getElementById('installationNotes');
    const confirmInstallEquipmentBtn = document.getElementById('confirmInstallEquipment');

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
    const confirmEquipmentTypeSpan = document.getElementById('confirmEquipmentType');
    const confirmNewEquipmentNameSpan = document.getElementById('confirmNewEquipmentName');
    const confirmNewEquipmentRefSpan = document.getElementById('confirmNewEquipmentRef');
    const confirmQuantityContainer = document.getElementById('confirmQuantityContainer');
    const confirmEquipmentQuantitySpan = document.getElementById('confirmEquipmentQuantity');
    const confirmAddressLogradouroSpan = document.getElementById('confirmAddressLogradouro');
    const confirmAddressBairroSpan = document.getElementById('confirmAddressBairro');
    const confirmAddressCepSpan = document.getElementById('confirmAddressCep');
    const confirmInstallationNotesSpan = document.getElementById('confirmInstallationNotes');
    
    // --- SEÇÃO DE VARIÁVEIS DE ESTADO ---
    let allEquipments = [];
    let selectedCityId = null, selectedCityName = '';
    let selectedEquipment = null;
    let selectedProblemDescription = '', selectedRepairDescription = '';
    let currentMaintenanceType = '', currentRepairStatus = '', currentFlow = '';
    let realizadoPor = '', tecnicoInLoco = null;
    let tempInstallationData = {};

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
    
    window.closeCadastroManutencaoModal = function() {
        cadastroManutencaoModal.classList.remove('is-active');
        confirmationModal.classList.remove('is-active');
        
        [installEquipmentAndAddressSection, equipmentSelectionSection].forEach(section => {
            if(section) section.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
        });
        [realizadoPorSection, tecnicoInLocoSection, repairDescriptionSection, problemDescriptionSection, quantitySection].forEach(el => el.style.display = 'none');
        
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
            loadEquipamentos(selectedCityId);
        }
    }
    
    async function loadEquipamentos(cityId) {
        equipmentSelect.innerHTML = '<option>Carregando...</option>';
        try {
            const url = `get_equipamentos.php?city_id=${cityId}`;
            const response = await fetch(url);
            const data = await response.json();
            if (data.success) {
                allEquipments = data.equipamentos;
                equipmentSelect.innerHTML = '';
                allEquipments.forEach(equip => {
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
    
    window.goBackToCitySelection = function() {
        citySelectionSection.style.display = 'block';
        equipmentSelectionSection.style.display = 'none';
        installEquipmentAndAddressSection.style.display = 'none';
    }

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
        confirmEquipmentSelectionBtn.click();
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
        confirmEquipmentNameSpan.textContent = `${selectedEquipment.nome_equip} - ${selectedEquipment.referencia_equip}`;
        confirmProblemDescriptionSpan.textContent = selectedProblemDescription;
        confirmMaintenanceTypeSpan.textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
        
        let finalStatus = currentRepairStatus;
        maintenanceConfirmationDetails.classList.remove('hidden');
        confirmTecnicoInLocoContainer.classList.add('hidden');

        if (currentMaintenanceType === 'preditiva' && realizadoPor === 'provedor' && tecnicoInLoco) {
            finalStatus = 'pendente';
            maintenanceConfirmationDetails.classList.add('hidden');
            confirmTecnicoInLocoContainer.classList.remove('hidden');
        }
        
        confirmRepairStatusSpan.textContent = finalStatus.charAt(0).toUpperCase() + finalStatus.slice(1);
        
        if (selectedRepairDescription) {
            confirmRepairDescriptionSpan.textContent = selectedRepairDescription;
            confirmRepairDescriptionContainer.classList.remove('hidden');
        } else {
             confirmRepairDescriptionContainer.classList.add('hidden');
        }
        
        installConfirmationDetails.classList.add('hidden');
        confirmationModal.classList.add('is-active');
    });
    
    // **LÓGICA DE INSTALAÇÃO REINTEGRADA**
    newEquipmentType.addEventListener('change', () => {
        quantitySection.style.display = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA'].includes(newEquipmentType.value) ? 'flex' : 'none';
    });

    confirmInstallEquipmentBtn.addEventListener('click', () => {
        if (newEquipmentType.value === "" || newEquipmentNameInput.value.trim() === "" || newEquipmentReferenceInput.value.trim() === "" || addressLogradouroInput.value.trim() === "" || addressBairroInput.value.trim() === "" || addressCepInput.value.trim() === "") {
            alert("Por favor, preencha todos os campos obrigatórios.");
            return;
        }

        tempInstallationData = {
            type: newEquipmentType.value, name: newEquipmentNameInput.value.trim(), ref: newEquipmentReferenceInput.value.trim(),
            quantity: newEquipmentQuantity.value.trim(), logradouro: addressLogradouroInput.value.trim(),
            bairro: addressBairroInput.value.trim(), cep: addressCepInput.value.trim(),
            lat: addressLatitudeInput.value.trim(), lon: addressLongitudeInput.value.trim(),
            notes: installationNotes.value.trim(),
        };

        confirmCityNameSpan.textContent = selectedCityName;
        confirmEquipmentTypeSpan.textContent = tempInstallationData.type;
        confirmNewEquipmentNameSpan.textContent = tempInstallationData.name;
        confirmNewEquipmentRefSpan.textContent = tempInstallationData.ref;
        confirmAddressLogradouroSpan.textContent = tempInstallationData.logradouro;
        confirmAddressBairroSpan.textContent = tempInstallationData.bairro;
        confirmAddressCepSpan.textContent = tempInstallationData.cep;
        confirmInstallationNotesSpan.textContent = tempInstallationData.notes || 'Nenhuma';
        confirmMaintenanceTypeSpan.textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
        confirmRepairStatusSpan.textContent = currentRepairStatus.charAt(0).toUpperCase() + currentRepairStatus.slice(1);

        if (!quantitySection.classList.contains('hidden') && tempInstallationData.quantity) {
            confirmEquipmentQuantitySpan.textContent = tempInstallationData.quantity;
            confirmQuantityContainer.classList.remove('hidden');
        } else {
            confirmQuantityContainer.classList.add('hidden');
        }

        installConfirmationDetails.classList.remove('hidden');
        maintenanceConfirmationDetails.classList.add('hidden');
        confirmationModal.classList.add('is-active');
    });

    confirmSaveButton.addEventListener('click', async function() {
        confirmSpinner.classList.remove('hidden');
        confirmSaveButton.disabled = true;
        cancelSaveButton.disabled = true;
        confirmMessage.classList.add('hidden');
    
        try {
            let finalMessage = '';
    
            if (currentFlow === 'installation') {
                const addressResponse = await fetch('save_endereco.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        logradouro: tempInstallationData.logradouro, bairro: tempInstallationData.bairro,
                        cep: tempInstallationData.cep, latitude: tempInstallationData.lat, longitude: tempInstallationData.lon
                    })
                });
                const addressData = await addressResponse.json();
                if (!addressData.success) throw new Error('Falha ao salvar endereço: ' + addressData.message);

                const equipmentResponse = await fetch('save_equipamento.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nome_equip: tempInstallationData.name, referencia_equip: tempInstallationData.ref,
                        id_cidade: selectedCityId, id_endereco: addressData.id_endereco,
                        tipo_equip: tempInstallationData.type, qtd_faixa: tempInstallationData.quantity
                    })
                });
                const equipmentData = await equipmentResponse.json();
                if (!equipmentData.success) throw new Error('Falha ao salvar equipamento: ' + equipmentData.message);

                const maintenanceResponse = await fetch('save_manutencao.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        city_id: selectedCityId, equipment_id: equipmentData.id_equipamento,
                        problem_description: 'Instalação de novo equipamento', tipo_manutencao: 'instalação',
                        status_reparo: 'pendente', observacao_instalacao: tempInstallationData.notes
                    })
                });
                const maintenanceData = await maintenanceResponse.json();
                if (!maintenanceData.success) throw new Error('Falha ao registrar operação: ' + maintenanceData.message);
                finalMessage = 'Instalação registrada com sucesso!';
            } else {
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
                finalMessage = "Cadastrada com sucesso!";
            }
    
            confirmMessage.textContent = finalMessage;
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

    window.cancelSaveManutencao = function() {
        if(confirmationModal) confirmationModal.classList.remove('is-active');
    }

    window.closeConfirmationModal = function() {
        if(confirmationModal) confirmationModal.classList.remove('is-active');
    }

    if (matrizManutencaoBtn) matrizManutencaoBtn.addEventListener('click', () => openMaintenanceModal('corretiva', 'pendente', 'maintenance'));
    if (matrizSemaforicaBtn) matrizSemaforicaBtn.addEventListener('click', () => alert('Funcionalidade ainda não implementada.'));
    if (controleOcorrenciaBtn) controleOcorrenciaBtn.addEventListener('click', () => openMaintenanceModal('preditiva', 'concluido', 'maintenance'));
    if (instalarEquipamentoBtn) instalarEquipamentoBtn.addEventListener('click', () => openMaintenanceModal('instalação', 'pendente', 'installation'));
});