document.addEventListener('DOMContentLoaded', function () {
    const filterContainer = document.getElementById('filterContainer');
    const solicitacoesContainer = document.getElementById('solicitacoesContainer');
    const loadingMessage = document.getElementById('loadingMessage');
    const campoPesquisa = document.getElementById('campoPesquisa');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const statusFilters = document.getElementById('statusFilters');

    let allData = null;
    let activeCity = 'todos';
    let activeStatus = 'todos';
    let totalSolicitacoes = 0; // Variável para rastrear o número de solicitações

    // --- FUNÇÕES DE LÓGICA PRINCIPAL ---
    async function fetchData() {
        const searchTerm = campoPesquisa.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        try {
            const params = new URLSearchParams({
                search: searchTerm,
                data_inicio: startDate,
                data_fim: endDate,
                status: activeStatus
            });
            const response = await fetch(`API/get_solicitacoes.php?${params.toString()}`);
            const result = await response.json();
            
            if (result.success) {
                loadingMessage.style.display = 'none';
                allData = result.data;
                totalSolicitacoes = result.total_count; // Atualiza a contagem total
                renderAllSolicitacoes(allData);
                updateCityFilters(allData.cidades || []);
                updateDisplay();
            } else {
                loadingMessage.style.display = 'none';
                solicitacoesContainer.innerHTML = `<p class="mensagem erro">${result.message || 'Nenhuma solicitação encontrada.'}</p>`;
                updateCityFilters([]);
            }
        } catch (error) {
            loadingMessage.style.display = 'none';
            console.error('Erro ao buscar dados:', error);
            solicitacoesContainer.innerHTML = `<p class="mensagem erro">Ocorreu um erro ao carregar os dados.</p>`;
        }
    }

    async function checkForUpdates() {
        try {
            const response = await fetch('API/get_solicitacoes_count.php');
            const result = await response.json();
            if (result.success && result.total_count !== totalSolicitacoes) {
                console.log('Novas atualizações encontradas, atualizando a lista...');
                fetchData();
            }
        } catch (error) {
            console.error('Erro ao verificar atualizações:', error);
        }
    }

    function renderAllSolicitacoes(data) {
        const { solicitacoes } = data;
        solicitacoesContainer.innerHTML = '';
        if (solicitacoes && Object.keys(solicitacoes).length > 0) {
            Object.keys(solicitacoes).sort().forEach(cidade => {
                const cityGroup = document.createElement('div');
                cityGroup.className = 'city-group';
                cityGroup.dataset.city = cidade;

                let cityGridHTML = '';
                solicitacoes[cidade].forEach(item => {
                    cityGridHTML += createSolicitacaoHTML(item);
                });

                cityGroup.innerHTML = `
                    <h2 class="city-group-title">${cidade}</h2>
                    <div class="solicitacoes-grid">${cityGridHTML}</div>
                `;
                
                solicitacoesContainer.appendChild(cityGroup);
            });
        } else {
            solicitacoesContainer.innerHTML = `<p class="mensagem">Nenhuma solicitação encontrada para os filtros selecionados.</p>`;
        }
    }
    
    function createSolicitacaoHTML(item) {
        const statusClass = item.status_solicitacao.replace(' ', '-');
        const statusText = item.status_solicitacao.charAt(0).toUpperCase() + item.status_solicitacao.slice(1);
        const desdobramentoHTML = item.desdobramento_soli ? `<div class="detail-item"><strong>Desdobramento:</strong> <span>${item.desdobramento_soli}</span></div>
        <div class="detail-item"><strong>Data conclusão:</strong> <span>${item.data_conclusao || 'N/A'}</span></div>` : '';

        let actionsHTML = '';
        const isConcluido = item.status_solicitacao === 'concluido';
        const isCancelado = item.status_solicitacao === 'cancelado';

        if (isConcluido || isCancelado) {
            actionsHTML = `
                <div class="item-actions">
                    <button class="item-btn edit-btn" onclick="abrirModalEdicaoSolicitacao(${item.id_solicitacao})">Editar</button>
                </div>`;
        } else {
            const isPendente = item.status_solicitacao === 'pendente';
            actionsHTML = `
                <div class="item-actions">
                    <button class="item-btn concluir-btn" onclick="abrirModalConcluirSolicitacao(${item.id_solicitacao})">Concluir</button>
                    <button class="item-btn edit-btn" onclick="abrirModalEdicaoSolicitacao(${item.id_solicitacao})">Editar</button>
                    ${isPendente ? `<button class="item-btn cancel-btn" onclick="excluirSolicitacao(${item.id_solicitacao})">Excluir</button>` : ''}
                </div>`;
        }

        return `
            <div class="item-solicitacao status-${statusClass}">
                <div class="solicitacao-header"><h3>${item.solicitante}</h3></div>
                <div class="solicitacao-details">
                    <div class="detail-item"><strong>Cidade:</strong> <span>${item.nome_cidade || 'N/A'}</span></div>
                    <div class="detail-item"><strong>Tipo:</strong> <span>${item.tipo_solicitacao || 'N/A'}</span></div>
                    <div class="detail-item"><strong>Solicitação:</strong> <span>${item.desc_solicitacao || 'N/A'}</span></div>
                    <div class="detail-item"><strong>Data solicitação:</strong> <span>${item.data_solicitacao || 'N/A'}</span></div>
                    ${desdobramentoHTML}
                    <div class="detail-item"><strong>Status:</strong> <span class="status-tag">${statusText}</span></div>
                    <div class="detail-item"><strong>Adicionado por:</strong> <span>${item.nome_usuario || 'N/A'}</span></div>
                </div>
                ${actionsHTML}
            </div>`;
    }

    function updateCityFilters(cities) {
        filterContainer.innerHTML = '';
        const allButton = document.createElement('button');
        allButton.className = 'filter-btn active';
        allButton.dataset.city = 'todos';
        allButton.textContent = 'Todas as Cidades';
        filterContainer.appendChild(allButton);
        cities.forEach(cidade => {
            const button = document.createElement('button');
            button.className = 'filter-btn';
            button.dataset.city = cidade;
            button.textContent = cidade;
            filterContainer.appendChild(button);
        });
        filterContainer.addEventListener('click', (event) => {
            if (event.target.classList.contains('filter-btn')) {
                document.querySelectorAll('#filterContainer .filter-btn').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
                activeCity = event.target.dataset.city;
                updateDisplay();
            }
        });
    }

    function updateDisplay() {
        document.querySelectorAll('.city-group').forEach(group => {
            group.style.display = (activeCity === 'todos' || group.dataset.city === activeCity) ? 'block' : 'none';
        });
    }

    // --- FUNÇÕES DE MODAL E FORMULÁRIOS ---
    function exibirMensagem(elemento, mensagem, tipo) {
        elemento.textContent = mensagem;
        elemento.className = `mensagem ${tipo}`;
        elemento.style.display = 'block';
    }

    function alternarCarregamento(botao, mostrar) {
        botao.disabled = mostrar;
    }

    window.fecharModalAdicionarSolicitacao = () => document.getElementById('modalAdicionarSolicitacao').classList.remove('esta-ativo');
    window.fecharModalEdicaoSolicitacao = () => document.getElementById('modalEdicaoSolicitacao').classList.remove('esta-ativo');
    window.fecharModalConcluirSolicitacao = () => document.getElementById('modalConcluirSolicitacao').classList.remove('esta-ativo');

    async function carregarDropdowns(tipoModal) {
        const selectCidades = document.getElementById(`idCidade${tipoModal}`);
        if (selectCidades) selectCidades.innerHTML = '<option value="">Carregando...</option>';
        try {
            const res = await fetch('API/get_cidades.php');
            const data = await res.json();
            if (selectCidades && data.success) {
                selectCidades.innerHTML = '<option value="">Selecione a Cidade</option>';
                data.cidades.forEach(cidade => {
                    selectCidades.innerHTML += `<option value="${cidade.id_cidade}">${cidade.nome}</option>`;
                });
            }
        } catch (error) {
            console.error('Erro ao carregar dropdowns:', error);
        }
    }

    window.abrirModalAdicionarSolicitacao = function() {
        const modal = document.getElementById('modalAdicionarSolicitacao');
        const formulario = document.getElementById('formularioAdicionarSolicitacao');
        const botao = document.getElementById('botaoSalvarAdicionar');
        const desdobramentoContainer = document.getElementById('desdobramentoContainerAdicionar');
        const statusInput = document.getElementById('statusSolicitacaoAdicionar');
        
        formulario.reset();
        document.getElementById('mensagemAdicionarSolicitacao').style.display = 'none';
        botao.style.display = 'flex';
        
        desdobramentoContainer.style.display = 'none';
        document.getElementById('desdobramentoSoliAdicionar').required = false;
        botao.querySelector('span').textContent = 'Adicionar';
        statusInput.value = 'pendente';
        modal.querySelectorAll('.status-btn').forEach(btn => btn.classList.remove('active'));
        modal.querySelector('.status-btn[data-status="pendente"]').classList.add('active');
        
        alternarCarregamento(botao, false);
        modal.classList.add('esta-ativo');
        carregarDropdowns('Adicionar'); 
    }

    window.abrirModalEdicaoSolicitacao = function(id) {
        const cidadeEncontrada = Object.keys(allData.solicitacoes).find(cidade => allData.solicitacoes[cidade].some(s => s.id_solicitacao == id));
        const solicitacao = cidadeEncontrada ? allData.solicitacoes[cidadeEncontrada].find(s => s.id_solicitacao == id) : null;
        if (solicitacao) {
            const formulario = document.getElementById('formularioEdicaoSolicitacao');
            const botao = document.getElementById('botaoSalvarEdicao');
            
            document.getElementById('idSolicitacaoEdicao').value = solicitacao.id_solicitacao;
            document.getElementById('usuarioEdicao').value = solicitacao.nome_usuario;
            document.getElementById('cidadeEdicao').value = solicitacao.nome_cidade;
            document.getElementById('solicitanteEdicao').value = solicitacao.solicitante;
            document.getElementById('tipoSolicitacaoEdicao').value = solicitacao.tipo_solicitacao || '';
            document.getElementById('descSolicitacaoEdicao').value = solicitacao.desc_solicitacao || '';
            document.getElementById('statusSolicitacaoEdicao').value = solicitacao.status_solicitacao;
            
            document.getElementById('mensagemEdicaoSolicitacao').style.display = 'none';
            botao.style.display = 'flex';
            alternarCarregamento(botao, false);
            document.getElementById('modalEdicaoSolicitacao').classList.add('esta-ativo');
        }
    }

    window.abrirModalConcluirSolicitacao = function(id) {
        const cidadeEncontrada = Object.keys(allData.solicitacoes).find(cidade => allData.solicitacoes[cidade].some(s => s.id_solicitacao == id));
        const solicitacao = cidadeEncontrada ? allData.solicitacoes[cidadeEncontrada].find(s => s.id_solicitacao == id) : null;
        if (solicitacao) {
            const formulario = document.getElementById('formularioConcluirSolicitacao');
            const botao = formulario.querySelector('.botao-salvar');
            document.getElementById('idSolicitacaoConcluir').value = solicitacao.id_solicitacao;
            document.getElementById('solicitanteConcluir').textContent = solicitacao.solicitante;
            document.getElementById('descSolicitacaoConcluir').textContent = solicitacao.desc_solicitacao;
            document.getElementById('desdobramentoSoliConcluir').value = solicitacao.desdobramento_soli || '';
            document.getElementById('mensagemConcluirSolicitacao').style.display = 'none';
            botao.style.display = 'flex';
            alternarCarregamento(botao, false);
            document.getElementById('modalConcluirSolicitacao').classList.add('esta-ativo');
        }
    }

    window.excluirSolicitacao = async function(id) {
        if (confirm('Tem certeza que deseja excluir esta solicitação?')) {
            try {
                const response = await fetch('API/delete_solicitacao.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_solicitacao: id })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Solicitação excluída com sucesso!');
                    fetchData();
                } else {
                    alert(result.message || 'Erro ao excluir solicitação.');
                }
            } catch (error) {
                console.error('Erro ao excluir solicitação:', error);
                alert('Ocorreu um erro ao excluir a solicitação. Tente novamente.');
            }
        }
    }

    // --- EVENT LISTENERS ---
    function handleFilterChange() {
        fetchData();
    }
    
    campoPesquisa.addEventListener('input', handleFilterChange);
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
    endDateInput.addEventListener('change', handleFilterChange);
    
    statusFilters.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') {
            document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');
            activeStatus = e.target.dataset.status;
            handleFilterChange();
        }
    });

    document.querySelectorAll('#modalAdicionarSolicitacao .status-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('#modalAdicionarSolicitacao .status-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const status = this.dataset.status;
            const statusInput = document.getElementById('statusSolicitacaoAdicionar');
            const desdobramentoContainer = document.getElementById('desdobramentoContainerAdicionar');
            const desdobramentoInput = document.getElementById('desdobramentoSoliAdicionar');
            const submitButtonText = document.querySelector('#formularioAdicionarSolicitacao .botao-salvar span');

            statusInput.value = status;

            if (status === 'concluido') {
                desdobramentoContainer.style.display = 'block';
                desdobramentoInput.required = true;
                submitButtonText.textContent = 'Concluir Solicitação';
            } else {
                desdobramentoContainer.style.display = 'none';
                desdobramentoInput.required = false;
                submitButtonText.textContent = 'Adicionar';
            }
        });
    });

    document.getElementById('formularioAdicionarSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemAdicionarSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        alternarCarregamento(botao, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        try {
            const response = await fetch('API/add_solicitacao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                exibirMensagem(mensagem, result.message, 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalAdicionarSolicitacao();
                    fetchData();
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao adicionar.', 'erro');
                alternarCarregamento(botao, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, false);
        }
    });

    document.getElementById('formularioEdicaoSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemEdicaoSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        alternarCarregamento(botao, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        
        delete data.nome_usuario;
        delete data.nome_cidade;

        try {
            const response = await fetch('API/update_solicitacao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                exibirMensagem(mensagem, result.message, 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalEdicaoSolicitacao();
                    fetchData();
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao atualizar.', 'erro');
                alternarCarregamento(botao, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, false);
        }
    });

    document.getElementById('formularioConcluirSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemConcluirSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        const desdobramento = document.getElementById('desdobramentoSoliConcluir').value.trim();

        if (!desdobramento) {
            exibirMensagem(mensagem, 'O campo de desdobramento é obrigatório.', 'erro');
            return;
        }
        alternarCarregamento(botao, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.action = 'concluir';
        data.desdobramento_soli = desdobramento;
        try {
            const response = await fetch('API/update_solicitacao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                exibirMensagem(mensagem, result.message, 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalConcluirSolicitacao();
                    fetchData();
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao concluir.', 'erro');
                alternarCarregamento(botao, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, false);
        }
    });

    window.onclick = function(event) {
        if (event.target.matches('.modal')) {
            event.target.classList.remove('esta-ativo');
        }
    };

     fetchData(); // Carga inicial
    setInterval(checkForUpdates, 15000); // Verifica atualizações a cada 15 segundos
});