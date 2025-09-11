document.addEventListener('DOMContentLoaded', function () {
    // --- REFERÊNCIAS AOS ELEMENTOS (ANTIGOS E NOVOS) ---
    const actionButtons = document.querySelectorAll('.action-btn');
    const filterContainer = document.getElementById('filterContainer');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const pageLoader = document.getElementById('pageLoader');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const searchInput = document.getElementById('searchInput');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const btnSimplificado = document.getElementById('btnSimplificado');
    const simplifiedView = document.getElementById('simplifiedView');
    const mainControls = document.querySelector('.main-controls-container');
    const voltarBtnFooter = document.querySelector('.voltar-btn');
    const btSemaforica = document.getElementById('btSemaforica');

    // Referências para o modal de conclusão
    const nenhumMaterialCheckbox = document.getElementById('nenhumMaterialCheckbox');
    const materiaisUtilizadosInput = document.getElementById('materiaisUtilizados');
    const lacreSimBtn = document.getElementById('lacreSimBtn');
    const lacreNaoBtn = document.getElementById('lacreNaoBtn');
    const lacreFieldsContainer = document.getElementById('lacreFieldsContainer');

    // --- VARIÁVEIS DE ESTADO ---
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null;
    let currentEditingId = null;
    let updateInterval;
    let isSimplifiedViewActive = false;

    // ---  VERIFICAR SEMAFÓRICAS ---
    async function checkAndShowSemaforicaButton() {
        try {
            const response = await fetch('API/check_semaforicas_em_andamento.php');
            const result = await response.json();

            // A condição verifica se a chamada foi um sucesso e se 'has_pending' é true
            if (result.success && result.data.has_pending) {
                // Garante que estamos nos referindo ao elemento correto pelo ID
                const btSemaforica = document.getElementById('btSemaforica');
                if (btSemaforica) {
                    btSemaforica.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Erro ao verificar ocorrências semafóricas em andamento:', error);
        }
    }

    function calculateDaysOpen(startDateString) {
        const startDate = new Date(startDateString);
        const today = new Date();
        // Zera o horário para comparar apenas as datas
        startDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);

        const diffTime = Math.abs(today - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            return 'Hoje';
        } else if (diffDays === 1) {
            return '(1 dia)';
        } else {
            return `(${diffDays} dias)`;
        }
    }
    // --- FUNÇÃO DE BUSCA DE DADOS ATUALIZADA ---
    async function fetchData(isUpdate = false) {
        if (!isUpdate) {
            pageLoader.style.display = 'flex';
            ocorrenciasContainer.innerHTML = '';
        }

        const params = new URLSearchParams({
            data_inicio: startDateInput.value,
            data_fim: endDateInput.value || startDateInput.value
        });

        // A API agora busca todos os tipos, a filtragem será feita no frontend
        const apiUrl = `API/get_ocorrencias_em_andamento.php?${params.toString()}`;

        try {
            const response = await fetch(apiUrl);
            const result = await response.json();
            const newSignature = JSON.stringify(result.data);
            const oldSignature = JSON.stringify(allData);

            if (isUpdate) {
                if (newSignature !== oldSignature) {
                    allData = result.data;
                    applyFiltersAndRender();
                }
            } else {
                if (result.success) {
                    allData = result.data;
                    applyFiltersAndRender();
                } else {
                    allData = null;
                    ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                    updateCityFilters([]);
                }
            }
        } catch (error) {
            if (!isUpdate) {
                console.error('Erro ao buscar dados:', error);
                ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
            }
        } finally {
            if (!isUpdate) {
                pageLoader.style.display = 'none';
            }
        }
    }

    // --- FUNÇÃO DE AUTO-UPDATE (Intervalo ajustado) ---
    function startAutoUpdate() {
        if (updateInterval) clearInterval(updateInterval);
        updateInterval = setInterval(() => fetchData(true), 10000); // Mantido 10s
    }

    // --- APLICAR FILTROS E RENDERIZAR ---
    function applyFiltersAndRender() {
        if (isSimplifiedViewActive) {
            generateSimplifiedView(activeCity);
            return;
        }

        if (!allData || !allData.ocorrencias || Object.keys(allData.ocorrencias).length === 0) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência em andamento encontrada.</p>`;
            updateCityFilters([]);
            return;
        }

        const searchTerm = searchInput.value.toLowerCase();
        let filteredOcorrencias = {};
        let citiesWithContent = new Set();

        const allItems = Object.values(allData.ocorrencias).flat();

        const itemsToRender = allItems.filter(item => {
            const searchMatch = !searchTerm ||
                (item.nome_equip && item.nome_equip.toLowerCase().includes(searchTerm)) ||
                (item.referencia_equip && item.referencia_equip.toLowerCase().includes(searchTerm)) ||
                (item.ocorrencia_reparo && item.ocorrencia_reparo.toLowerCase().includes(searchTerm));

            let typeMatch = false;
            if (activeType === 'manutencao') {
                typeMatch = ['corretiva', 'preventiva', 'preditiva'].includes(item.tipo_manutencao);
            } else {
                typeMatch = item.tipo_manutencao === activeType;
            }

            return searchMatch && typeMatch;
        });

        // Agrupar os itens filtrados por cidade
        itemsToRender.forEach(item => {
            if (!filteredOcorrencias[item.cidade]) {
                filteredOcorrencias[item.cidade] = [];
            }
            filteredOcorrencias[item.cidade].push(item);
            citiesWithContent.add(item.cidade);
        });

        renderAllOcorrencias({ ocorrencias: filteredOcorrencias });
        updateCityFilters(Array.from(citiesWithContent));
        updateDisplay();
    }


    // --- FUNÇÃO DE RENDERIZAÇÃO  ---
    function renderAllOcorrencias(data) {
        const { ocorrencias } = data;
        ocorrenciasContainer.innerHTML = ''; // Limpa o container

        // Inicializa um contador para as ocorrências semafóricas
        let semaforicaCounter = 1;

        if (ocorrencias && Object.keys(ocorrencias).length > 0) {
            Object.keys(ocorrencias).sort().forEach(cidade => {
                const cityGroup = document.createElement('div');
                cityGroup.className = 'city-group';
                cityGroup.dataset.city = cidade;

                // Mapeia cada item individualmente e passa o contador se for semafórica
                let cityGridHTML = ocorrencias[cidade].map(item => {
                    let indexParaCard = null;
                    if (item.tipo_manutencao === 'semaforica') {
                        indexParaCard = semaforicaCounter;
                        semaforicaCounter++; // Incrementa o contador para o próximo
                    }
                    return createOcorrenciaHTML(item, indexParaCard);
                }).join('');

                cityGroup.innerHTML = `
                <h2 class="city-group-title">${cidade}</h2>
                <div class="city-ocorrencias-grid">${cityGridHTML}</div>
            `;
                ocorrenciasContainer.appendChild(cityGroup);
            });
        } else {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência em andamento encontrada para os filtros selecionados.</p>`;
        }
    }

    // --- FUNÇÃO DE FILTRO DE CIDADE ATUALIZADA ---
    function updateCityFilters(cities) {
        const currentActive = filterContainer.querySelector('.active')?.dataset.city || 'todos';
        filterContainer.innerHTML = '';

        const allButton = document.createElement('button');
        allButton.className = 'filter-btn';
        allButton.dataset.city = 'todos';
        allButton.textContent = 'Todos';
        filterContainer.appendChild(allButton);

        cities.sort().forEach(cidade => {
            const button = document.createElement('button');
            button.className = 'filter-btn';
            button.dataset.city = cidade;
            button.textContent = cidade;
            filterContainer.appendChild(button);
        });

        const newActiveButton = filterContainer.querySelector(`[data-city="${currentActive}"]`) || allButton;
        newActiveButton.classList.add('active');
        activeCity = newActiveButton.dataset.city;

        addFilterListeners();
    }

    function createOcorrenciaHTML(item, semaforicaIndex = null) {
        const tempoReparo = calculateRepairTime(item.inicio_periodo_reparo, item.fim_periodo_reparo);
        const statusClass = item.status_reparo === 'pendente' ? 'status-pendente' : 'status-em-andamento';
        const statusText = item.status_reparo.charAt(0).toUpperCase() + item.status_reparo.slice(1);
        const statusHTML = `<span class="status-tag ${statusClass}">${statusText}</span>`;

        const dataInicioFormatada = new Date(item.inicio_reparo).toLocaleDateString('pt-BR');
        const diasEmAberto = calculateDaysOpen(item.inicio_reparo);
        const inicioOcorrenciaHTML = `<span>${dataInicioFormatada} (${diasEmAberto})</span>`;




        let cardHeader = '';
        let detailsHTML = '';

        if (item.tipo_manutencao === 'semaforica') {
            cardHeader = `Ocorrência Semafórica ${semaforicaIndex}`;
            detailsHTML = `
            <div class="detail-item"><strong>Referência:</strong> <span class="ocorrencia-tag status-em-andamento">${item.nome_equip}</span></div>
            <div class="detail-item"><strong>Ocorrência:</strong> <span>${item.ocorrencia_reparo || ''}</span></div>
            <div class="detail-item"><strong>Técnico(s):</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
            <div class="detail-item"><strong>Veículo(s):</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
            <div class="detail-item"><strong>Início Ocorrência:</strong> ${inicioOcorrenciaHTML}</div>
            <div class="detail-item"><strong>Status:</strong> ${statusHTML}</div>
            <div class="detail-item"><strong>Local:</strong> <span>${item.local_completo || ''}</span></div>
        `;
        } else if (item.tipo_manutencao === 'instalação') {
            cardHeader = `${item.nome_equip} - ${item.referencia_equip}`;
            // ... Lógica original para Instalação ...
            const tipoOcorrencia = item.tipo_manutencao.charAt(0).toUpperCase() + item.tipo_manutencao.slice(1);
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
        } else {
            // Lógica para outros tipos de manutenção
            cardHeader = `${item.nome_equip} - ${item.referencia_equip}`;
            detailsHTML = `
            <div class="detail-item"><strong>Ocorrência:</strong> <span class="ocorrencia-tag status-em-andamento">${item.ocorrencia_reparo || ''}</span></div>
            <div class="detail-item"><strong>Técnico(s):</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
            <div class="detail-item"><strong>Veículo(s):</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
            <div class="detail-item"><strong>Início Ocorrência:</strong> ${inicioOcorrenciaHTML}</div>
            <div class="detail-item"><strong>Status:</strong> ${statusHTML}</div>
            <div class="detail-item"><strong>Local:</strong> <span>${item.local_completo || ''}</span></div>
            <div class="detail-item"><strong>Tempo de Reparo:</strong> <span>${tempoReparo}</span></div>
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
                <h3>${cardHeader}</h3>
            </div>
            <div class="ocorrencia-details">${detailsHTML}</div>
            ${actionsHTML}
        </div>
    `;
    }

    // --- FUNÇÕES AUXILIARES ---
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

    // --- FUNÇÃO DE EXIBIÇÃO ---
    function updateDisplay() {
        const cityGroups = document.querySelectorAll('.city-group');
        let hasVisibleContent = false;

        cityGroups.forEach(group => {
            const isVisible = (activeCity === 'todos' || group.dataset.city === activeCity);
            group.classList.toggle('hidden', !isVisible);
            if (isVisible) hasVisibleContent = true;
        });

        const containerIsEmpty = ocorrenciasContainer.querySelector('.city-group:not(.hidden)') === null;
        if (containerIsEmpty && (searchInput.value || startDateInput.value)) {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência em andamento encontrada para os filtros selecionados.</p>`;
        }
    }

    // --- LÓGICA DA VISÃO SIMPLIFICADA ---
    function generateSimplifiedView(cityFilter = 'todos') {
        if (!allData || !allData.ocorrencias || Object.keys(allData.ocorrencias).length === 0) {
            simplifiedView.innerHTML = '<p>Não há dados para exibir no resumo.</p>';
            return;
        }

        const allCorretivas = Object.values(allData.ocorrencias).flat().filter(item => item.tipo_manutencao === 'corretiva');

        if (allCorretivas.length === 0) {
            simplifiedView.innerHTML = '<h2>RESUMO DE PRIORIDADES</h2><p>Nenhuma manutenção corretiva em andamento encontrada.</p>';
            return;
        }

        const urgentes = [], padrao = [], semUrgencia = [];

        allCorretivas.forEach(item => {
            if (cityFilter !== 'todos' && item.cidade !== cityFilter) return;
            switch (item.nivel_ocorrencia) {
                case '1': urgentes.push(item); break;
                case '3': semUrgencia.push(item); break;
                case '2': default: padrao.push(item); break;
            }
        });

        const buildPrioritySection = (items, cssClass) => {
            if (items.length === 0) return '';
            const itemsByCity = {};
            items.forEach(item => {
                if (!itemsByCity[item.cidade]) itemsByCity[item.cidade] = [];
                itemsByCity[item.cidade].push(item);
            });
            let sectionHtml = '';
            const sortedCities = Object.keys(itemsByCity).sort();
            for (const city of sortedCities) {
                sectionHtml += `<h3>${city}</h3><ul>`;
                const itemsByEquip = {};
                for (const item of itemsByCity[city]) {
                    const key = `${item.nome_equip}|||${item.referencia_equip}`;
                    if (!itemsByEquip[key]) itemsByEquip[key] = [];
                    itemsByEquip[key].push(item);
                }
                for (const groupKey in itemsByEquip) {
                    const groupItems = itemsByEquip[groupKey];
                    const firstItem = groupItems[0];
                    let displayName = firstItem.nome_equip.split('-')[0].trim();
                    const problemas = groupItems.map(item => item.ocorrencia_reparo).join('; ');

                    const diasEmAberto = calculateDaysOpen(firstItem.inicio_reparo);
                    const dateInfoHtml = `
                    <div class="card-dias-simplificado">
                        <span class="dias-simplificado">${diasEmAberto}</span>
                    </div>
                `;

                    sectionHtml += `
                    <li class="${cssClass}">
                        ${dateInfoHtml}
                        <div style="margin-right: 150px;"> <strong>${displayName}</strong> - ${firstItem.referencia_equip}: ${problemas}
                        </div>
                    </li>`;
                }
                sectionHtml += `</ul>`;
            }
            return sectionHtml;
        };

        const urgenteHtml = buildPrioritySection(urgentes, 'prioridade-urgente');
        const padraoHtml = buildPrioritySection(padrao, 'prioridade-padrao');
        let semUrgenciaHtml = buildPrioritySection(semUrgencia, 'prioridade-sem-urgencia');

        if (semUrgenciaHtml) {
            semUrgenciaHtml = `<h2 class="simplified-section-title">OCORRÊNCIAS SEM URGÊNCIA</h2>` + semUrgenciaHtml;
        }

        const finalHtml = [urgenteHtml, padraoHtml, semUrgenciaHtml].filter(Boolean).join('<hr class="section-divider">');

        if (finalHtml === '') {
            simplifiedView.innerHTML = `<h2>RESUMO DE PRIORIDADES</h2><p>Nenhuma manutenção corretiva encontrada para ${cityFilter}.</p>`;
        } else {
            simplifiedView.innerHTML = `<h2>RESUMO DE PRIORIDADES</h2>` + finalHtml;
        }
    }

    function toggleView(showSimplified) {
        isSimplifiedViewActive = showSimplified;
        searchInput.parentElement.classList.toggle('hidden', showSimplified);
        mainControls.querySelector('.action-buttons').classList.toggle('hidden', showSimplified);
        mainControls.querySelector('.date-filter-container').classList.toggle('hidden', showSimplified);
        ocorrenciasContainer.classList.toggle('hidden', showSimplified);
        simplifiedView.classList.toggle('hidden', !showSimplified);
        voltarBtnFooter.classList.toggle('hidden', !showSimplified);
        btnSimplificado.classList.toggle('active', showSimplified);

        if (showSimplified) {
            generateSimplifiedView(activeCity);
            actionButtons.forEach(btn => btn.classList.remove('active'));
        } else {
            document.getElementById('btnManutencoes').classList.add('active');
            activeType = 'manutencao';
            applyFiltersAndRender();
        }
    }

    // ---  EVENT LISTENERS ---
    clearFiltersBtn.addEventListener('click', () => {
        if (isSimplifiedViewActive) toggleView(false);
        searchInput.value = '';
        startDateInput.value = '';
        endDateInput.value = '';
        activeType = 'manutencao';
        activeCity = 'todos';
        actionButtons.forEach(btn => btn.classList.remove('active'));
        document.getElementById('btnManutencoes').classList.add('active');
        fetchData();
    });

    btnSimplificado.addEventListener('click', () => toggleView(!isSimplifiedViewActive));
    startDateInput.addEventListener('change', fetchData);
    endDateInput.addEventListener('change', fetchData);
    searchInput.addEventListener('input', applyFiltersAndRender);


    // --- EVENT LISTENERS --
    actionButtons.forEach(button => {
        button.addEventListener('click', () => {
            if (isSimplifiedViewActive && button.id !== 'btnSimplificado') toggleView(false);
            if (button.id !== 'btnSimplificado') {
                actionButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeType = button.dataset.type;
                applyFiltersAndRender();
            }
        });
    });

    function addFilterListeners() {
        filterContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('filter-btn')) {
                filterContainer.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                e.target.classList.add('active');
                activeCity = e.target.dataset.city;

                if (isSimplifiedViewActive) {
                    generateSimplifiedView(activeCity);
                } else {
                    updateDisplay();
                }
            }
        });
    }

    window.openModal = function (modalId) { document.getElementById(modalId).classList.add('is-active'); }
    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('is-active');
        if (modalId === 'concluirModal') {
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
        if (!allData || !allData.ocorrencias) return null;
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
        materiaisUtilizadosInput.value = '';
        nenhumMaterialCheckbox.checked = false;
        materiaisUtilizadosInput.disabled = false;
        lacreNaoBtn.click();
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
        errorMessageDiv.classList.add('hidden');
        errorMessageDiv.textContent = '';
        if (!reparoFinalizado.trim()) {
            errorMessageDiv.textContent = 'Por favor, descreva o reparo realizado.';
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
        let materiais = materiaisUtilizadosInput.value.trim();
        if (nenhumMaterialCheckbox.checked) {
            materiais = 'Nenhum material utilizado';
        } else if (!materiais) {
            errorMessageDiv.textContent = 'Informe os materiais utilizados ou marque "Nenhum".';
            errorMessageDiv.classList.remove('hidden');
            return;
        }
        const rompimentoLacre = lacreSimBtn.classList.contains('selected');
        let numeroLacre = null,
            infoRompimento = null;
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
                }, 2000);
            } else {
                errorMessageDiv.textContent = 'Erro ao concluir: ' + result.message;
                errorMessageDiv.classList.remove('hidden');
                saveBtn.disabled = false;
                spinner.style.display = 'none';
                saveBtn.firstChild.textContent = 'Concluir Reparo';
            }
        } catch (error) {
            errorMessageDiv.textContent = 'Erro de comunicação com o servidor.';
            errorMessageDiv.classList.remove('hidden');
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

    // --- INICIALIZAÇÃO ---
    checkAndShowSemaforicaButton();
    fetchData();
    startAutoUpdate();
});