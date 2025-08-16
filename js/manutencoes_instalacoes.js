 // --- SEÇÃO DE REFERÊNCIAS DOM ---
        const matrizManutencaoBtn = document.getElementById('matrizManutencaoBtn');
        const controleOcorrenciaBtn = document.getElementById('controleOcorrenciaBtn');
        const instalarEquipamentoBtn = document.getElementById('instalarEquipamentoBtn');
        const cadastroManutencaoModal = document.getElementById('cadastroManutencaoModal');
        const citySelectionSection = document.getElementById('citySelectionSection');
        const cityButtonsContainer = document.getElementById('cityButtonsContainer');
        const loadingCitiesMessage = document.getElementById('loadingCitiesMessage');
        const cityErrorMessage = document.getElementById('cityErrorMessage');

        const equipmentSelectionSection = document.getElementById('equipmentSelectionSection');
        const equipmentSearchInput = document.getElementById('equipmentSearchInput');
        const equipmentSelect = document.getElementById('equipmentSelect');
        const loadingEquipmentMessage = document.getElementById('loadingEquipmentMessage');
        const equipmentErrorMessage = document.getElementById('equipmentErrorMessage');
        const confirmEquipmentSelectionBtn = document.getElementById('confirmEquipmentSelection');
        const problemDescriptionSection = document.getElementById('problemDescriptionSection');
        const problemDescriptionInput = document.getElementById('problemDescription');
        const repairDescriptionSection = document.getElementById('repairDescriptionSection');
        const repairDescriptionInput = document.getElementById('repairDescription');
        const repairDescriptionErrorMessage = document.getElementById('repairDescriptionErrorMessage');
        const equipmentSelectionErrorMessage = document.getElementById('equipmentSelectionErrorMessage');
        const selectionSpinner = document.getElementById('selectionSpinner');

        // Referências para Instalação
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
        const confirmSaveButton = document.getElementById('confirmSaveButton');
        const cancelSaveButton = document.getElementById('cancelSaveButton');
        const confirmSpinner = document.getElementById('confirmSpinner');
        const confirmMessage = document.getElementById('confirmMessage');
        const confirmationButtonsDiv = confirmationModal.querySelector('.confirmation-buttons');

        // Detalhes da confirmação
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
        let selectedCityId = null;
        let selectedCityName = '';
        let selectedEquipmentId = null;
        let selectedEquipmentName = '';
        let selectedProblemDescription = '';
        let selectedRepairDescription = '';
        let currentMaintenanceType = '';
        let currentRepairStatus = '';
        let currentFlow = '';
        let tempInstallationData = {};

        // --- SEÇÃO DE FUNÇÕES ---

        async function openMaintenanceModal(type, status, flow) {
            currentMaintenanceType = type;
            currentRepairStatus = status;
            currentFlow = flow;
            document.getElementById('modalTitle').textContent = flow === 'installation' ? 'Cadastrar Instalação' : 'Cadastrar Manutenção';
            cadastroManutencaoModal.classList.add('is-active');
            citySelectionSection.style.display = 'block';
            equipmentSelectionSection.style.display = 'none';
            installEquipmentAndAddressSection.style.display = 'none';
            loadingCitiesMessage.classList.remove('hidden');
            cityErrorMessage.classList.add('hidden');
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
                console.error('Erro ao carregar cidades:', error);
                cityButtonsContainer.innerHTML = `<p class="selection-error-message">Erro de conexão ao carregar cidades.</p>`;
            }
        }

        // --- FUNÇÃO MODIFICADA PARA CORRIGIR O BUG ---
        function closeCadastroManutencaoModal() {
            cadastroManutencaoModal.classList.remove('is-active');
            confirmationModal.classList.remove('is-active');
            
            // Limpa todos os formulários
            installEquipmentAndAddressSection.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
            quantitySection.classList.add('hidden');
            equipmentSelectionSection.querySelectorAll('input, select, textarea').forEach(el => el.value = '');
            
            // Esconde seções
            repairDescriptionSection.style.display = 'none';
            problemDescriptionSection.style.display = 'none';
            
            // Reseta variáveis de estado
            selectedCityId = null; selectedCityName = ''; currentFlow = ''; tempInstallationData = {};
            
            // Reseta mensagens
            confirmMessage.classList.add('hidden');
            confirmMessage.textContent = '';
            
            // Reseta o estado dos botões de confirmação para a próxima operação
            confirmationButtonsDiv.style.display = 'flex';
            confirmSaveButton.disabled = false;
            cancelSaveButton.disabled = false;
        }

        function handleCitySelection(cityId, cityName) {
            selectedCityId = cityId;
            selectedCityName = cityName;
            citySelectionSection.style.display = 'none';
            if (currentFlow === 'installation') {
                installEquipmentAndAddressSection.style.display = 'flex';
            } else {
                equipmentSelectionSection.style.display = 'flex';
                problemDescriptionSection.style.display = 'none';
                repairDescriptionSection.style.display = 'none';
                loadEquipamentos(selectedCityId);
            }
        }
        
        async function loadEquipamentos(cityId, searchTerm = '') {
            equipmentSelect.innerHTML = '';
            loadingEquipmentMessage.classList.remove('hidden');
            loadingEquipmentMessage.textContent = 'Carregando equipamentos...';
            equipmentErrorMessage.classList.add('hidden');
            problemDescriptionSection.style.display = 'none';
            try {
                const url = `get_equipamentos.php?city_id=${cityId}&search_term=${encodeURIComponent(searchTerm)}`;
                const response = await fetch(url);
                const data = await response.json();
                loadingEquipmentMessage.classList.add('hidden');
                if (data.success && data.equipamentos.length > 0) {
                    data.equipamentos.forEach(equip => {
                        const option = document.createElement('option');
                        option.value = equip.id_equipamento;
                        option.textContent = `${equip.nome_equip} - ${equip.referencia_equip}`;
                        equipmentSelect.appendChild(option);
                    });
                } else {
                    equipmentErrorMessage.textContent = data.message || 'Nenhum equipamento encontrado.';
                    equipmentErrorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar equipamentos:', error);
                loadingEquipmentMessage.classList.add('hidden');
                equipmentErrorMessage.textContent = 'Erro de conexão ao buscar equipamentos.';
                equipmentErrorMessage.classList.remove('hidden');
            }
        }
        
        function goBackToCitySelection() {
            citySelectionSection.style.display = 'block';
            equipmentSelectionSection.style.display = 'none';
            installEquipmentAndAddressSection.style.display = 'none';
        }

        equipmentSearchInput.addEventListener('keyup', () => {
            if (selectedCityId) {
                const searchTerm = equipmentSearchInput.value.trim();
                loadEquipamentos(selectedCityId, searchTerm);
            }
        });
        
        equipmentSelect.addEventListener('change', () => {
            problemDescriptionSection.style.display = 'flex';
            repairDescriptionSection.style.display = 'none';
            problemDescriptionInput.value = '';
            repairDescriptionInput.value = '';
        });

        problemDescriptionInput.addEventListener('input', () => {
            if (currentMaintenanceType === 'preditiva' && problemDescriptionInput.value.trim() !== '') {
                repairDescriptionSection.style.display = 'flex';
            } else {
                repairDescriptionSection.style.display = 'none';
            }
        });

        confirmEquipmentSelectionBtn.addEventListener('click', () => {
            confirmEquipmentSelectionBtn.disabled = true;
            selectionSpinner.classList.remove('hidden');
            equipmentSelectionErrorMessage.classList.add('hidden'); 

            setTimeout(() => {
                const equipId = equipmentSelect.value;
                const problemDesc = problemDescriptionInput.value.trim();
                const repairDesc = repairDescriptionInput.value.trim();
                let errorMessage = '';

                if (!equipId) {
                    errorMessage = 'Por favor, selecione um equipamento.';
                } else if (!problemDesc) {
                    errorMessage = 'Por favor, descreva o problema.';
                } else if (currentMaintenanceType === 'preditiva' && !repairDesc) {
                    errorMessage = 'Por favor, descreva o reparo realizado.';
                }

                if (errorMessage) {
                    equipmentSelectionErrorMessage.textContent = errorMessage;
                    equipmentSelectionErrorMessage.classList.remove('hidden');
                    confirmEquipmentSelectionBtn.disabled = false;
                    selectionSpinner.classList.add('hidden');
                    return; 
                }

                selectedEquipmentId = equipId;
                selectedEquipmentName = equipmentSelect.options[equipmentSelect.selectedIndex].text;
                selectedProblemDescription = problemDesc;
                selectedRepairDescription = repairDesc;

                confirmCityNameSpan.textContent = selectedCityName;
                confirmEquipmentNameSpan.textContent = selectedEquipmentName;
                confirmProblemDescriptionSpan.textContent = selectedProblemDescription;
                confirmMaintenanceTypeSpan.textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
                confirmRepairStatusSpan.textContent = currentRepairStatus.charAt(0).toUpperCase() + currentRepairStatus.slice(1);
                
                if (selectedRepairDescription) {
                    confirmRepairDescriptionSpan.textContent = selectedRepairDescription;
                    confirmRepairDescriptionContainer.classList.remove('hidden');
                } else {
                     confirmRepairDescriptionContainer.classList.add('hidden');
                }
                
                maintenanceConfirmationDetails.classList.remove('hidden');
                installConfirmationDetails.classList.add('hidden');
                confirmationModal.classList.add('is-active');

                confirmEquipmentSelectionBtn.disabled = false;
                selectionSpinner.classList.add('hidden');

            }, 200);
        });

        newEquipmentType.addEventListener('change', () => {
            const typesWithOptions = ['RADAR FIXO', 'EDUCATIVO', 'LOMBADA'];
            if (typesWithOptions.includes(newEquipmentType.value)) {
                quantitySection.classList.remove('hidden');
            } else {
                quantitySection.classList.add('hidden');
                newEquipmentQuantity.value = '';
            }
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
                    const response = await fetch('save_manutencao.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            city_id: selectedCityId,
                            equipment_id: selectedEquipmentId,
                            problem_description: selectedProblemDescription,
                            reparo_finalizado: selectedRepairDescription,
                            tipo_manutencao: currentMaintenanceType,
                            status_reparo: currentRepairStatus
                        })
                    });
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Ocorreu um erro desconhecido.');
                    finalMessage = 'Operação de manutenção registrada com sucesso!';
                }

                confirmMessage.textContent = finalMessage;
                confirmMessage.className = 'confirmation-message success';
                confirmMessage.classList.remove('hidden');
                confirmSpinner.classList.add('hidden');
                confirmationButtonsDiv.style.display = 'none'; 

                setTimeout(() => {
                    closeCadastroManutencaoModal();
                }, 2000);

            } catch (error) {
                confirmMessage.textContent = error.message;
                confirmMessage.className = 'confirmation-message error';
                confirmMessage.classList.remove('hidden');

                confirmSpinner.classList.add('hidden');
                confirmSaveButton.disabled = false;
                cancelSaveButton.disabled = false;
            }
        });

        function cancelSaveManutencao() {
            confirmationModal.classList.remove('is-active');
        }

        function closeConfirmationModal() {
            confirmationModal.classList.remove('is-active');
        }

        if (matrizManutencaoBtn) {
            matrizManutencaoBtn.addEventListener('click', () => openMaintenanceModal('corretiva', 'pendente', 'maintenance'));
        }
        if (controleOcorrenciaBtn) {
            controleOcorrenciaBtn.addEventListener('click', () => openMaintenanceModal('preditiva', 'concluido', 'maintenance'));
        }
        if (instalarEquipamentoBtn) {
            instalarEquipamentoBtn.addEventListener('click', () => openMaintenanceModal('instalação', 'pendente', 'installation'));
        }