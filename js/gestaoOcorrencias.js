document.addEventListener('DOMContentLoaded', function () {
    const loadingMessage = document.getElementById('loadingMessage');
    const ocorrenciasContainer = document.getElementById('ocorrenciasContainer');
    const actionButtons = document.querySelectorAll('.action-btn');
    const cityFilters = document.getElementById('cityFilters');
    const statusFilters = document.getElementById('statusFilters');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    let allData = [];
    let activeType = 'manutencao'; // Valor inicial
    let activeCity = 'todos';
    let activeStatus = 'todos';

    async function fetchData() {
        loadingMessage.classList.remove('hidden');
        ocorrenciasContainer.innerHTML = '';

        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        const params = new URLSearchParams({
            type: activeType,
            status: activeStatus,
            data_inicio: startDate,
            data_fim: endDate
        });

        try {
            const response = await fetch(`API/get_gestao_ocorrencias.php?${params.toString()}`);
            const result = await response.json();

            if (result.success) {
                allData = result.data;
                renderAllOcorrencias(allData);
                updateCityFilters(allData.cidades || []);
                updateDisplay();
            } else {
                ocorrenciasContainer.innerHTML = `<p>${result.message || 'Nenhuma ocorrência encontrada.'}</p>`;
                updateCityFilters([]);
            }
        } catch (error) {
            console.error('Erro ao buscar dados:', error);
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
                let cityGridHTML = '';
                ocorrencias[cidade].forEach(item => {
                    cityGridHTML += createOcorrenciaHTML(item);
                });
                cityGroup.innerHTML = `
                            <h2 class="city-group-title">${cidade}</h2>
                            <div class="city-ocorrencias-grid">${cityGridHTML}</div>
                        `;
                ocorrenciasContainer.appendChild(cityGroup);
            });
        } else {
            ocorrenciasContainer.innerHTML = `<p>Nenhuma ocorrência encontrada para os filtros selecionados.</p>`;
        }
    }

    function updateCityFilters(cities) {
        const currentActiveCityButton = cityFilters.querySelector('.filter-btn.active');
        activeCity = currentActiveCityButton ? currentActiveCityButton.dataset.city : 'todos';

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
                updateDisplay();
            });
        });
    }

    // FUNÇÃO ATUALIZADA para incluir detalhes da instalação
    function createOcorrenciaHTML(item) {
        const statusClass = item.status_reparo.replace(' ', '-');
        const statusTag = `<span class="status-tag tag-${statusClass}">${item.status_reparo}</span>`;

        let detailsHTML = '';
        const formatDate = (dateString) => {
            if (!dateString || dateString === '0000-00-00') return '';
            const date = new Date(dateString);
            return new Date(date.getTime() + date.getTimezoneOffset() * 60000).toLocaleDateString('pt-BR');
        };

        // Verifica se é uma instalação para montar o HTML específico
        if (item.tipo_manutencao === 'instalação') {

            const baseStatus = item.inst_base == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_base)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const infraStatus = item.inst_infra == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_infra)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const energiaStatus = item.inst_energia == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_energia)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
            const provStatus = item.inst_prov == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.data_provedor)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;

            detailsHTML += `<div class="detail-item"><strong>Base</strong> <span>${baseStatus}</span></div>`;

            // Adiciona o Laço apenas se o tipo de equipamento não for DOME
            if (item.tipo_equip !== 'DOME') {
                const lacoStatus = item.inst_laco == 1 ? `<span class="status-value instalado">Instalado ${formatDate(item.dt_laco)}</span>` : `<span class="status-value aguardando">Aguardando</span>`;
                detailsHTML += `<div class="detail-item"><strong>Laço</strong> <span>${lacoStatus}</span></div>`;
            }

            detailsHTML += `<div class="detail-item"><strong>Infra</strong> <span>${infraStatus}</span></div>`;
            detailsHTML += `<div class="detail-item"><strong>Energia</strong> <span>${energiaStatus}</span></div>`;
            detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${provStatus}</span></div>`;

        } else {
            // HTML padrão para manutenções
            detailsHTML += `<div class="detail-item"><strong>Problema</strong> <span>${item.ocorrencia_reparo || 'N/A'}</span></div>`;
            if (item.reparo_finalizado) {
                detailsHTML += `<div class="detail-item"><strong>Reparo Realizado</strong> <span>${item.reparo_finalizado}</span></div>`;
            }
        }

        // Adiciona informações comuns a ambos
        detailsHTML += `<div class="detail-item"><strong>Início</strong> <span>${new Date(item.inicio_reparo).toLocaleString('pt-BR')}</span></div>`;
        if (item.fim_reparo) {
            detailsHTML += `<div class="detail-item"><strong>Conclusão</strong> <span>${new Date(item.fim_reparo).toLocaleString('pt-BR')}</span></div>`;
        }
        if (item.tecnicos_nomes) {
            detailsHTML += `<div class="detail-item"><strong>Técnicos</strong> <span>${item.tecnicos_nomes}</span></div>`;
        }
        if (item.nome_prov && item.tipo_manutencao !== 'instalação') { // Provedor já mostrado em instalação
            detailsHTML += `<div class="detail-item"><strong>Provedor</strong> <span>${item.nome_prov}</span></div>`;
        }
        detailsHTML += `<div class="detail-item"><strong>Status</strong> <span>${statusTag}</span></div>`;


        return `
                    <div class="ocorrencia-item status-${statusClass}" data-city="${item.cidade}">
                        <div class="ocorrencia-header">
                            <h3>${item.nome_equip} - ${item.referencia_equip}</h3>
                        </div>
                        <div class="ocorrencia-details">${detailsHTML}</div>
                    </div>`;
    }

    function updateDisplay() {
        document.querySelectorAll('.city-group').forEach(group => {
            group.style.display = (activeCity === 'todos' || group.dataset.city === activeCity) ? 'block' : 'none';
        });
    }

    // Listener para os botões de TIPO (Manutenção/Instalação)
    actionButtons.forEach(button => {
        button.addEventListener('click', () => {
            actionButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            activeType = button.dataset.type;
            fetchData(); // Busca os novos dados
        });
    });

    statusFilters.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') {
            document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');
            activeStatus = e.target.dataset.status;
            fetchData();
        }
    });

    startDateInput.addEventListener('change', fetchData);
    endDateInput.addEventListener('change', fetchData);

    fetchData();
});