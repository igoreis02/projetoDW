    let dadosVeiculos = [];

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
        
        function fecharModalAdicionarVeiculo() {
            document.getElementById('modalAdicionarVeiculo').classList.remove('esta-ativo');
        }

        function fecharModalEdicaoVeiculo() {
            document.getElementById('modalEdicaoVeiculo').classList.remove('esta-ativo');
        }

        function abrirModalAdicionarVeiculo() {
            document.getElementById('formularioAdicionarVeiculo').reset();
            document.getElementById('mensagemAdicionarVeiculo').style.display = 'none';
            document.getElementById('modalAdicionarVeiculo').classList.add('esta-ativo');
        }

        function abrirModalEdicaoVeiculo(id) {
            const veiculo = dadosVeiculos.find(v => v.id_veiculo == id);
            
            if (veiculo) {
                document.getElementById('idVeiculoEdicao').value = veiculo.id_veiculo;
                document.getElementById('nomeVeiculoEdicao').value = veiculo.nome;
                document.getElementById('placaVeiculoEdicao').value = veiculo.placa;
                document.getElementById('modeloVeiculoEdicao').value = veiculo.modelo;
                document.getElementById('mensagemEdicaoVeiculo').style.display = 'none';
                document.getElementById('modalEdicaoVeiculo').classList.add('esta-ativo');
            } else {
                alert('Veículo não encontrado.');
            }
        }
        
        function exibirListaVeiculos(veiculos) {
            const container = document.getElementById('containerListaVeiculos');
            if (veiculos.length === 0) {
                container.innerHTML = '<div class="mensagem">Nenhum veículo encontrado.</div>';
                return;
            }

            const html = veiculos.map(veiculo => `
                <div class="item-veiculo">
                    <h3>${veiculo.nome}</h3>
                    <p><strong>Placa:</strong> ${veiculo.placa}</p>
                    <p><strong>Modelo:</strong> ${veiculo.modelo}</p>
                    <div class="btn-group">
                        <button class="botao-editar" onclick="abrirModalEdicaoVeiculo(${veiculo.id_veiculo})">Editar</button>
                        <button class="botao-excluir" onclick="excluirVeiculo(${veiculo.id_veiculo})">Excluir</button>
                    </div>
                </div>
            `).join('');

            container.innerHTML = `<div class="lista-veiculos">${html}</div>`;
        }

        async function buscarEExibirVeiculos() {
            const container = document.getElementById('containerListaVeiculos');
            container.innerHTML = 'Carregando veículos...';

            try {
                const response = await fetch('get_veiculos.php');
                const data = await response.json();
                
                // Assumindo que get_veiculos.php retorna um array diretamente
                dadosVeiculos = data;
                exibirListaVeiculos(dadosVeiculos);
                
            } catch (error) {
                console.error('Erro ao buscar veículos:', error);
                container.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os veículos.</div>`;
            }
        }
        
        async function excluirVeiculo(id) {
            if (confirm('Tem certeza que deseja excluir este veículo?')) {
                try {
                    const response = await fetch('API/delete_veiculo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_veiculo: id })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Veículo excluído com sucesso!');
                        buscarEExibirVeiculos();
                    } else {
                        alert(result.message || 'Erro ao excluir veículo.');
                    }
                } catch (error) {
                    console.error('Erro ao excluir veículo:', error);
                    alert('Ocorreu um erro ao excluir o veículo. Tente novamente.');
                }
            }
        }

        document.getElementById('formularioAdicionarVeiculo').addEventListener('submit', async function(e) {
            e.preventDefault();
            const mensagem = document.getElementById('mensagemAdicionarVeiculo');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarVeiculo');
            alternarCarregamento(botao, spinner, true);

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('add_veiculo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    exibirMensagem(mensagem, result.message, 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        botao.style.display = 'flex';
                        fecharModalAdicionarVeiculo();
                        buscarEExibirVeiculos();
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, result.message, 'erro');
                }
            } catch (error) {
                exibirMensagem(mensagem, 'Ocorreu um erro de conexão.', 'erro');
            } finally {
                alternarCarregamento(botao, spinner, false);
            }
        });

        document.getElementById('formularioEdicaoVeiculo').addEventListener('submit', async function(e) {
            e.preventDefault();
            const mensagem = document.getElementById('mensagemEdicaoVeiculo');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoVeiculo');
            alternarCarregamento(botao, spinner, true);
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('API/update_veiculo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    exibirMensagem(mensagem, result.message, 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        botao.style.display = 'flex';
                        fecharModalEdicaoVeiculo();
                        buscarEExibirVeiculos();
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, result.message, 'erro');
                }
            } catch (error) {
                exibirMensagem(mensagem, 'Ocorreu um erro de conexão.', 'erro');
            } finally {
                alternarCarregamento(botao, spinner, false);
            }
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalAdicionarVeiculo')) {
                fecharModalAdicionarVeiculo();
            }
            if (event.target == document.getElementById('modalEdicaoVeiculo')) {
                fecharModalEdicaoVeiculo();
            }
        };

        document.addEventListener('DOMContentLoaded', buscarEExibirVeiculos);