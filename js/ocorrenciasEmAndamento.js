document.addEventListener('DOMContentLoaded', function () {
    const actionButtons = document.querySelectorAll('.action-btn');
    const filterContainer = document.getElementById('filterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const loadingMessage = document.getElementById('loadingMessage');
    const nenhumMaterialCheckbox = document.getElementById('nenhumMaterialCheckbox');
    const materiaisUtilizadosInput = document.getElementById('materiaisUtilizados');
    const lacreSimBtn = document.getElementById('lacreSimBtn');
    const lacreNaoBtn = document.getElementById('lacreNaoBtn');
    const lacreFieldsContainer = document.getElementById('lacreFieldsContainer');

    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null;
    let currentEditingId = null;
    let updateInterval;

    async function fetchData(isUpdate = false) {
        if (!isUpdate) {
            loadingMessage.classList.remove('hidden');
            ocorrenciasContainer.innerHTML = '';
        }

        try {
            const response = await fetch('API/get_ocorrencias_em_andamento.php');
            const result = await response.json();

            const newSignature = JSON.stringify(result.data);
            const oldSignature = JSON.stringify(allData);

            if (isUpdate) {
                if (newSignature !== oldSignature) {
                    allData = result.data;
                    renderAllOcorrencias(allData);
                    updateDisplay();
                }
            } else {
                if (result.success) {
                    allData = result.data;
                    renderAllOcorrencias(allData);
                    updateCityFilters();
                    updateDisplay();
                } else {
                    ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                }
            }
        } catch (error) {
            if (!isUpdate) {
                console.error('Erro ao buscar dados:', error);
                ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
            }
        } finally {
            if (!isUpdate) {
                loadingMessage.classList.add('hidden');
            }
        }
    }

    function startAutoUpdate() {
        if (updateInterval) clearInterval(updateInterval);
        updateInterval = setInterval(() => fetchData(true), 30000);
    }

    function renderAllOcorrencias(data) {
        const { ocorrencias } = data;
        ocorrenciasContainer.innerHTML = '';
        if (ocorrencias && Object.keys(ocorrencias).length > 0) {
            for (const cidade in ocorrencias) {
                const cityGroup = document.createElement('div');
                cityGroup.className = 'city-group';
                cityGroup.dataset.city = cidade;
                let cityGridHTML = '';
                ocorrencias[cidade].forEach(item => {
                    cityGridHTML += createOcorrenciaHTML(item);
                });
                cityGroup.innerHTML = `
                            <h2 class="city-group-title">${cidade}</h2>
                            <div class="city-ocorrencias-grid">
                                ${cityGridHTML}
                            </div>
                        `;
                ocorrenciasContainer.appendChild(cityGroup);
            }
        } else {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência em andamento encontrada.</p>`;
        }
    }

    function updateCityFilters() {
        filterContainer.innerHTML = '';
        const citiesWithContent = new Set();
        document.querySelectorAll('.ocorrencia-item').forEach(item => {
            const itemType = item.dataset.type;
            let typeMatch = false;
            if (activeType === 'manutencao') {
                if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) typeMatch = true;
            } else if (activeType === 'instalação') {
                if (itemType === 'instalação') typeMatch = true;
            }
            if (typeMatch) {
                const city = item.closest('.city-group').dataset.city;
                citiesWithContent.add(city);
            }
        });
        const allButton = document.createElement('button');
        allButton.className = 'filter-btn active';
        allButton.dataset.city = 'todos';
        allButton.textContent = 'Todos';
        filterContainer.appendChild(allButton);
        Array.from(citiesWithContent).sort().forEach(cidade => {
            const button = document.createElement('button');
            button.className = 'filter-btn';
            button.dataset.city = cidade;
            button.textContent = cidade;
            filterContainer.appendChild(button);
        });
        addFilterListeners();
    }

    function createOcorrenciaHTML(item) {
        const tempoReparo = calculateRepairTime(item.inicio_periodo_reparo, item.fim_periodo_reparo);
        const tipoOcorrencia = item.tipo_manutencao === 'instalação' ? 'Instalação' : item.tipo_manutencao.charAt(0).toUpperCase() + item.tipo_manutencao.slice(1);
        const statusClass = item.status_reparo === 'pendente' ? 'status-pendente' : 'status-em-andamento';
        const statusText = item.status_reparo.charAt(0).toUpperCase() + item.status_reparo.slice(1);
        const statusHTML = `<span class="status-tag ${statusClass}">${statusText}</span>`;
        let detailsHTML = '';
        if (item.tipo_manutencao !== 'instalação') {
            detailsHTML = `
                        <div class="detail-item"><strong>Ocorrência</strong> <span class="ocorrencia-tag status-em-andamento">${item.ocorrencia_reparo || ''}</span></div>
                        <div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
                        <div class="detail-item"><strong>Veículo(s)</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        <div class="detail-item"><strong>Tempo Reparo</strong> <span>${tempoReparo}</span></div>
                    `;
        } else {
            const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
            const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
            const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;

            let lacoHTML = '';
            if (item.tipo_equip !== 'DOME' && item.tipo_equip !== 'CCO') {
                const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando instalação</span>`;
                lacoHTML = `<div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>`;
            }

            detailsHTML = `
                        <div class="detail-item stacked"><strong>Tipo</strong> <span>${tipoOcorrencia}</span></div>
                        ${lacoHTML}
                        <div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>
                        <div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>
                        <div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>
                        <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
                        <div class="detail-item"><strong>Início Ocorrência</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>
                        <div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
                        <div class="detail-item"><strong>Veículo(s)</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
                        <div class="detail-item"><strong>Tempo Instalação</strong> <span>${tempoReparo}</span></div>
                        <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
                    `;
        }
        const actionsHTML = `
                    <div class="item-actions">
                        <button class="item-btn concluir-btn" onclick="openConcluirModal(${item.id_manutencao})">Concluir</button>
                        <button class="item-btn status-btn" onclick="openConfirmationModal(${item.id_manutencao}, 'pendente')">Status</button>
                        <button class="item-btn cancel-btn" onclick="openConfirmationModal(${item.id_manutencao}, 'cancelado')">Cancelar</button>
                    </div>
                `;
        return `
                    <div class="ocorrencia-item" data-type="${item.tipo_manutencao}" data-id="${item.id_manutencao}">
                        <div class="ocorrencia-header">
                            <h3>${item.nome_equip} - ${item.referencia_equip}</h3>
                        </div>
                        <div class="ocorrencia-details">
                            ${detailsHTML}
                        </div>
                        ${actionsHTML}
                    </div>
                `;
    }

    function formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00') return '';
        const date = new Date(dateString);
        return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
    }

    function formatDateForInput(dateString) {
        if (!dateString) return '';
        return new Date(dateString).toISOString().split('T')[0];
    }

    function calculateRepairTime(startDate, endDate) {
        if (!startDate || !endDate) return "N/A";
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return `${formatDate(startDate)} até ${formatDate(endDate)} (${diffDays} dia(s))`;
    }

    function updateDisplay() {
        const cityGroups = document.querySelectorAll('.city-group');
        cityGroups.forEach(group => {
            const groupCity = group.dataset.city;
            let hasVisibleItemsInGroup = false;
            const cityMatch = activeCity === 'todos' || groupCity === activeCity;
            if (cityMatch) {
                group.querySelectorAll('.ocorrencia-item').forEach(item => {
                    const itemType = item.dataset.type;
                    let typeMatch = false;
                    if (activeType === 'manutencao') {
                        if (['corretiva', 'preventiva', 'preditiva'].includes(itemType)) typeMatch = true;
                    } else if (activeType === 'instalação') {
                        if (itemType === 'instalação') typeMatch = true;
                    }
                    if (typeMatch) {
                        item.classList.remove('hidden');
                        hasVisibleItemsInGroup = true;
                    } else {
                        item.classList.add('hidden');
                    }
                });
                group.classList.toggle('hidden', !hasVisibleItemsInGroup);
            } else {
                group.classList.add('hidden');
            }
        });
    }

    actionButtons.forEach(button => {
        button.addEventListener('click', () => {
            actionButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            activeType = button.dataset.type;
            activeCity = 'todos';
            updateCityFilters();
            updateDisplay();
        });
    });

    function addFilterListeners() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeCity = button.dataset.city;
                updateDisplay();
            });
        });
    }

    window.openModal = function (modalId) { document.getElementById(modalId).classList.add('is-active'); }
    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('is-active');
        if (modalId === 'concluirModal') {
            // Reseta o estado do botão e mensagens ao fechar
            const saveBtn = document.getElementById('saveConclusionBtn');
            const spinner = document.getElementById('conclusionSpinner');
            saveBtn.disabled = false;
            spinner.style.display = 'none';
            saveBtn.firstChild.textContent = 'Concluir Reparo';
            document.getElementById('reparoErrorMessage').classList.add('hidden');
            document.getElementById('conclusionSuccessMessage').classList.add('hidden');
        }
    }

    function findOcorrenciaById(id) {
        for (const cidade in allData.ocorrencias) {
            const found = allData.ocorrencias[cidade].find(item => item.id_manutencao == id);
            if (found) return found;
        }
        return null;
    }

    window.openConcluirModal = async function (id) {
        currentEditingId = id;
        const item = findOcorrenciaById(id);
        if (!item) return;

        document.getElementById('concluirModalEquipName').textContent = `${item.nome_equip} - ${item.referencia_equip}`;
        document.getElementById('concluirOcorrenciaText').textContent = item.ocorrencia_reparo;
        document.getElementById('reparoFinalizado').value = '';
        document.getElementById('concluirInicioReparo').value = formatDateForInput(item.inicio_periodo_reparo);
        document.getElementById('concluirFimReparo').value = formatDateForInput(item.fim_periodo_reparo);

        // Reset dos novos campos
        materiaisUtilizadosInput.value = '';
        nenhumMaterialCheckbox.checked = false;
        materiaisUtilizadosInput.disabled = false;
        lacreNaoBtn.click(); // Define 'Não' como padrão
        document.getElementById('numeroLacre').value = '';
        document.getElementById('infoRompimento').value = '';


        const tecnicosContainer = document.getElementById('concluirTecnicosContainer');
        const veiculosContainer = document.getElementById('concluirVeiculosContainer');
        tecnicosContainer.innerHTML = 'A carregar...';
        veiculosContainer.innerHTML = 'A carregar...';

        openModal('concluirModal');

        const [tecnicosRes, veiculosRes] = await Promise.all([fetch('API/get_tecnicos.php'), fetch('API/get_veiculos.php')]);
        const tecnicosData = await tecnicosRes.json();
        const veiculosData = await veiculosRes.json();

        const selectedTecnicos = item.tecnicos_nomes ? item.tecnicos_nomes.split(', ') : [];
        tecnicosContainer.innerHTML = '';
        if (tecnicosData.success) {
            tecnicosData.tecnicos.forEach(tec => {
                const btn = document.createElement('button');
                btn.className = 'choice-btn';
                btn.dataset.id = tec.id_tecnico;
                btn.textContent = tec.nome;
                if (selectedTecnicos.includes(tec.nome)) btn.classList.add('selected');
                btn.onclick = () => btn.classList.toggle('selected');
                tecnicosContainer.appendChild(btn);
            });
        }

        const selectedVeiculos = item.veiculos_nomes ? item.veiculos_nomes.split(', ').map(v => v.split(' (')[0]) : [];
        veiculosContainer.innerHTML = '';
        if (veiculosData.length > 0) {
            veiculosData.forEach(vec => {
                const btn = document.createElement('button');
                btn.className = 'choice-btn';
                btn.dataset.id = vec.id_veiculo;
                btn.textContent = `${vec.nome} (${vec.placa})`;
                if (selectedVeiculos.includes(vec.nome)) btn.classList.add('selected');
                btn.onclick = () => btn.classList.toggle('selected');
                veiculosContainer.appendChild(btn);
            });
        }
    }

    window.saveConclusion = async function () {
        const reparoFinalizadoInput = document.getElementById('reparoFinalizado');
        const reparoFinalizado = reparoFinalizadoInput.value;
        const inicioReparo = document.getElementById('concluirInicioReparo').value;
        const fimReparo = document.getElementById('concluirFimReparo').value;
        const tecnicos = Array.from(document.querySelectorAll('#concluirTecnicosContainer .choice-btn.selected')).map(btn => btn.dataset.id);
        const veiculos = Array.from(document.querySelectorAll('#concluirVeiculosContainer .choice-btn.selected')).map(btn => btn.dataset.id);
        const errorMessageDiv = document.getElementById('reparoErrorMessage');

        // Limpa mensagens de erro anteriores
        errorMessageDiv.classList.add('hidden');
        errorMessageDiv.textContent = '';

        // Validação
        if (!reparoFinalizado.trim()) {
            errorMessageDiv.textContent = 'Por favor, descreva o reparo realizado.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        const hoje = new Date().toISOString().split('T')[0];
        if (inicioReparo < hoje) {
            errorMessageDiv.textContent = 'A data de início não pode ser anterior à data atual.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        if (fimReparo < inicioReparo) {
            errorMessageDiv.textContent = 'A data de fim não pode ser anterior à data de início.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        if (tecnicos.length === 0) {
            errorMessageDiv.textContent = 'Selecione pelo menos um técnico.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        if (veiculos.length === 0) {
            errorMessageDiv.textContent = 'Selecione pelo menos um veículo.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        // Validação dos novos campos
        let materiais = materiaisUtilizadosInput.value.trim();
        if (nenhumMaterialCheckbox.checked) {
            materiais = 'Nenhum material utilizado';
        } else if (!materiais) {
            errorMessageDiv.textContent = 'Informe os materiais utilizados ou marque "Nenhum".';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        const rompimentoLacre = lacreSimBtn.classList.contains('selected');
        let numeroLacre = null, infoRompimento = null;

        if (rompimentoLacre) {
            numeroLacre = document.getElementById('numeroLacre').value.trim();
            infoRompimento = document.getElementById('infoRompimento').value.trim();
            if (!numeroLacre || !infoRompimento) {
                errorMessageDiv.textContent = 'Preencha as informações sobre o rompimento do lacre.';
                errorMessageDiv.classList.remove('hidden');
                return;
            }
        }


        const dataToSend = {
            action: 'concluir_reparo',
            id_manutencao: currentEditingId,
            reparo_finalizado: reparoFinalizado,
            inicio_reparo: inicioReparo,
            fim_reparo: fimReparo,
            tecnicos: tecnicos,
            veiculos: veiculos,
            materiais_utilizados: materiais,
            rompimento_lacre: rompimentoLacre,
            numero_lacre: numeroLacre,
            info_rompimento: infoRompimento
        };

        const saveBtn = document.getElementById('saveConclusionBtn');
        const spinner = document.getElementById('conclusionSpinner');

        // Desativa o botão e mostra o spinner
        saveBtn.disabled = true;
        spinner.style.display = 'inline-block';
        saveBtn.firstChild.textContent = 'Salvando...';

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                const successMsg = document.getElementById('conclusionSuccessMessage');
                successMsg.textContent = 'Ocorrência concluída com sucesso!';
                successMsg.classList.remove('hidden');

                setTimeout(() => {
                    closeModal('concluirModal');
                    fetchData();
                }, 2000); // Fecha o modal após 2 segundos
            } else {
                errorMessageDiv.textContent = 'Erro ao concluir: ' + result.message;
                errorMessageDiv.classList.remove('hidden');
                // Reativa o botão em caso de erro
                saveBtn.disabled = false;
                spinner.style.display = 'none';
                saveBtn.firstChild.textContent = 'Concluir Reparo';
            }
        } catch (error) {
            errorMessageDiv.textContent = 'Erro de comunicação com o servidor.';
            errorMessageDiv.classList.remove('hidden');
            // Reativa o botão em caso de erro
            saveBtn.disabled = false;
            spinner.style.display = 'none';
            saveBtn.firstChild.textContent = 'Concluir Reparo';
        }
    }

    window.openConfirmationModal = function (id, status) {
        const title = status === 'pendente' ? 'Voltar para Pendente' : 'Cancelar Ocorrência';
        const text = status === 'pendente' ? 'Tem a certeza de que deseja voltar esta ocorrência para o estado "Pendente"?' : 'Tem a certeza de que deseja cancelar esta ocorrência? Esta ação não pode ser desfeita.';

        document.getElementById('confirmationModalTitle').textContent = title;
        document.getElementById('confirmationModalText').textContent = text;

        const confirmBtn = document.getElementById('confirmActionButton');
        confirmBtn.onclick = () => executeStatusChange(id, status);

        openModal('confirmationModal');
    }

    async function executeStatusChange(id, status) {
        const dataToSend = {
            action: 'update_status',
            id_manutencao: id,
            status: status,
            origem: 'manutencao'
        };

        try {
            const response = await fetch('API/update_ocorrencia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dataToSend)
            });
            const result = await response.json();
            if (result.success) {
                closeModal('confirmationModal');
                fetchData();
            } else {
                alert('Erro ao alterar o status: ' + result.message);
            }
        } catch (error) {
            alert('Erro de comunicação com o servidor.');
        }
    }

    // Listeners para os novos campos
    nenhumMaterialCheckbox.addEventListener('change', () => {
        if (nenhumMaterialCheckbox.checked) {
            materiaisUtilizadosInput.value = 'Nenhum material utilizado';
            materiaisUtilizadosInput.disabled = true;
        } else {
            materiaisUtilizadosInput.value = '';
            materiaisUtilizadosInput.disabled = false;
        }
    });

    lacreSimBtn.addEventListener('click', () => {
        lacreSimBtn.classList.add('selected');
        lacreNaoBtn.classList.remove('selected');
        lacreFieldsContainer.classList.remove('hidden');
    });

    lacreNaoBtn.addEventListener('click', () => {
        lacreNaoBtn.classList.add('selected');
        lacreSimBtn.classList.remove('selected');
        lacreFieldsContainer.classList.add('hidden');
    });


    fetchData();
    startAutoUpdate();
});