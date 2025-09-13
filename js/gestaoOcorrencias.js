// ===== INÍCIO DO CÓDIGO COMPLETO E CORRIGIDO PARA gestaoOcorrencias.js =====

document.addEventListener('DOMContentLoaded', function () {
    // =================================================================================
    // 1. REFERÊNCIAS AOS ELEMENTOS DO DOM
    // =================================================================================
    const loadingMessage = document.getElementById('loadingMessage');
    const cityFilters = document.getElementById('cityFilters');
    const statusFilters = document.getElementById('statusFilters');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const dashboardView = document.getElementById('dashboardView');
    const ocorrenciasView = document.getElementById('ocorrenciasView');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const btnDashboard = document.getElementById('btnDashboard');
    const btnManutencoes = document.getElementById('btnManutencoes');
    const btnInstalacoes = document.getElementById('btnInstalacoes');
    const btnSemaforica = document.getElementById('btnSemaforica');
    const searchInput = document.getElementById('searchInput');
    const btnClearFilters = document.getElementById('btnClearFilters');
    const evolucaoTitle = document.getElementById('evolucaoTitle');
    const concluidasLabel = document.getElementById('concluidasLabel');
    const afericoesLabel = document.getElementById('afericoesLabel');
    const btnLimparFiltroData = document.getElementById('limparFiltroData');
    const searchContainer = document.getElementById('searchContainer');

    const btnSimplificado = document.getElementById('btnSimplificado');
    const simplifiedView = document.getElementById('simplifiedView');
    const dateClearWrapper = document.querySelector('.date-clear-wrapper');
    const filtersWrapper = document.querySelector('.filters-wrapper'); // <<< ADICIONE ESTA LINHA





    // =================================================================================
    // 2. VARIÁVEIS DE ESTADO
    // =================================================================================
    let activeType = 'manutencao';
    let activeCity = 'todos';
    let activeStatus = 'todos';
    let charts = {};

    let allOcorrenciasData = null;
    let isSimplifiedViewActive = false;

    // =================================================================================
    // 3. FUNÇÕES DE APOIO (HELPERS)
    // =================================================================================
    const formatDate = (dateString) => {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === '0000-00-00') return '';
        const date = new Date(dateString);
        const userTimezoneOffset = date.getTimezoneOffset() * 60000;
        return new Date(date.getTime() + userTimezoneOffset).toLocaleDateString('pt-BR');
    };
    const formatDateTime = (dateTimeString) => {
        if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00') return '';
        const date = new Date(dateTimeString);
        return date.toLocaleString('pt-BR');
    };
    function calculateDaysOpen(startDateString) {
        if (!startDateString || startDateString === '0000-00-00 00:00:00') return '';
        const startDate = new Date(startDateString);
        const today = new Date();
        startDate.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        const diffTime = Math.abs(today - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays === 0) return '(Hoje)';
        if (diffDays === 1) return '(1 dia)';
        return `(${diffDays} dias)`;
    }


    // =================================================================================
    // 4. LÓGICA DE UX DOS FILTROS DE DATA
    // =================================================================================
    function calculateCompletionTime(startDateString, endDateString) {
        if (!startDateString || !endDateString || endDateString === '0000-00-00 00:00:00') {
            return null;
        }
        const start = new Date(startDateString);
        const end = new Date(endDateString);
        const diffMinutes = Math.round((end - start) / (1000 * 60));

        if (diffMinutes < 1) return null; // Não mostra se for menos de 1 minuto
        if (diffMinutes === 1) return 'Concluído em 1 minuto';
        if (diffMinutes < 60) return `Concluído em ${diffMinutes} minutos`;

        const diffHours = Math.round(diffMinutes / 60);
        if (diffHours === 1) return 'Concluído em 1 hora';
        if (diffHours < 24) return `Concluído em ${diffHours} horas`;

        const diffDays = Math.round(diffHours / 24);
        if (diffDays === 1) return 'Concluído em 1 dia';
        return `Concluído em ${diffDays} dias`;
    }



    // =================================================================================
    // 5. CONTROLE DAS VISUALIZAÇÕES (VIEWS)
    // =================================================================================
    function switchView(viewName) {
        dashboardView.classList.add('hidden');
        ocorrenciasView.classList.add('hidden');
        if (viewName === 'dashboard') {
            dashboardView.classList.remove('hidden');
            btnLimparFiltroData.style.display = 'block';
            searchContainer.classList.add('hidden');
            filtersWrapper.classList.add('hidden'); // <<< ADICIONE ESTA LINHA


            loadDashboardData();
        } else {
            ocorrenciasView.classList.remove('hidden');
            btnLimparFiltroData.style.display = 'none';
            searchContainer.classList.remove('hidden')
            filtersWrapper.classList.remove('hidden');;

            fetchOcorrencias();
        }
        document.querySelectorAll('.main-actions-filter .action-btn, .left-actions .action-btn').forEach(btn => btn.classList.remove('active'));
        if (viewName === 'dashboard') {
            btnDashboard.classList.add('active');
        } else {
            const activeBtn = document.querySelector(`.action-btn[data-type="${activeType}"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }
    }

    // =================================================================================
    // 6. LÓGICA DO DASHBOARD
    // =================================================================================
    async function loadDashboardData() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const params = new URLSearchParams({ data_inicio: startDate, data_fim: endDate });
        try {
            const response = await fetch(`API/get_dashboard_data.php?${params.toString()}`);
            const result = await response.json();
            if (result.success) {
                const data = result.data;
                updateKpiCards(data);
                renderStatusChart(data.manutencoes_por_status || []);
                renderTipoChart(data.manutencoes_por_tipo || []);
                renderCidadeChart(data.manutencoes_abertas_cidade || []);
                renderEvolucaoDiariaChart(data.evolucao_diaria || []);
            } else { console.error('Falha ao carregar dados do dashboard:', result.message); }
        } catch (error) { console.error('Erro de rede ao buscar dados do dashboard:', error); }
    }

    function updateKpiCards(data) {
        document.querySelector('#kpiManutencoesAbertas .kpi-number').textContent = data.kpi_manutencoes_abertas || 0;
        document.querySelector('#kpiConcluidasMes .kpi-number').textContent = data.kpi_concluidas_mes || 0;
        document.querySelector('#kpiMttr .kpi-number').textContent = data.kpi_mttr || 'N/A';
        document.querySelector('#kpiAfericoesVencendo .kpi-number').textContent = data.kpi_afericoes_vencendo || 0;
    }

    function generateSimplifiedView() {
        if (!allOcorrenciasData || !allOcorrenciasData.ocorrencias) {
            simplifiedView.innerHTML = '<p>Não há dados para exibir no resumo.</p>';
            return;
        }

        const statusPermitidos = ['pendente', 'em andamento', 'concluido'];
        let itensFiltrados = Object.values(allOcorrenciasData.ocorrencias).flat();

        if (activeStatus !== 'todos') {
            itensFiltrados = itensFiltrados.filter(item => item.status_reparo === activeStatus);
        } else {
            itensFiltrados = itensFiltrados.filter(item => statusPermitidos.includes(item.status_reparo));
        }

        if (activeCity !== 'todos') {
            itensFiltrados = itensFiltrados.filter(item => item.cidade === activeCity);
        }

        if (itensFiltrados.length === 0) {
            simplifiedView.innerHTML = `<h2>RESUMO DE OCORRÊNCIAS</h2><p>Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
            return;
        }

        const itensPorCidade = {};
        itensFiltrados.forEach(item => {
            if (!itensPorCidade[item.cidade]) itensPorCidade[item.cidade] = [];
            itensPorCidade[item.cidade].push(item);
        });

        let finalHtml = '<h2>RESUMO DE OCORRÊNCIAS</h2>';
        const sortedCities = Object.keys(itensPorCidade).sort();

        for (const cidade of sortedCities) {
            // <<< MODIFICAÇÃO AQUI >>>
            // Adicionamos a classe 'cidade-toggle', o onclick, e o span da seta.
            // A seta começa para baixo (lista visível).
            finalHtml += `
            <h3 class="cidade-toggle" onclick="toggleCityList(this)">
                <span class="arrow-toggle">&#9660;</span>
                ${cidade}
            </h3>
            <ul>`; // A lista <ul> vem logo depois

            const itensPorEquipamento = {};
            for (const item of itensPorCidade[cidade]) {
                const groupingStatus = (item.status_reparo === 'pendente' || item.status_reparo === 'em andamento')
                    ? 'aberto'
                    : item.status_reparo;
                const key = `${item.nome_equip}|||${item.referencia_equip}|||${groupingStatus}`;

                if (!itensPorEquipamento[key]) itensPorEquipamento[key] = [];
                itensPorEquipamento[key].push(item);
            }

            for (const groupKey in itensPorEquipamento) {
                const groupItems = itensPorEquipamento[groupKey];
                const firstItem = groupItems[0];

                const displayName = firstItem.nome_equip.split('-')[0].trim();
                const problemas = groupItems.map(item => {
                    if (item.status_reparo === 'concluido') {
                        const tempo = calculateCompletionTime(item.inicio_reparo, item.fim_reparo);
                        const tempoDisplay = tempo ? `(${tempo})` : '';
                        return `${item.ocorrencia_reparo} -> <strong>${item.reparo_finalizado || 'Concluído'}</strong> ${tempoDisplay}`;
                    }
                    return item.ocorrencia_reparo;
                }).join('; ');

                const diasEmAberto = (firstItem.status_reparo !== 'concluido') ? calculateDaysOpen(firstItem.inicio_reparo) : '';
                const statusClass = `status-${firstItem.status_reparo.replace(/ /g, '-')}`;

                const dateInfoHtml = `<div class="card-dias-simplificado"><span class="dias-simplificado">${diasEmAberto}</span></div>`;

                finalHtml += `
                <li class="${statusClass}">
                    ${diasEmAberto ? dateInfoHtml : ''}
                    <div style="margin-right: 150px;"> 
                        <strong>${displayName}</strong> - ${firstItem.referencia_equip}: ${problemas}
                    </div>
                </li>`;
            }
            finalHtml += `</ul>`;
        }
        simplifiedView.innerHTML = finalHtml;
    }

    window.toggleCityList = function (h3Element) {
        // Encontra a lista de ocorrências (<ul>) que é o próximo elemento irmão do <h3>
        const list = h3Element.nextElementSibling;
        // Encontra o <span> que contém a seta
        const arrow = h3Element.querySelector('.arrow-toggle');

        // Verifica se a lista está escondida
        if (list.classList.contains('hidden')) {
            // Se estiver, mostra a lista e muda a seta para baixo
            list.classList.remove('hidden');
            arrow.innerHTML = '&#9660;'; // Seta para baixo ▼
        } else {
            // Se estiver visível, esconde a lista e muda a seta para o lado
            list.classList.add('hidden');
            arrow.innerHTML = '&#9654;'; // Seta para o lado ▶
        }
    };

    function toggleView(showSimplified) {
        isSimplifiedViewActive = showSimplified;

        // Controla as views principais
        dashboardView.classList.add('hidden');
        ocorrenciasView.classList.toggle('hidden', showSimplified);
        simplifiedView.classList.toggle('hidden', !showSimplified);

        // Controla os filtros
        searchContainer.classList.toggle('hidden', showSimplified);
        dateClearWrapper.classList.toggle('hidden', showSimplified);
        filtersWrapper.classList.toggle('hidden', !showSimplified); // Mostra os filtros na visão simplificada

        // Ajusta os botões "ativos"
        document.querySelectorAll('.main-actions-filter .action-btn, .left-actions .action-btn').forEach(btn => btn.classList.remove('active'));
        btnSimplificado.classList.toggle('active', showSimplified);


        if (showSimplified) {
            cityFilters.classList.remove('hidden'); // Garante que os filtros de cidade estejam visíveis
            generateSimplifiedView(); // Gera a view com os filtros ativos
        }
    }
    function renderChart(chartId, chartConfig) { if (charts[chartId]) { charts[chartId].destroy(); } const ctx = document.getElementById(chartId).getContext('2d'); charts[chartId] = new Chart(ctx, chartConfig); }
    function renderStatusChart(data) { const labels = data.map(item => item.status_reparo); const values = data.map(item => item.total); renderChart('manutencoesPorStatusChart', { type: 'doughnut', data: { labels, datasets: [{ label: 'Status', data: values, backgroundColor: ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#6b7280'] }] }, options: { responsive: true, plugins: { legend: { position: 'top' } } } }); }
    function renderTipoChart(data) { const labels = data.map(item => item.tipo_manutencao); const values = data.map(item => item.total); renderChart('manutencoesPorTipoChart', { type: 'pie', data: { labels, datasets: [{ label: 'Tipo', data: values, backgroundColor: ['#6366f1', '#a855f7', '#ec4899', '#14b8a6', '#f43f5e'] }] }, options: { responsive: true, plugins: { legend: { position: 'top' } } } }); }
    function renderCidadeChart(data) { const labels = data.map(item => item.nome); const values = data.map(item => item.total); renderChart('manutencoesPorCidadeChart', { type: 'bar', data: { labels, datasets: [{ label: 'Manutenções Abertas', data: values, backgroundColor: '#3b82f6' }] }, options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } } }); }
    function renderEvolucaoDiariaChart(data) { const hasDateFilter = startDateInput.value && endDateInput.value; const labels = data.map(item => { const date = new Date(item.dia + 'T00:00:00'); return hasDateFilter ? date.toLocaleDateString('pt-BR') : date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' }); }); const abertasData = data.map(item => item.abertas); const fechadasData = data.map(item => item.fechadas); renderChart('evolucaoDiariaChart', { type: 'line', data: { labels, datasets: [{ label: 'Abertas', data: abertasData, borderColor: '#3b82f6', backgroundColor: '#3b82f620', fill: true, tension: 0.3 }, { label: 'Concluídas', data: fechadasData, borderColor: '#22c55e', backgroundColor: '#22c55e20', fill: true, tension: 0.3 }] }, options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } } }); }

    // =================================================================================
    // 7. LÓGICA DA LISTA DE OCORRÊNCIAS
    // =================================================================================
    async function fetchOcorrencias() {
        loadingMessage.classList.remove('hidden');
        ocorrenciasContainer.innerHTML = '';
        const params = new URLSearchParams({ type: activeType, status: activeStatus, data_inicio: startDateInput.value, data_fim: endDateInput.value, search: searchInput.value });
        try {
            const response = await fetch(`API/get_gestao_ocorrencias.php?${params.toString()}`);
            const result = await response.json();
            if (result.success) {
                allOcorrenciasData = result.data;

                // Se a visão simplificada estiver ativa, gera o resumo. Senão, mostra os cards.
                if (isSimplifiedViewActive) {
                    generateSimplifiedView();
                } else {
                    renderAllOcorrencias(result.data);
                }

                updateCityFilters(result.data.cidades || []);
                updateDisplay();
            } else {
                ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                updateCityFilters([]);
            }
        } catch (error) {
            console.error('Erro ao buscar dados de ocorrências:', error);
            ocorrenciasContainer.innerHTML = `<p>Ocorreu um erro ao carregar os dados.</p>`;
        } finally {
            loadingMessage.classList.add('hidden');
        }
    }

    function renderAllOcorrencias(data) {
        const { ocorrencias } = data;
        ocorrenciasContainer.innerHTML = '';
        if (ocorrencias && Object.keys(ocorrencias).length > 0) {
            Object.keys(ocorrencias).sort().forEach(cidade => {
                const cityGroup = document.createElement('div');
                cityGroup.className = 'city-group';
                cityGroup.dataset.city = cidade;

                const itemsToRender = [];
                const groupedItems = {};
                // MODIFICADO: Define quais status devem ser agrupados
                const statusToGroup = ['pendente', 'em andamento'];

                (ocorrencias[cidade] || []).forEach(item => {
                    // Se o item for corretiva E o status for um dos que devem ser agrupados...
                    if (item.tipo_manutencao === 'corretiva' && statusToGroup.includes(item.status_reparo)) {
                        const key = `${item.nome_equip}|${item.referencia_equip}`;
                        if (!groupedItems[key]) {
                            groupedItems[key] = [];
                        }
                        groupedItems[key].push(item);
                    } else {
                        // Outros itens são adicionados como estão
                        itemsToRender.push({ isGrouped: false, details: [item] });
                    }
                });

                for (const key in groupedItems) {
                    const groupDetails = groupedItems[key];
                    if (groupDetails.length > 1) {
                        itemsToRender.push({ isGrouped: true, details: groupDetails });
                    } else {
                        itemsToRender.push({ isGrouped: false, details: groupDetails });
                    }
                }

                let cityGridHTML = itemsToRender.map(group => createOcorrenciaHTML(group)).join('');
                cityGroup.innerHTML = `<h2 class="city-group-title">${cidade}</h2><div class="city-ocorrencias-grid">${cityGridHTML}</div>`;
                ocorrenciasContainer.appendChild(cityGroup);
            });
        } else {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
        }
    }

    function updateCityFilters(cities) {
        cityFilters.innerHTML = '';
        const allButton = document.createElement('button');
        allButton.className = 'filter-btn';
        allButton.dataset.city = 'todos';
        allButton.textContent = 'Todas as Cidades';
        if (activeCity === 'todos') allButton.classList.add('active');
        cityFilters.appendChild(allButton);
        cities.forEach(cidade => {
            const button = document.createElement('button');
            button.className = 'filter-btn';
            button.dataset.city = cidade;
            button.textContent = cidade;
            if (activeCity === cidade) button.classList.add('active');
            cityFilters.appendChild(button);
        });
        document.querySelectorAll('#cityFilters .filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('#cityFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeCity = button.dataset.city;
                if (isSimplifiedViewActive) {
                    generateSimplifiedView(activeCity);
                } else {
                    updateDisplay();
                }
            });
        });
        document.querySelectorAll('#cityFilters .filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('#cityFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeCity = button.dataset.city;

                // SE A VISÃO SIMPLIFICADA ESTIVER ATIVA, RECARREGA ELA
                if (isSimplifiedViewActive) {
                    generateSimplifiedView();
                } else {
                    updateDisplay();
                }
            });
        });
    }

    function updateDisplay() {
        document.querySelectorAll('.city-group').forEach(group => {
            group.style.display = (activeCity === 'todos' || group.dataset.city === activeCity) ? 'block' : 'none';
        });
    }
    function createOcorrenciaHTML(group) {
        const firstItem = group.details[0];
        const headerTitle = `${firstItem.nome_equip} - ${firstItem.referencia_equip}`;
        let detailsHTML = '';

        const statusClass = `status-${(firstItem.status_reparo || 'pendente').replace(/ /g, '-')}`;

        // Calcula e formata o tempo de conclusão para ser usado no cabeçalho
        const tempoConclusao = calculateCompletionTime(firstItem.inicio_reparo, firstItem.fim_reparo);
        const tagClassConclusao = `tag-${statusClass}`;
        const tempoConclusaoTag = tempoConclusao ? `<div class="detail-item" style="margin-top: 0.5rem;"><span class="status-tag ${tagClassConclusao}">${tempoConclusao}</span></div>` : '';

        const headerHTML = `
        <div class="ocorrencia-header">
            <h3>${headerTitle}</h3>
            ${tempoConclusaoTag}
        </div>
    `;

        // --- LÓGICA PARA GRUPOS DE OCORRÊNCIAS (PENDENTES OU EM ANDAMENTO) ---
        if (group.isGrouped) {
            const tagClass = `tag-${(firstItem.status_reparo || '').replace(/ /g, '-')}`;

            let ocorrenciasListHTML = group.details.map(ocor => {
                const dataInicioFormatada = formatDate(ocor.inicio_reparo);
                const diasEmAberto = calculateDaysOpen(ocor.inicio_reparo);
                const solicitadoPorHTML = ocor.atribuido_por ? `<div style="font-size: 0.9em; color: #6b7280; padding-left: 20px; margin-top: 2px;"><strong>Solicitado por</strong> ${ocor.atribuido_por}</div>` : '';
                const tecnicosHTML = ocor.tecnicos_nomes ? `<div style="font-size: 0.9em; color: #6b7280; padding-left: 20px; margin-top: 2px;"><strong>Técnico(s)</strong> ${ocor.tecnicos_nomes}</div>` : '';

                return `<li>
                <span class="status-tag ${tagClass}">${(ocor.ocorrencia_reparo || '').toUpperCase()}</span>
                <div style="font-size: 0.9em; color: #6b7280; padding-left: 5px; margin-top: 5px;">
                    <strong>Data Ocorrência</strong> ${dataInicioFormatada} ${diasEmAberto}
                </div>
                ${solicitadoPorHTML}
                ${tecnicosHTML}
            </li>`;
            }).join('');

            detailsHTML = `
            <div class="detail-item"><strong>Ocorrências</strong></div>
            <ol class="ocorrencia-list">${ocorrenciasListHTML}</ol>
            <div class="detail-item"><strong>Status</strong> <span class="status-tag ${tagClass}">${firstItem.status_reparo}</span></div>
            <div class="detail-item"><strong>Local</strong> <span>${firstItem.local_completo || 'N/A'}</span></div>
        `;
            return `
            <div class="ocorrencia-item ${statusClass}" data-city="${firstItem.cidade}">
                ${headerHTML}
                <div class="ocorrencia-details">${detailsHTML}</div>
            </div>`;
        }

        // --- LÓGICA PADRÃO PARA ITENS ÚNICOS ---
        const item = firstItem;
        const itemStatusClass = `status-${(item.status_reparo || '').replace(/ /g, '-')}`;
        const itemTagClass = `tag-${(item.status_reparo || '').replace(/ /g, '-')}`;
        const statusTag = `<span class="status-tag ${itemTagClass}">${item.status_reparo}</span>`;
        const solicitadoPorHTML = item.atribuido_por ? `<div class="detail-item"><strong>Solicitado por</strong> <span>${item.atribuido_por}</span></div>` : '';
        const dataInicioFormatada = formatDate(item.inicio_reparo);

        // MODIFICADO: Remove o tempo em aberto para ocorrências concluídas/canceladas
        let diasEmAberto = calculateDaysOpen(item.inicio_reparo);
        if (item.status_reparo === 'concluido' || item.status_reparo === 'cancelado') {
            diasEmAberto = '';
        }

        if (item.tipo_manutencao === 'instalação') {
            const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const provStatus = item.inst_prov == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_provedor)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            detailsHTML += `<div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>`;
            if (item.tipo_equip !== 'DOME') {
                const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                detailsHTML += `<div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>`;
            }
            detailsHTML += `<div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>`;
            detailsHTML += `<div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>`;
            detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${provStatus}</span></div>`;
            detailsHTML += solicitadoPorHTML;
        } else {
            detailsHTML += `<div class="detail-item"><strong>Ocorrência</strong> <span class="status-tag ${itemTagClass}">${(item.ocorrencia_reparo || 'N/A').toUpperCase()}</span></div>`;
            detailsHTML += `<div class="detail-item"><strong>Data Ocorrência</strong> <span>${dataInicioFormatada} ${diasEmAberto}</span></div>`;
            detailsHTML += solicitadoPorHTML;
            if (item.reparo_finalizado) {
                detailsHTML += `<div class="detail-item"><strong>Reparo Realizado</strong> <span class="status-tag ${itemTagClass}">${item.reparo_finalizado}</span></div>`;
            }
        }

        // MODIFICADO: Usa formatDate para remover as horas da data de conclusão
        if (item.fim_reparo && item.fim_reparo !== '0000-00-00 00:00:00') {
            detailsHTML += `<div class="detail-item"><strong>Data Conclusão</strong> <span>${formatDate(item.fim_reparo)}</span></div>`;
        }
        if (item.tecnicos_nomes) {
            detailsHTML += `<div class="detail-item"><strong>Técnico(s)</strong> <span>${item.tecnicos_nomes}</span></div>`;
        }
        if (item.nome_prov && item.tipo_manutencao !== 'instalação') {
            detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${item.nome_prov}</span></div>`;
        }
        detailsHTML += `<div class="detail-item"><strong>Status</strong> ${statusTag}</div>`;
        detailsHTML += `<div class="detail-item"><strong>Local</strong> <span>${item.local_completo || 'N/A'}</span></div>`;

        return `<div class="ocorrencia-item ${itemStatusClass}" data-city="${item.cidade}">
                ${headerHTML}
                <div class="ocorrencia-details">${detailsHTML}</div>
            </div>`;
    }
    // =================================================================================
    // 8. EVENT LISTENERS E FUNÇÕES DE APOIO
    // =================================================================================
    function updateDashboardTitles() { const hasDateFilter = startDateInput.value && endDateInput.value; if (hasDateFilter) { evolucaoTitle.textContent = 'Evolução no Período'; concluidasLabel.textContent = 'Concluídas no Período'; afericoesLabel.textContent = 'Aferições Vencendo no Período'; } else { evolucaoTitle.textContent = 'Evolução Mensal (Todo o Período)'; concluidasLabel.textContent = 'Concluídas (Todo o Período)'; afericoesLabel.textContent = 'Aferições a Vencer (Total)'; } }
    function handleDateChange() { const isDashboardActive = !dashboardView.classList.contains('hidden'); if (isDashboardActive) { updateDashboardTitles(); loadDashboardData(); } else { fetchOcorrencias(); } }
    function clearFilters() { searchInput.value = ''; startDateInput.value = ''; endDateInput.value = ''; startDateInput.max = ''; endDateInput.min = ''; activeStatus = 'todos'; activeCity = 'todos'; activeType = 'manutencao'; document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active')); document.querySelector('#statusFilters .filter-btn[data-status="todos"]').classList.add('active'); switchView('ocorrencias'); }
    btnDashboard.addEventListener('click', () => {
        if (isSimplifiedViewActive) toggleView(false); // Desativa a visão simplificada se estiver ativa
        updateDashboardTitles();
        switchView('dashboard');
    });

    btnManutencoes.addEventListener('click', () => {
        if (isSimplifiedViewActive) toggleView(false); // Desativa a visão simplificada se estiver ativa
        activeType = 'manutencao';
        switchView('ocorrencias');
    });
    btnInstalacoes.addEventListener('click', () => {
        if (isSimplifiedViewActive) toggleView(false); // Desativa a visão simplificada se estiver ativa
        activeType = 'instalacao';
        switchView('ocorrencias');
    });
    btnSemaforica.addEventListener('click', () => { activeType = 'semaforica'; alert('Funcionalidade Semafórica em desenvolvimento.'); document.querySelectorAll('.main-actions-filter .action-btn, .left-actions .action-btn').forEach(btn => btn.classList.remove('active')); btnSemaforica.classList.add('active'); dashboardView.classList.add('hidden'); ocorrenciasView.classList.add('hidden'); });
    statusFilters.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') {
            document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');
            activeStatus = e.target.dataset.status;

            // SE A VISÃO SIMPLIFICADA ESTIVER ATIVA, RECARREGA ELA
            if (isSimplifiedViewActive) {
                generateSimplifiedView();
            } else {
                fetchOcorrencias();
            }
        }
    });
    startDateInput.addEventListener('change', () => {
        const dataDe = startDateInput.value;

        if (dataDe) {
            // 1. Trava as datas anteriores no campo "Até"
            endDateInput.min = dataDe;

            // o campo "Até" será limpo para evitar um intervalo inválido.
            if (endDateInput.value && endDateInput.value < dataDe) {
                endDateInput.value = '';
            }

            // 2. Abre o calendário do campo "Até" automaticamente
            endDateInput.showPicker();

        } else {
            // Se o campo "De" for limpo, remove a restrição de data mínima do campo "Até"
            endDateInput.min = '';
        }

        // Chama a função original para recarregar os dados do dashboard/lista
        handleDateChange();
    });
    endDateInput.addEventListener('change', handleDateChange);
    searchInput.addEventListener('input', () => { if (ocorrenciasView.classList.contains('hidden')) { switchView('ocorrencias'); } else { fetchOcorrencias(); } });
    btnClearFilters.addEventListener('click', clearFilters);
    btnLimparFiltroData.addEventListener('click', () => {
        startDateInput.value = '';
        endDateInput.value = '';
        // Reutiliza a função que já recarrega os dados do dashboard
        handleDateChange();
    });

    btnClearFilters.addEventListener('click', clearFilters);
    btnSimplificado.addEventListener('click', () => {
        // Apenas alterna a interface, sem chamar a lógica de dados ainda
        toggleView(!isSimplifiedViewActive);

        // Se a visão simplificada foi ativada, agora verificamos os dados
        if (isSimplifiedViewActive) {
            // Se já temos os dados carregados, apenas geramos a lista
            if (allOcorrenciasData) {
                generateSimplifiedView();
            } else {
                // Se for o primeiro clique, buscamos os dados na API
                fetchOcorrencias();
            }
        }
    });

    // =================================================================================
    // 9. INICIALIZAÇÃO
    // =================================================================================
    updateDashboardTitles();
    switchView('dashboard');
});

// ===== FIM DO CÓDIGO COMPLETO E CORRIGIDO PARA gestaoOcorrencias.js =====