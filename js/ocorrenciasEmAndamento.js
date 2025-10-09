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

    const controlsWrapper = document.querySelector('.controls-wrapper');

    const btnVoltarAoTopo = document.getElementById("btnVoltarAoTopo");

     const camposInstalacao = document.getElementById('camposInstalacao');
    const camposCorretiva = document.getElementById('camposCorretiva');
    const footerInstalacao = document.getElementById('footerInstalacao');
    const footerCorretiva = document.getElementById('footerCorretiva');
    const concluirModalTitle = document.getElementById('concluirModalTitle');

    const dataBaseInput = document.getElementById('dataBase');
    const dataLacoInput = document.getElementById('dataLaco');
    const dataInfraInput = document.getElementById('dataInfra');
    const dataEnergiaInput = document.getElementById('dataEnergia');
    const dataProvedorInput = document.getElementById('dataProvedor');

    const btnSalvarProgresso = document.getElementById('btnSalvarProgresso');
    const btnConcluirInstalacao = document.getElementById('btnConcluirInstalacao');
    
    const partialConfirmModal = document.getElementById('partialConfirmModal');
    const fullConfirmModal = document.getElementById('fullConfirmModal');
    const listaItensConcluidos = document.getElementById('listaItensConcluidos');
    const btnConfirmarParcial = document.getElementById('btnConfirmarParcial');
    const btnCancelarParcial = document.getElementById('btnCancelarParcial');
    const btnConfirmarTotal = document.getElementById('btnConfirmarTotal');
    const btnCancelarTotal = document.getElementById('btnCancelarTotal');



    // --- VARIÁVEIS DE ESTADO ---
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let allData = null;
    let currentEditingId = null;
    let isSimplifiedViewActive = false;

    let currentChecksum = null;
    let updateTimeoutId = null;
    const BASE_INTERVAL = 15000; // Intervalo inicial: 15 segundos
    const MAX_INTERVAL = 120000; // Intervalo máximo: 2 minutos
    let currentInterval = BASE_INTERVAL;

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
    async function fetchData() {
        pageLoader.style.display = 'flex';
        ocorrenciasContainer.innerHTML = '';

        const params = new URLSearchParams({
            data_inicio: startDateInput.value,
            data_fim: endDateInput.value || startDateInput.value
        });

        const apiUrl = `API/get_ocorrencias_em_andamento.php?${params.toString()}`;

        try {
            const response = await fetch(apiUrl);
            const result = await response.json();

            if (result.success) {
                allData = result.data;
                // ATUALIZA O CHECKSUM LOCAL com o valor vindo da API
                currentChecksum = result.checksum;
                applyFiltersAndRender();
            } else {
                allData = null;
                ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                updateCityFilters([]);
            }

        } catch (error) {
            console.error('Erro ao buscar dados:', error);
            ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados. Tente novamente.</p>`;
        } finally {
            pageLoader.style.display = 'none';
        }
    }

    // --- FUNÇÃO DE AUTO-UPDATE ---
    async function scheduleNextCheck() {
        if (updateTimeoutId) {
            clearTimeout(updateTimeoutId);
        }

        try {
            // Chama o script central de verificação com o contexto correto para esta página.
            const checkResponse = await fetch('API/check_updates.php?context=ocorrencias_em_andamento');
            const checkResult = await checkResponse.json();

            // Compara o checksum do servidor com o checksum local
            if (checkResult.success && checkResult.checksum !== currentChecksum) {
                console.log('Novas atualizações de ocorrências detectadas. Recarregando...');
                await fetchData(); // Recarrega os dados
                currentInterval = BASE_INTERVAL; // Reseta o intervalo para o valor base
                console.log('Intervalo de verificação de ocorrências resetado.');
            } else {
                // Se não houver mudança, aumenta o intervalo para a próxima verificação
                currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
                console.log(`Nenhuma atualização. Próxima verificação de ocorrências em ${currentInterval / 1000}s.`);
            }
        } catch (error) {
            console.error('Erro no ciclo de verificação de atualizações:', error);
            // Em caso de erro, também aumenta o intervalo para evitar sobrecarga
            currentInterval = Math.min(currentInterval * 2, MAX_INTERVAL);
        } finally {
            // Agenda a próxima verificação, independentemente do resultado
            updateTimeoutId = setTimeout(scheduleNextCheck, currentInterval);
        }
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
                typeMatch = ['corretiva', 'preventiva', 'preditiva', 'afixar'].includes(item.tipo_manutencao);
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
            <div class="detail-item"><strong>Ocorrência:</strong> <span>${item.ocorrencia_reparo.toUpperCase() || ''}</span></div>
            <div class="detail-item"><strong>Técnico(s):</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
            <div class="detail-item"><strong>Veículo(s):</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
            <div class="detail-item"><strong>Início Ocorrência:</strong> ${inicioOcorrenciaHTML}</div>
            <div class="detail-item"><strong>Status:</strong> ${statusHTML}</div>
            <div class="detail-item"><strong>Local:</strong> <span>${item.local_completo || ''}</span></div>
        `;
        } else if (item.tipo_manutencao === 'instalação') {
            cardHeader = `${item.nome_equip} - ${item.referencia_equip}`;
            const tipoEquip = item.tipo_equip || 'Não especificado';
            detailsHTML += `<div class="detail-item"><strong>Tipo de Equip.:</strong> <span>${tipoEquip}</span></div>`;

            // Lógica para Qtd. Faixas
            const typesThatAlwaysShowFaixas = ['LAP', 'MONITOR DE SEMÁFORO', 'LOMBADA ELETRÔNICA', 'RADAR FIXO'];
            if (typesThatAlwaysShowFaixas.some(t => tipoEquip.includes(t)) || (tipoEquip.includes('EDUCATIVO') && item.qtd_faixa)) {
                detailsHTML += `<div class="detail-item"><strong>Qtd. Faixa(s):</strong> <span>${item.qtd_faixa}</span></div>`;
            }

            // Lógica para Passos de Instalação
             let statusMap = { inst_laco: 'Laço', inst_base: 'Base', inst_infra: 'Infra', inst_energia: 'Energia' };

            // Regra 1: Equipamentos específicos
            if (tipoEquip.includes('CCO') ) {
                delete statusMap.inst_laco;
                delete statusMap.inst_base;
            } else if (tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP') || tipoEquip.includes('DOME')) {
                delete statusMap.inst_laco;
            }

            // Regra 2: Etiqueta
            const tiposComEtiqueta = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'MONITOR DE SEMÁFORO'];
            const precisaEtiqueta = tiposComEtiqueta.some(tipo => tipoEquip.includes(tipo));
            if (precisaEtiqueta && item.etiqueta_feita != 1) {
                delete statusMap.inst_infra;
                delete statusMap.inst_energia;
            }

            // Regra 3: Provedor
            const prerequisitos = Object.keys(statusMap);
            const todosPrerequisitosOK = prerequisitos.every(passo => item[passo] == 1);
            if (todosPrerequisitosOK) {
                statusMap.inst_prov = 'Provedor';
            }
            
            const dateMap = { inst_laco: 'dt_laco', inst_base: 'dt_base', inst_infra: 'data_infra', inst_energia: 'dt_energia', inst_prov: 'data_provedor' }; // Provedor adicionado
            
            detailsHTML += Object.entries(statusMap).map(([key, label]) => {
                const status = item[key] == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item[dateMap[key]])}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                return `<div class="detail-item"><strong>${label}:</strong> <span>${status}</span></div>`;
            }).join('');
            
            // Detalhes restantes
            detailsHTML += `
                <div class="detail-item"><strong>Local:</strong> <span>${item.local_completo || ''}</span></div>
                <div class="detail-item"><strong>Início Ocorrência:</strong> ${inicioOcorrenciaHTML}</div>
                <div class="detail-item"><strong>Técnico(s):</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
                <div class="detail-item"><strong>Veículo(s):</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
                <div class="detail-item"><strong>Tempo Instalação:</strong> <span>${tempoReparo}</span></div>
                <div class="detail-item"><strong>Status:</strong> ${statusHTML}</div>`;
        } else {
            // Lógica para outros tipos de manutenção
            cardHeader = `${item.nome_equip} - ${item.referencia_equip}`;
            detailsHTML = `
            <div class="detail-item"><strong>Ocorrência:</strong> <span class="ocorrencia-tag status-em-andamento">${item.ocorrencia_reparo.toUpperCase() || ''}</span></div>
            <div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes || 'Não atribuído'}</span></div>
            <div class="detail-item"><strong>Veículo(s)</strong> <span>${item.veiculos_nomes || 'Nenhum'}</span></div>
            <div class="detail-item"><strong>Início Ocorrência</strong> ${inicioOcorrenciaHTML}</div>
            <div class="detail-item"><strong>Status</strong> ${statusHTML}</div>
            <div class="detail-item"><strong>Local</strong> <span>${item.local_completo || ''}</span></div>
            <div class="detail-item"><strong>Tempo de Reparo</strong> <span>${tempoReparo}</span></div>
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

        const allCorretivas = Object.values(allData.ocorrencias).flat().filter(item =>
            ['corretiva', 'afixar'].includes(item.tipo_manutencao)
        );

        if (allCorretivas.length === 0) {
            simplifiedView.innerHTML = '<h2>RESUMO DE PRIORIDADES</h2><p>Nenhuma manutenção corretiva em andamento encontrada.</p>';
            return;
        }

        const urgentes = [], padrao = [], semUrgencia = [];

        allCorretivas.forEach(item => {
            if (cityFilter !== 'todos' && item.cidade !== cityFilter) return;
            switch (String(item.nivel_ocorrencia)) {
                case '1': urgentes.push(item); break;
                case '3': semUrgencia.push(item); break;
                case '2':
                default: padrao.push(item); break;
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
                // Criamos o cabeçalho completo com um container, o título com a seta, e o novo botão.
                sectionHtml += `
            <div class="cidade-header-simplificado">
                <h3 class="cidade-toggle" onclick="toggleCityList(this)">
                    <span class="arrow-toggle">&#9660;</span>${city}
                </h3>
                <button class="toggle-dias-btn" onclick="toggleDiasVisibilidade(this, event)">Ocultar Dias</button>
            </div>
            <ul>`; // A lista <ul> vem logo depois

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
                    const dateInfoHtml = `<div class="card-dias-simplificado"><span class="dias-simplificado">${diasEmAberto}</span></div>`;
                    sectionHtml += `
            <li class="${cssClass}">
                ${dateInfoHtml}
                <div style="margin-right: 150px;"> 
                    <strong>${displayName} - ${firstItem.referencia_equip}</strong>: ${problemas}
                </div>
            </li>`;
                }
                sectionHtml += `</ul>`;
            }
            return sectionHtml;
        };

        let urgenteHtml = buildPrioritySection(urgentes, 'prioridade-urgente');
        let padraoHtml = buildPrioritySection(padrao, 'prioridade-padrao');
        let semUrgenciaHtml = buildPrioritySection(semUrgencia, 'prioridade-sem-urgencia');

        // ADICIONADO: Títulos para cada seção de prioridade.
        if (urgenteHtml) {
            urgenteHtml = `<h2 class="simplified-section-title">OCORRÊNCIAS URGENTES </h2>` + urgenteHtml;
        }
        if (padraoHtml) {
            padraoHtml = `<h2 class="simplified-section-title">OCORRÊNCIAS - NÍVEL 1</h2>` + padraoHtml;
        }
        if (semUrgenciaHtml) {
            semUrgenciaHtml = `<h2 class="simplified-section-title">OCORRÊNCIAS SEM URGÊNCIA - NÍVEL 2</h2>` + semUrgenciaHtml;
        }

        const finalHtml = [urgenteHtml, padraoHtml, semUrgenciaHtml].filter(Boolean).join('<hr class="section-divider">');

        if (finalHtml === '') {
            simplifiedView.innerHTML = `<h2>RESUMO DE PRIORIDADES</h2><p>Nenhuma manutenção corretiva encontrada para ${cityFilter}.</p>`;
        } else {
            simplifiedView.innerHTML = `<h2>RESUMO DE PRIORIDADES</h2>` + finalHtml;
        }
    }

    window.toggleCityList = function (h3Element) {
        // Encontra a lista de ocorrências (<ul>)
        const list = h3Element.parentElement.nextElementSibling;
        // Encontra o <span> que contém a seta
        const arrow = h3Element.querySelector('.arrow-toggle');
        //Encontra o botão "Ocultar/Exibir Dias" no mesmo cabeçalho >>>
        const diasButton = h3Element.parentElement.querySelector('.toggle-dias-btn');

        // Verifica se a lista está escondida para decidir a ação
        if (list.classList.contains('hidden')) {
            // Ação: EXPANDIR
            list.classList.remove('hidden');
            arrow.innerHTML = '&#9660;'; // Seta para baixo ▼
            // Mostra o botão de dias novamente
            if (diasButton) {
                diasButton.classList.remove('hidden');
            }
        } else {
            // Ação: MINIMIZAR
            list.classList.add('hidden');
            arrow.innerHTML = '&#9654;'; // Seta para o lado ▶
            // Esconde o botão de dias junto com a lista
            if (diasButton) {
                diasButton.classList.add('hidden');
            }
        }
    };

    window.toggleDiasVisibilidade = function (buttonElement, event) {
        // Impede que o clique no botão também acione o toggle da cidade (expandir/recolher)
        event.stopPropagation();

        // Encontra a lista (<ul>) associada a este cabeçalho
        const listElement = buttonElement.parentElement.nextElementSibling;
        if (!listElement) return;

        // Encontra todos os elementos de dias dentro desta lista
        const diasElements = listElement.querySelectorAll('.card-dias-simplificado');

        // Verifica o estado atual pelo texto do botão
        const isVisible = buttonElement.textContent === 'Ocultar Dias';

        if (isVisible) {
            diasElements.forEach(el => el.classList.add('hidden'));
            buttonElement.textContent = 'Exibir Dias';
        } else {
            diasElements.forEach(el => el.classList.remove('hidden'));
            buttonElement.textContent = 'Ocultar Dias';
        }
    };

    function toggleView(showSimplified) {
        isSimplifiedViewActive = showSimplified;

        // Pega as referências dos elementos que serão movidos/alterados
        const leftControlsTarget = mainControls.querySelector('.action-buttons'); // Onde os filtros vão entrar

        if (showSimplified) {

            // Move o filterContainer para a mesma linha dos botões
            leftControlsTarget.insertAdjacentElement('beforebegin', filterContainer);
            // Adiciona a classe para remover bordas e margens
            filterContainer.classList.add('inline-view');

            // Esconde os outros controles
            mainControls.querySelector('.action-buttons').classList.add('hidden');
            mainControls.querySelector('.date-filter-container').classList.add('hidden');
            searchInput.parentElement.classList.add('hidden');

            // Mostra/esconde as views principais
            ocorrenciasContainer.classList.add('hidden');
            simplifiedView.classList.remove('hidden');

        } else {
            // Devolve o filterContainer para sua posição original (abaixo do search)
            controlsWrapper.insertAdjacentElement('afterend', filterContainer);
            // Remove a classe de formatação inline
            filterContainer.classList.remove('inline-view');

            // Mostra os outros controles
            mainControls.querySelector('.action-buttons').classList.remove('hidden');
            mainControls.querySelector('.date-filter-container').classList.remove('hidden');
            searchInput.parentElement.classList.remove('hidden');

            // Mostra/esconde as views principais
            ocorrenciasContainer.classList.remove('hidden');
            simplifiedView.classList.add('hidden');
        }

        // Lógica para ativar/desativar botões e recarregar dados (permanece a mesma)
        voltarBtnFooter.classList.toggle('hidden', showSimplified);
        btnSimplificado.classList.toggle('active', showSimplified);

        if (showSimplified) {
            actionButtons.forEach(btn => btn.classList.remove('active'));
            generateSimplifiedView(activeCity);
        } else {
            document.getElementById('btnManutencoes').classList.add('active');
            activeType = 'manutencao';
            applyFiltersAndRender();
        }
    }
    // --- EVENTOS DE INTERAÇÃO ---
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
    startDateInput.addEventListener('change', () => {
        const dataDe = startDateInput.value;

        if (dataDe) {
            endDateInput.min = dataDe;
            if (endDateInput.value && endDateInput.value < dataDe) {
                endDateInput.value = '';
            }
            endDateInput.showPicker();
        } else {
            endDateInput.min = '';
        }
        fetchData();
    });
    endDateInput.addEventListener('change', fetchData);
    searchInput.addEventListener('input', applyFiltersAndRender);


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

        const isInstalacao = item.tipo_manutencao === 'instalação';

        // Configura o modal com base no tipo
        concluirModalTitle.textContent = isInstalacao ? 'Registrar Progresso da Instalação' : 'Concluir Reparo';
        camposInstalacao.classList.toggle('hidden', !isInstalacao);
        camposCorretiva.classList.toggle('hidden', isInstalacao);
        footerInstalacao.classList.toggle('hidden', !isInstalacao);
        footerCorretiva.classList.toggle('hidden', isInstalacao);
        
        document.getElementById('concluirModalEquipName').textContent = `${item.nome_equip} - ${item.referencia_equip}`;
        
        if (isInstalacao) {
            // Lógica para preencher e mostrar/esconder campos de instalação
            const tipoEquip = item.tipo_equip || '';
            const checklist = {
                laco: dataLacoInput.closest('.item-checklist'),
                base: dataBaseInput.closest('.item-checklist'),
                infra: dataInfraInput.closest('.item-checklist'),
                energia: dataEnergiaInput.closest('.item-checklist'),
                provedor: dataProvedorInput.closest('.item-checklist'),
            };

            Object.values(checklist).forEach(el => el.classList.add('hidden')); // Esconde tudo para começar

            let passosParaMostrar = { laco: true, base: true, infra: true, energia: true };

            // Regra 1: Equipamentos
            if (tipoEquip.includes('CCO') ) {
                delete passosParaMostrar.laco;
                delete passosParaMostrar.base;
            } else if (tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP') || tipoEquip.includes('DOME')) {
                delete passosParaMostrar.laco;
            }

            // Regra 2: Etiqueta
            const tiposComEtiqueta = ['LOMBADA ELETRÔNICA', 'RADAR FIXO', 'MONITOR DE SEMÁFORO'];
            const precisaEtiqueta = tiposComEtiqueta.some(tipo => tipoEquip.includes(tipo));
            if (precisaEtiqueta && item.etiqueta_feita != 1) {
                delete passosParaMostrar.infra;
                delete passosParaMostrar.energia;
            }

            // Regra 3: Provedor
            const prerequisitos = Object.keys(passosParaMostrar);
            const todosPrerequisitosOK = prerequisitos.every(passo => item[`inst_${passo}`] == 1);
            if (todosPrerequisitosOK) {
                passosParaMostrar.provedor = true;
            }

            // Mostra os campos corretos
            Object.keys(passosParaMostrar).forEach(passo => {
                if(checklist[passo]) checklist[passo].classList.remove('hidden');
            });

            dataLacoInput.value = item.dt_laco || '';
            dataBaseInput.value = item.dt_base || '';
            dataInfraInput.value = item.data_infra || '';
            dataEnergiaInput.value = item.dt_energia || '';
            dataProvedorInput.value = item.data_provedor || '';
        } else {
            // Lógica para preencher campos de corretiva
            document.getElementById('concluirInicioReparo').value = formatDateForInput(item.inicio_periodo_reparo);
            document.getElementById('concluirFimReparo').value = formatDateForInput(item.fim_periodo_reparo);
            materiaisUtilizadosInput.value = '';
            nenhumMaterialCheckbox.checked = false;
            materiaisUtilizadosInput.disabled = false;
            lacreNaoBtn.click();
            document.getElementById('numeroLacre').value = '';
            document.getElementById('infoRompimento').value = '';

        }

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
    // Adiciona um "ouvinte" para o evento de rolagem da página
    window.onscroll = function () {
        controlarVisibilidadeBotao();
    };

    function controlarVisibilidadeBotao() {
        // Se a página for rolada mais de 20px para baixo...
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            // ...o botão aparece.
            btnVoltarAoTopo.style.display = "block";
        } else {
            // ...senão, ele desaparece.
            btnVoltarAoTopo.style.display = "none";
        }
    }

    // Adiciona a ação que acontece quando o botão é clicado
    btnVoltarAoTopo.addEventListener('click', function () {
        // Manda a página de volta para o topo de forma suave
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

      btnSalvarProgresso.addEventListener('click', () => {
        executarSalvamentoInstalacao(false); // false = não é uma conclusão
    });

    // Ação para o botão "Concluir Instalação"
    btnConcluirInstalacao.addEventListener('click', () => {
        const item = findOcorrenciaById(currentEditingId);
        const tipoEquip = item.tipo_equip || '';
        
        let passosNecessarios = ['laco', 'base', 'infra', 'energia', 'provedor'];
        if (tipoEquip.includes('CCO')) { passosNecessarios = ['infra', 'energia', 'provedor']; }
        else if (tipoEquip.includes('DOME') || tipoEquip.includes('VÍDEO MONITORAMENTO') || tipoEquip.includes('LAP')) { passosNecessarios = ['base', 'infra', 'energia', 'provedor']; }

        const allFilled = passosNecessarios.every(passo => document.getElementById(`data${passo.charAt(0).toUpperCase() + passo.slice(1)}`).value);

        if (allFilled) {
            fullConfirmModal.classList.add('is-active');
        } else {
            const preenchidos = passosNecessarios
                .filter(passo => document.getElementById(`data${passo.charAt(0).toUpperCase() + passo.slice(1)}`).value)
                .map(passo => passo.charAt(0).toUpperCase() + passo.slice(1));
            
            if (preenchidos.length === 0) {
                alert('Preencha pelo menos uma data para concluir parcialmente.');
                return;
            }
            listaItensConcluidos.innerHTML = preenchidos.map(item => `<li>${item}</li>`).join('');
            partialConfirmModal.classList.add('is-active');
        }
    });

    // Ações para os modais de confirmação
    btnConfirmarTotal.addEventListener('click', () => {
        fullConfirmModal.classList.remove('is-active');
        executarSalvamentoInstalacao(true); // true = é uma conclusão
    });
    btnCancelarTotal.addEventListener('click', () => fullConfirmModal.classList.remove('is-active'));
    btnConfirmarParcial.addEventListener('click', () => {
        partialConfirmModal.classList.remove('is-active');
        executarSalvamentoInstalacao(true); // true = é uma conclusão
    });
    btnCancelarParcial.addEventListener('click', () => partialConfirmModal.classList.remove('is-active'));

    // Nova função para salvar instalação (baseada na de manutencao_tecnico.js)
    async function executarSalvamentoInstalacao(isFinal) {
        // Lógica de status
        let novoStatus = isFinal ? 'concluido' : 'em andamento';
        
        const payload = {
            action: 'concluir_instalacao', // Nova ação para o backend
            id_manutencao: currentEditingId,
            is_final: isFinal,
            status_reparo: novoStatus,
            dt_base: dataBaseInput.value || null,
            dt_laco: dataLacoInput.value || null,
            data_infra: dataInfraInput.value || null,
            dt_energia: dataEnergiaInput.value || null,
            data_provedor: dataProvedorInput.value || null
        };
        
        try {
            const response = await fetch('API/update_ocorrencia.php', { // Usando o endpoint correto
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (!data.success) throw new Error(data.message);

            alert('Operação realizada com sucesso!');
            closeModal('concluirModal');
            fetchData();
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    }

    // --- INICIALIZAÇÃO ---
    checkAndShowSemaforicaButton();
    fetchData().then(() => {
        console.log('Carga inicial de ocorrências completa. Iniciando ciclo de verificação.');
        scheduleNextCheck();
    });
});