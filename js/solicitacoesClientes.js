document.addEventListener('DOMContentLoaded', function () {
    const filterContainer = document.getElementById('filterContainer');
    const solicitacoesContainer = document.getElementById('solicitacoesContainer');
    const loadingMessage = document.getElementById('loadingMessage');
    const campoPesquisa = document.getElementById('campoPesquisa');

    let allData = null;
    let activeCity = 'todos';
    let todosUsuarios = [];
    let todasCidades = [];

    // --- FUNÇÕES DE LÓGICA PRINCIPAL ---
    async function fetchData(searchTerm = '') {
        loadingMessage.style.display = 'block';
        solicitacoesContainer.innerHTML = ''; 
        try {
            const response = await fetch(`API/get_solicitacoes.php?search=${encodeURIComponent(searchTerm)}`);
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
        const statusClass = `status-${item.status_solicitacao.replace(' ', '-')}`;
        const statusText = item.status_solicitacao.charAt(0).toUpperCase() + item.status_solicitacao.slice(1);

        const desdobramentoHTML = item.desdobramento_soli ? `
            <div class="detail-item"><strong>Desdobramento:</strong> <span>${item.desdobramento_soli}</span></div>` : '';

        return `
            <div class="item-solicitacao">
                <div class="solicitacao-header"><h3>${item.solicitante}</h3></div>
                <div class="solicitacao-details">
                    <div class="detail-item"><strong>Cidade:</strong> <span>${item.nome_cidade || 'N/A'}</span></div>
                    <div class="detail-item"><strong>Solicitação:</strong> <span>${item.desc_solicitacao || 'N/A'}</span></div>
                    ${desdobramentoHTML}
                    <div class="detail-item"><strong>Status:</strong> <span class="status-tag ${statusClass}">${statusText}</span></div>
                    <div class="detail-item"><strong>Adicionado por:</strong> <span>${item.nome_usuario || 'N/A'}</span></div>
                </div>
                <div class="item-actions">
                    <button class="item-btn edit-btn" onclick="abrirModalEdicaoSolicitacao(${item.id_solicitacao})">Editar</button>
                    <button class="item-btn cancel-btn" onclick="excluirSolicitacao(${item.id_solicitacao})">Excluir</button>
                </div>
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

        document.querySelectorAll('#filterContainer .filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('#filterContainer .filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                activeCity = button.dataset.city;
                updateDisplay();
            });
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
        const botaoTexto = botao.querySelector('span');
        if (mostrar) {
            botao.disabled = true;
            spinner.style.display = 'block';
            if (botaoTexto) botaoTexto.style.display = 'none';
        } else {
            botao.disabled = false;
            spinner.style.display = 'none';
            if (botaoTexto) botaoTexto.style.display = 'flex';
        }
    }

    window.fecharModalAdicionarSolicitacao = function() {
        document.getElementById('modalAdicionarSolicitacao').classList.remove('esta-ativo');
    }

    window.fecharModalEdicaoSolicitacao = function() {
        document.getElementById('modalEdicaoSolicitacao').classList.remove('esta-ativo');
    }
    
    async function carregarDropdowns(tipoModal) {
        const selectUsuarios = document.getElementById(`idUsuario${tipoModal}`);
        const selectCidades = document.getElementById(`idCidade${tipoModal}`);

        if (selectUsuarios) selectUsuarios.innerHTML = '<option value="">Carregando...</option>';
        if (selectCidades) selectCidades.innerHTML = '<option value="">Carregando...</option>';

        try {
            const [usuariosRes, cidadesRes] = await Promise.all([
                fetch('API/get_usuario.php'),
                fetch('get_cidades.php')
            ]);
            
            const usuariosData = await usuariosRes.json();
            const cidadesData = await cidadesRes.json();

            if (selectUsuarios && usuariosData.success) {
                todosUsuarios = usuariosData.users;
                selectUsuarios.innerHTML = '<option value="">Selecione o Usuário</option>';
                todosUsuarios.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id_usuario;
                    option.textContent = user.nome;
                    selectUsuarios.appendChild(option);
                });
            }

            if (selectCidades && cidadesData.success) {
                todasCidades = cidadesData.cidades;
                selectCidades.innerHTML = '<option value="">Selecione a Cidade</option>';
                todasCidades.forEach(cidade => {
                    const option = document.createElement('option');
                    option.value = cidade.id_cidade;
                    option.textContent = cidade.nome;
                    selectCidades.appendChild(option);
                });
            }

        } catch (error) {
            console.error('Erro ao carregar dropdowns:', error);
            if (selectUsuarios) selectUsuarios.innerHTML = '<option value="">Erro ao carregar usuários</option>';
            if (selectCidades) selectCidades.innerHTML = '<option value="">Erro ao carregar cidades</option>';
        }
    }

    window.abrirModalAdicionarSolicitacao = function() {
        const formulario = document.getElementById('formularioAdicionarSolicitacao');
        const botao = formulario.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoAdicionarSolicitacao');
        
        formulario.reset();
        document.getElementById('mensagemAdicionarSolicitacao').style.display = 'none';
        botao.style.display = 'flex'; // Garante que o botão esteja visível
        alternarCarregamento(botao, spinner, false); // Reseta o estado do botão
        
        document.getElementById('modalAdicionarSolicitacao').classList.add('esta-ativo');
        carregarDropdowns('Adicionar'); 
    }

    window.abrirModalEdicaoSolicitacao = function(id) {
        const cidadeEncontrada = Object.keys(allData.solicitacoes).find(cidade => 
            allData.solicitacoes[cidade].some(s => s.id_solicitacao == id)
        );
        const solicitacao = cidadeEncontrada ? allData.solicitacoes[cidadeEncontrada].find(s => s.id_solicitacao == id) : null;
        
        if (solicitacao) {
            const formulario = document.getElementById('formularioEdicaoSolicitacao');
            const botao = formulario.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoSolicitacao');

            document.getElementById('idSolicitacaoEdicao').value = solicitacao.id_solicitacao;
            document.getElementById('solicitanteEdicao').value = solicitacao.solicitante;
            document.getElementById('descSolicitacaoEdicao').value = solicitacao.desc_solicitacao || '';
            document.getElementById('desdobramentoSoliEdicao').value = solicitacao.desdobramento_soli || '';
            document.getElementById('statusSolicitacaoEdicao').value = solicitacao.status_solicitacao;
            document.getElementById('mensagemEdicaoSolicitacao').style.display = 'none';
            botao.style.display = 'flex'; // Garante que o botão esteja visível
            alternarCarregamento(botao, spinner, false); // Reseta o estado do botão
            
            document.getElementById('modalEdicaoSolicitacao').classList.add('esta-ativo');
            
            carregarDropdowns('Edicao').then(() => {
                document.getElementById('idUsuarioEdicao').value = solicitacao.id_usuario;
                document.getElementById('idCidadeEdicao').value = solicitacao.id_cidade;
            });
        } else {
            alert('Solicitação não encontrada.');
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
                    fetchData(campoPesquisa.value);
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
    campoPesquisa.addEventListener('input', (e) => {
        fetchData(e.target.value);
    });

    document.getElementById('formularioAdicionarSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemAdicionarSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoAdicionarSolicitacao');
        alternarCarregamento(botao, spinner, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('API/add_solicitacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                exibirMensagem(mensagem, result.message, 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalAdicionarSolicitacao();
                    fetchData(campoPesquisa.value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao adicionar solicitação.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    });

    document.getElementById('formularioEdicaoSolicitacao').addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensagem = document.getElementById('mensagemEdicaoSolicitacao');
        const botao = this.querySelector('.botao-salvar');
        const spinner = document.getElementById('carregandoEdicaoSolicitacao');
        alternarCarregamento(botao, spinner, true);
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.id_solicitacao = document.getElementById('idSolicitacaoEdicao').value;

        try {
            const response = await fetch('API/update_solicitacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                exibirMensagem(mensagem, 'Solicitação atualizada com sucesso!', 'sucesso');
                botao.style.display = 'none';
                setTimeout(() => {
                    fecharModalEdicaoSolicitacao();
                    fetchData(campoPesquisa.value);
                }, 1500);
            } else {
                exibirMensagem(mensagem, result.message || 'Erro ao atualizar solicitação.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        } catch (error) {
            exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
            alternarCarregamento(botao, spinner, false);
        }
    });

    window.onclick = function(event) {
        const addModal = document.getElementById('modalAdicionarSolicitacao');
        const editModal = document.getElementById('modalEdicaoSolicitacao');
        if (event.target == addModal) {
            fecharModalAdicionarSolicitacao();
        }
        if (event.target == editModal) {
            fecharModalEdicaoSolicitacao();
        }
    };

    fetchData(); // Carga inicial
});