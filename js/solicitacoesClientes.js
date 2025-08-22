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

    // --- FUNÇÕES DE LÓGICA PRINCIPAL ---
    async function fetchData() {
        loadingMessage.style.display = 'block';
        solicitacoesContainer.innerHTML = ''; 

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
            
            loadingMessage.style.display = 'none';
            if (result.success) {
                allData = result.data;
                renderAllSolicitacoes(allData);
                updateCityFilters(allData.cidades || []);
                updateDisplay();
            } else {
                solicitacoesContainer.innerHTML = `<p class="mensagem erro">${result.message || 'Nenhuma solicitação encontrada.'}</p>`;
                updateCityFilters([]);
            }
        } catch (error) {
            loadingMessage.style.display = 'none';
            console.error('Erro ao buscar dados:', error);
            solicitacoesContainer.innerHTML = `<p class="mensagem erro">Ocorreu um erro ao carregar os dados.</p>`;
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
                cityGroup.innerHTML = `<h2 class="city-group-title">${cidade}</h2><div class="solicitacoes-grid">${cityGridHTML}</div>`;
                solicitacoesContainer.appendChild(cityGroup);
            });
        } else {
            solicitacoesContainer.innerHTML = `<p class="mensagem">Nenhuma solicitação encontrada para os filtros selecionados.</p>`;
        }
    }
    
    function createSolicitacaoHTML(item) {
        const statusClass = item.status_solicitacao.replace(' ', '-');
        const statusText = item.status_solicitacao.charAt(0).toUpperCase() + item.status_solicitacao.slice(1);
        const isConcluido = item.status_solicitacao === 'concluido';
        const isPendente = item.status_solicitacao === 'pendente';
        const desdobramentoHTML = item.desdobramento_soli ? `<div class="detail-item"><strong>Desdobramento:</strong> <span>${item.desdobramento_soli}</span></div>
        <div class="detail-item"><strong>Data conclusão:</strong> <span>${item.data_conclusao || 'N/A'}</span></div>` : '';

        const actionsHTML = `
            <div class="item-actions">
                ${!isConcluido ? `<button class="item-btn concluir-btn" onclick="abrirModalConcluirSolicitacao(${item.id_solicitacao})">Concluir</button>` : ''}
                ${!isConcluido ? `<button class="item-btn edit-btn" onclick="abrirModalEdicaoSolicitacao(${item.id_solicitacao})">Editar</button>` : ''}
                ${isPendente ? `<button class="item-btn cancel-btn" onclick="excluirSolicitacao(${item.id_solicitacao})">Excluir</button>` : ''}
            </div>`;

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

    function alternarCarregamento(botao, spinner, mostrar) {
        const botaoTexto = botao.querySelector('span:not(.carregando)');
        spinner = botao.querySelector('.carregando');
        if (mostrar) {
            botao.disabled = true;
            if(spinner) spinner.style.display = 'block';
            if (botaoTexto) botaoTexto.style.display = 'none';
        } else {
            botao.disabled = false;
            if(spinner) spinner.style.display = 'none';
            if (botaoTexto) botaoTexto.style.display = 'flex';
        }
    }

    window.fecharModalAdicionarSolicitacao = () => document.getElementById('modalAdicionarSolicitacao').classList.remove('esta-ativo');
    window.fecharModalEdicaoSolicitacao = () => document.getElementById('modalEdicaoSolicitacao').classList.remove('esta-ativo');
    window.fecharModalConcluirSolicitacao = () => document.getElementById('modalConcluirSolicitacao').classList.remove('esta-ativo');

    async function carregarDropdowns(tipoModal) {
        const selectUsuarios = document.getElementById(`idUsuario${tipoModal}`);
        const selectCidades = document.getElementById(`idCidade${tipoModal}`);
        if (selectUsuarios) selectUsuarios.innerHTML = '<option value="">Carregando...</option>';
        if (selectCidades) selectCidades.innerHTML = '<option value="">Carregando...</option>';
        try {
            const [usuariosRes, cidadesRes] = await Promise.all([ fetch('API/get_usuario.php'), fetch('get_cidades.php') ]);
            const usuariosData = await usuariosRes.json();
            const cidadesData = await cidadesRes.json();
            if (selectUsuarios && usuariosData.success) {
                selectUsuarios.innerHTML = '<option value="">Selecione o Usuário</option>';
                usuariosData.users.forEach(user => {
                    selectUsuarios.innerHTML += `<option value="${user.id_usuario}">${user.nome}</option>`;
                });
            }
            if (selectCidades && cidadesData.success) {
                selectCidades.innerHTML = '<option value="">Selecione a Cidade</option>';
                cidadesData.cidades.forEach(cidade => {
                    selectCidades.innerHTML += `<option value="${cidade.id_cidade}">${cidade.nome}</option>`;
                });
            }
        } catch (error) {
            console.error('Erro ao carregar dropdowns:', error);
        }
    }

    window.abrirModalAdicionarSolicitacao = function() {
        const formulario = document.getElementById('formularioAdicionarSolicitacao');
        const botao = formulario.querySelector('.botao-salvar');
        formulario.reset();
        document.getElementById('mensagemAdicionarSolicitacao').style.display = 'none';
        botao.style.display = 'flex';
        alternarCarregamento(botao, null, false);
        document.getElementById('modalAdicionarSolicitacao').classList.add('esta-ativo');
        carregarDropdowns('Adicionar'); 
    }

    window.abrirModalEdicaoSolicitacao = function(id) {
        const cidadeEncontrada = Object.keys(allData.solicitacoes).find(cidade => allData.solicitacoes[cidade].some(s => s.id_solicitacao == id));
        const solicitacao = cidadeEncontrada ? allData.solicitacoes[cidadeEncontrada].find(s => s.id_solicitacao == id) : null;
        if (solicitacao) {
            const formulario = document.getElementById('formularioEdicaoSolicitacao');
            const botao = formulario.querySelector('.botao-salvar');
            document.getElementById('idSolicitacaoEdicao').value = solicitacao.id_solicitacao;
            document.getElementById('solicitanteEdicao').value = solicitacao.solicitante;
            document.getElementById('tipoSolicitacaoEdicao').value = solicitacao.tipo_solicitacao || '';
            document.getElementById('descSolicitacaoEdicao').value = solicitacao.desc_solicitacao || '';
            document.getElementById('statusSolicitacaoEdicao').value = solicitacao.status_solicitacao;
            document.getElementById('mensagemEdicaoSolicitacao').style.display = 'none';
            botao.style.display = 'flex';
            alternarCarregamento(botao, null, false);
            document.getElementById('modalEdicaoSolicitacao').classList.add('esta-ativo');
            carregarDropdowns('Edicao').then(() => {
                document.getElementById('idUsuarioEdicao').value = solicitacao.id_usuario;
                document.getElementById('idCidadeEdicao').value = solicitacao.id_cidade;
            });
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
            alternarCarregamento(botao, null, false);
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
                    fetchData(campoPesquisa.value, startDateInput.value, endDateInput.value);
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
        const searchTerm = campoPesquisa.value;
        const startDate = startDateInput.value;
        let endDate = endDateInput.value;

        if (startDate && endDate && endDate < startDate) {
            alert('A data final não pode ser anterior à data inicial.');
            endDateInput.value = '';
            return;
        }
        fetchData(searchTerm, startDate, endDate);
    }

    campoPesquisa.addEventListener('input', handleFilterChange);
    startDateInput.addEventListener('change', handleFilterChange);
    endDateInput.addEventListener('change', handleFilterChange);
    
    statusFilters.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') {
            document.querySelectorAll('#statusFilters .filter-btn').forEach(btn => btn.classList.remove('active'));
            e.target.classList.add('active');
            activeStatus = e.target.dataset.status;
            handleFilterChange();
        }
    });

    document.getElementById('formularioAdicionarSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemAdicionarSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        alternarCarregamento(botao, null, true);
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
                    fetchData(campoPesquisa.value, startDateInput.value, endDateInput.value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao adicionar.', 'erro');
                alternarCarregamento(botao, null, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, null, false);
        }
    });

    document.getElementById('formularioEdicaoSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemEdicaoSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        alternarCarregamento(botao, null, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.action = 'editar';
        try {
            const response = await fetch('API/update_solicitacao.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                exibirMensagem(mensagem, result.message, 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalEdicaoSolicitacao();
                    fetchData(campoPesquisa.value, startDateInput.value, endDateInput.value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao atualizar.', 'erro');
                alternarCarregamento(botao, null, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, null, false);
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
        alternarCarregamento(botao, null, true);
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
                    fetchData(campoPesquisa.value, startDateInput.value, endDateInput.value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao concluir.', 'erro');
                alternarCarregamento(botao, null, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, null, false);
        }
    });

    window.onclick = function(event) {
        if (event.target.matches('.modal')) {
            event.target.classList.remove('esta-ativo');
        }
    };

    fetchData(); // Carga inicial
});