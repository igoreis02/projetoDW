let dadosCidades = [];

        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            const botaoTexto = botao.querySelector('span');
            if (mostrar) {
                botao.disabled = true;
                if (spinner) spinner.style.display = 'block';
                if (botaoTexto) botaoTexto.style.display = 'none';
            } else {
                botao.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (botaoTexto) botaoTexto.style.display = 'block';
            }
        }
        
        function fecharModalAdicionarCidade() {
            document.getElementById('modalAdicionarCidade').classList.remove('esta-ativo');
            document.getElementById('mensagemAdicionarCidade').style.display = 'none';
            document.getElementById('formularioAdicionarCidade').reset();
        }

        function fecharModalEdicaoCidade() {
            document.getElementById('modalEdicaoCidade').classList.remove('esta-ativo');
            document.getElementById('mensagemEdicaoCidade').style.display = 'none';
            document.getElementById('formularioEdicaoCidade').reset();
        }

        function abrirModalAdicionarCidade() {
            const formulario = document.getElementById('formularioAdicionarCidade');
            const botao = formulario.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarCidade');
            const mensagem = document.getElementById('mensagemAdicionarCidade');
            
            formulario.reset();
            alternarCarregamento(botao, spinner, false);
            mensagem.style.display = 'none';
            document.getElementById('modalAdicionarCidade').classList.add('esta-ativo');
        }

        function abrirModalEdicaoCidade(id) {
            const cidade = dadosCidades.find(c => c.id_cidade == id);
            
            if (cidade) {
                const formulario = document.getElementById('formularioEdicaoCidade');
                const botao = formulario.querySelector('.botao-salvar');
                const spinner = document.getElementById('carregandoEdicaoCidade');
                const mensagem = document.getElementById('mensagemEdicaoCidade');

                alternarCarregamento(botao, spinner, false);
                mensagem.style.display = 'none';

                document.getElementById('idCidadeEdicao').value = cidade.id_cidade;
                document.getElementById('nomeCidadeEdicao').value = cidade.nome;
                document.getElementById('siglaCidadeEdicao').value = cidade.sigla_cidade || '';
                document.getElementById('codCidadeEdicao').value = cidade.cod_cidade || '';

                document.getElementById('modalEdicaoCidade').classList.add('esta-ativo');
            } else {
                alert('Cidade não encontrada.');
            }
        }
        
        // Função para renderizar a lista de cidades
        function exibirListaCidades(cidades) {
            const containerListaCidades = document.getElementById('containerListaCidades');
            if (cidades.length === 0) {
                containerListaCidades.innerHTML = '<div class="mensagem">Nenhuma cidade encontrada.</div>';
                return;
            }

            const htmlListaCidades = cidades.map(cidade => `
                <div class="item-cidade">
                    <h3>${cidade.nome} (${cidade.sigla_cidade || 'N/A'})</h3>
                    <p><strong>Código:</strong> ${cidade.cod_cidade || 'N/A'}</p>
                    <div class="btn-group">
                        <button class="botao-editar" onclick="abrirModalEdicaoCidade(${cidade.id_cidade})">Editar</button>
                        <button class="botao-excluir" onclick="excluirCidade(${cidade.id_cidade})">Excluir</button>
                    </div>
                </div>
            `).join('');

            containerListaCidades.innerHTML = `<div class="lista-cidades">${htmlListaCidades}</div>`;
        }

        // Função para buscar e exibir cidades
        async function buscarEExibirCidades() {
            const containerListaCidades = document.getElementById('containerListaCidades');
            containerListaCidades.innerHTML = 'Carregando cidades...';

            try {
                const response = await fetch('API/get_cidades.php');
                const data = await response.json();
                
                if (data.success) {
                    dadosCidades = data.cidades;
                    exibirListaCidades(dadosCidades);
                } else {
                    containerListaCidades.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao buscar cidades:', error);
                containerListaCidades.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar as cidades.</div>`;
            }
        }
        
        // Lógica de exclusão
        async function excluirCidade(id) {
            if (confirm('Tem certeza que deseja excluir esta cidade?')) {
                try {
                    const response = await fetch('API/delete_city.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_cidade: id })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Cidade excluída com sucesso!');
                        buscarEExibirCidades();
                    } else {
                        alert(result.message || 'Erro ao excluir cidade.');
                    }
                } catch (error) {
                    console.error('Erro ao excluir cidade:', error);
                    alert('Ocorreu um erro ao excluir a cidade. Tente novamente.');
                }
            }
        }

        // Event listener para o formulário de adição
        document.getElementById('formularioAdicionarCidade').addEventListener('submit', async function(e) {
            e.preventDefault();
            const mensagem = document.getElementById('mensagemAdicionarCidade');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarCidade');

            alternarCarregamento(botao, spinner, true);

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('API/add_city.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    exibirMensagem(mensagem, 'Cidade adicionada com sucesso!', 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        fecharModalAdicionarCidade();
                        buscarEExibirCidades();
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, result.message || 'Erro ao adicionar cidade.', 'erro');
                    alternarCarregamento(botao, spinner, false);
                }
            } catch (error) {
                console.error('Erro ao adicionar cidade:', error);
                exibirMensagem(mensagem, 'Ocorreu um erro ao adicionar a cidade. Tente novamente.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        });

        // Event listener para o formulário de edição
        document.getElementById('formularioEdicaoCidade').addEventListener('submit', async function(e) {
            e.preventDefault();
            const mensagem = document.getElementById('mensagemEdicaoCidade');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoCidade');

            alternarCarregamento(botao, spinner, true);
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.id_cidade = document.getElementById('idCidadeEdicao').value;

            try {
                const response = await fetch('API/update_city.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    exibirMensagem(mensagem, 'Cidade atualizada com sucesso!', 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        fecharModalEdicaoCidade();
                        buscarEExibirCidades();
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, result.message || 'Erro ao atualizar cidade.', 'erro');
                    alternarCarregamento(botao, spinner, false);
                }
            } catch (error) {
                console.error('Erro ao atualizar cidade:', error);
                exibirMensagem(mensagem, 'Ocorreu um erro ao atualizar a cidade. Tente novamente.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        });

        // Event listener global para fechar modais ao clicar fora
        window.onclick = function(event) {
            const addModal = document.getElementById('modalAdicionarCidade');
            const editModal = document.getElementById('modalEdicaoCidade');
            if (event.target == addModal) {
                fecharModalAdicionarCidade();
            }
            if (event.target == editModal) {
                fecharModalEdicaoCidade();
            }
        };

        // Carrega a lista de cidades quando a página é carregada
        document.addEventListener('DOMContentLoaded', buscarEExibirCidades);
