<?php
session_start(); // Inicia ou resume a sessão
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    // Redireciona para a página de login
    header("Location: index.html");
    exit;
}

// Verifica se o usuário tem permissão de 'administrador'
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] !== 'administrador') {
    header('Location: menu.php'); // Redireciona para o menu se não tiver permissão
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Gerenciar Equipamentos</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 0;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1000px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .card h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        /* Layout para o cabeçalho e botões */
        .cabecalho {
            width: 90%;
            max-width: 1000px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conteudo-cabecalho {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .titulo-cabecalho {
            flex-grow: 1;
            text-align: center;
            margin: 0;
        }

        .botao-voltar {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 50%;
            color: white;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 2rem;
            left: 5%;
        }

        .botao-voltar:hover {
            background-color: var(--cor-secundaria);
        }

        .container-botao-adicionar-equipamento {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-equipamento {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .botao-adicionar-equipamento:hover {
            background-color: var(--cor-secundaria);
        }
        
        .container-pesquisa {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 10px;
        }
        
        .container-pesquisa input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 400px;
        }

        .lista-equipamentos {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-equipamento {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-equipamento h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-equipamento p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-equipamento .botao-editar {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .item-equipamento .botao-editar:hover {
            background-color: #45a049;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal.esta-ativo {
            display: flex;
        }

        .conteudo-modal {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
        }

        .fechar-modal {
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .formulario-modal label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
            color: #333;
        }

        .formulario-modal input,
        .formulario-modal select,
        .formulario-modal textarea {
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        
        .formulario-modal button {
            width: 100%;
            padding: 1rem;
            margin-top: 1.5rem;
            border-radius: 0.5rem;
            border: none;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .formulario-modal .botao-salvar {
            background-color: #4CAF50;
        }

        .formulario-modal .botao-salvar:hover {
            background-color: #45a049;
        }
        
        .mensagem {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        
        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        
        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
        }

        .carregando {
            border: 4px solid rgba(0, 0, 0, .1);
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border-left-color: #fff;
            animation: spin 1.2s linear infinite;
            display: none;
            margin: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Estilo para os botões de cidades */
        .container-cidades {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .botao-cidade {
            background-color: #e9ecef;
            color: #495057;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .botao-cidade:hover {
            background-color: #dee2e6;
        }
        
        .botao-cidade.selecionado {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
        }
    </style>
</head>

<body>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="titulo-cabecalho">Gerenciar Equipamentos</h1>
        </header>

        <div class="container-botao-adicionar-equipamento">
            <button class="botao-adicionar-equipamento" onclick="abrirModalAdicionarEquipamento()">Adicionar Equipamento</button>
        </div>

        <div class="container-cidades" id="containerCidades">
            <!-- Os botões de cidades serão carregados aqui -->
        </div>

        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou referência...">
        </div>
        <div id="containerListaEquipamentos">
            <!-- A lista de equipamentos será renderizada aqui pelo JavaScript -->
        </div>
    </main>

    <!-- Modal para Editar Equipamento -->
    <div id="modalEdicaoEquipamento" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoEquipamento()">&times;</span>
            <h2>Editar Equipamento</h2>
            <form id="formularioEdicaoEquipamento" class="formulario-modal">
                <input type="hidden" id="idEquipamentoEdicao">
                <label for="nomeEdicao">Nome:</label>
                <input type="text" id="nomeEdicao" name="nome" required>
                <label for="tipoEdicao">Tipo:</label>
                <input type="text" id="tipoEdicao" name="tipo" required>
                <label for="referenciaEdicao">Referência:</label>
                <input type="text" id="referenciaEdicao" name="referencia" required>
                <label for="logradouroEdicao">Logradouro:</label>
                <input type="text" id="logradouroEdicao" name="logradouro">
                <label for="bairroEdicao">Bairro:</label>
                <input type="text" id="bairroEdicao" name="bairro">
                <label for="statusEquipamentoEdicao">Status:</label>
                <select id="statusEquipamentoEdicao" name="status_equipamento" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>
                <div id="mensagemEdicaoEquipamento" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar</span>
                    <span id="carregandoEdicaoEquipamento" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para Adicionar Equipamento -->
    <div id="modalAdicionarEquipamento" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarEquipamento()">&times;</span>
            <h2>Adicionar Novo Equipamento</h2>
            <form id="formularioAdicionarEquipamento" class="formulario-modal">
                <label for="nomeAdicionar">Nome:</label>
                <input type="text" id="nomeAdicionar" name="nome" required>
                <label for="tipoAdicionar">Tipo:</label>
                <input type="text" id="tipoAdicionar" name="tipo" required>
                <label for="referenciaAdicionar">Referência:</label>
                <input type="text" id="referenciaAdicionar" name="referencia" required>
                <label for="logradouroAdicionar">Logradouro:</label>
                <input type="text" id="logradouroAdicionar" name="logradouro">
                <label for="bairroAdicionar">Bairro:</label>
                <input type="text" id="bairroAdicionar" name="bairro">
                <div id="mensagemAdicionarEquipamento" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoAdicionar">Adicionar</span>
                    <span id="carregandoAdicionarEquipamento" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Variável global para armazenar a lista de equipamentos
        let dadosEquipamentos = [];
        let cidadeSelecionada = '';

        // Funções de utilidade para mostrar mensagens e spinners
        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            const textoBotao = botao.querySelector('span');
            if (mostrar) {
                botao.disabled = true;
                if (textoBotao) textoBotao.style.display = 'none';
                spinner.style.display = 'block';
            } else {
                botao.disabled = false;
                if (textoBotao) textoBotao.style.display = 'block';
                spinner.style.display = 'none';
            }
        }

        // Funções para fechar e abrir modais
        function fecharModalEdicaoEquipamento() {
            document.getElementById('modalEdicaoEquipamento').classList.remove('esta-ativo');
            document.getElementById('mensagemEdicaoEquipamento').style.display = 'none';
        }

        function fecharModalAdicionarEquipamento() {
            document.getElementById('modalAdicionarEquipamento').classList.remove('esta-ativo');
            document.getElementById('mensagemAdicionarEquipamento').style.display = 'none';
            document.getElementById('formularioAdicionarEquipamento').reset();
        }

        function abrirModalAdicionarEquipamento() {
            const formularioAdicionar = document.getElementById('formularioAdicionarEquipamento');
            const botaoEnviar = formularioAdicionar.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarEquipamento');
            
            formularioAdicionar.reset();
            alternarCarregamento(botaoEnviar, spinner, false);
            document.getElementById('modalAdicionarEquipamento').classList.add('esta-ativo');
        }

        /**
         * Abre o modal de edição e preenche com os dados do equipamento.
         * @param {number} idEquipamento - O ID do equipamento a ser editado.
         */
        function abrirModalEdicaoEquipamento(idEquipamento) {
            const equipamento = dadosEquipamentos.find(e => e.id_equipamento == idEquipamento);
            
            if (equipamento) {
                const formularioEdicao = document.getElementById('formularioEdicaoEquipamento');
                const botaoEnviar = formularioEdicao.querySelector('.botao-salvar');
                const spinner = document.getElementById('carregandoEdicaoEquipamento');
                
                alternarCarregamento(botaoEnviar, spinner, false);
                document.getElementById('mensagemEdicaoEquipamento').style.display = 'none';

                document.getElementById('idEquipamentoEdicao').value = equipamento.id_equipamento;
                document.getElementById('nomeEdicao').value = equipamento.nome;
                document.getElementById('tipoEdicao').value = equipamento.tipo;
                document.getElementById('referenciaEdicao').value = equipamento.referencia;
                document.getElementById('logradouroEdicao').value = equipamento.logradouro;
                document.getElementById('bairroEdicao').value = equipamento.bairro;
                document.getElementById('statusEquipamentoEdicao').value = equipamento.status_equipamento;

                document.getElementById('modalEdicaoEquipamento').classList.add('esta-ativo');
            } else {
                exibirMensagem(document.getElementById('mensagemEdicaoEquipamento'), 'Equipamento não encontrado. Recarregue a página.', 'erro');
            }
        }
        
        /**
         * Carrega os botões de cidades do servidor.
         */
        async function carregarBotoesCidades() {
            const containerCidades = document.getElementById('containerCidades');
            containerCidades.innerHTML = 'Carregando cidades...';
            
            try {
                const response = await fetch('get_cidades.php');
                const data = await response.json();
                
                if (data.success) {
                    const htmlBotoes = data.cidades.map(cidade => 
                        `<button class="botao-cidade" onclick="selecionarCidade('${cidade.nome}')">${cidade.nome}</button>`
                    ).join('');
                    
                    containerCidades.innerHTML = htmlBotoes;
                } else {
                    containerCidades.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao carregar cidades:', error);
                containerCidades.innerHTML = `<div class="mensagem erro">Erro ao carregar cidades.</div>`;
            }
        }

        /**
         * Seleciona uma cidade e atualiza a lista de equipamentos.
         * @param {string} nomeCidade - O nome da cidade a ser filtrada.
         */
        function selecionarCidade(nomeCidade) {
            cidadeSelecionada = nomeCidade;
            const botoes = document.querySelectorAll('.botao-cidade');
            botoes.forEach(btn => {
                btn.classList.remove('selecionado');
                if (btn.textContent === nomeCidade) {
                    btn.classList.add('selecionado');
                }
            });
            buscarEExibirEquipamentos();
        }

        // Função principal para buscar e renderizar a lista de equipamentos
        async function buscarEExibirEquipamentos(termoPesquisa = '') {
            const containerListaEquipamentos = document.getElementById('containerListaEquipamentos');
            containerListaEquipamentos.innerHTML = 'Carregando equipamentos...';

            try {
                // NOTE: O script 'get_equipamentos.php' deve ser modificado para aceitar o parâmetro 'cidade'
                const url = new URL('get_equipamentos.php', window.location.href);
                if (termoPesquisa) {
                    url.searchParams.set('search', termoPesquisa);
                }
                if (cidadeSelecionada) {
                    url.searchParams.set('cidade', cidadeSelecionada);
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    dadosEquipamentos = data.equipamentos;
                    exibirListaEquipamentos(dadosEquipamentos);
                } else {
                    containerListaEquipamentos.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao buscar equipamentos:', error);
                containerListaEquipamentos.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os equipamentos.</div>`;
            }
        }

        // Função para renderizar a lista de equipamentos na tela
        function exibirListaEquipamentos(equipamentos) {
            const containerListaEquipamentos = document.getElementById('containerListaEquipamentos');
            if (equipamentos.length === 0) {
                containerListaEquipamentos.innerHTML = '<div class="mensagem">Nenhum equipamento encontrado.</div>';
                return;
            }

            const htmlListaEquipamentos = equipamentos.map(equipamento => `
                <div class="item-equipamento">
                    <h3>${equipamento.nome}</h3>
                    <p><strong>Tipo:</strong> ${equipamento.tipo}</p>
                    <p><strong>Referência:</strong> ${equipamento.referencia}</p>
                    <p><strong>Logradouro:</strong> ${equipamento.logradouro || 'N/A'}</p>
                    <p><strong>Bairro:</strong> ${equipamento.bairro || 'N/A'}</p>
                    <p><strong>Status:</strong> ${equipamento.status_equipamento}</p>
                    <button class="botao-editar" onclick="abrirModalEdicaoEquipamento(${equipamento.id_equipamento})">Editar</button>
                </div>
            `).join('');

            containerListaEquipamentos.innerHTML = `<div class="lista-equipamentos">${htmlListaEquipamentos}</div>`;
        }

        // Event listener para o campo de pesquisa
        document.getElementById('campoPesquisa').addEventListener('input', (evento) => {
            buscarEExibirEquipamentos(evento.target.value);
        });

        // Event listener para o formulário de edição de equipamento
        document.getElementById('formularioEdicaoEquipamento').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagem = document.getElementById('mensagemEdicaoEquipamento');
            const botaoEnviar = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoEquipamento');
            
            exibirMensagem(mensagem, 'Salvando...', 'sucesso');
            alternarCarregamento(botaoEnviar, spinner, true);

            // Simulação de chamada de API
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            exibirMensagem(mensagem, 'Equipamento salvo com sucesso!', 'sucesso');
            alternarCarregamento(botaoEnviar, spinner, false);

            setTimeout(() => {
                fecharModalEdicaoEquipamento();
                buscarEExibirEquipamentos(); // Recarrega a lista
            }, 1500);
        });

        // Event listener para o formulário de adição de equipamento
        document.getElementById('formularioAdicionarEquipamento').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagem = document.getElementById('mensagemAdicionarEquipamento');
            const botaoEnviar = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarEquipamento');

            alternarCarregamento(botaoEnviar, spinner, true);
            
            // Pega os dados do formulário e adiciona o status 'ativo' fixo
            const formData = Object.fromEntries(new FormData(this).entries());
            formData.status_equipamento = 'ativo';

            try {
                // NOTE: Crie um arquivo 'add_equipamento.php' que receba os dados via POST e insira no banco.
                const response = await fetch('add_equipamento.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const data = await response.json();

                if (data.success) {
                    exibirMensagem(mensagem, 'Equipamento adicionado com sucesso!', 'sucesso');
                    alternarCarregamento(botaoEnviar, spinner, false);
                    setTimeout(() => {
                        fecharModalAdicionarEquipamento();
                        buscarEExibirEquipamentos(); // Recarrega a lista
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, data.message || 'Erro ao adicionar equipamento.', 'erro');
                    alternarCarregamento(botaoEnviar, spinner, false);
                }
            } catch (error) {
                console.error('Erro ao adicionar equipamento:', error);
                exibirMensagem(mensagem, 'Ocorreu um erro ao adicionar o equipamento. Tente novamente.', 'erro');
                alternarCarregamento(botaoEnviar, spinner, false);
            }
        });

        // Carrega a lista de equipamentos e os botões de cidades quando a página é carregada
        document.addEventListener('DOMContentLoaded', () => {
            carregarBotoesCidades();
            buscarEExibirEquipamentos();
        });

        // Fecha os modais se o usuário clicar fora
        window.onclick = function(evento) {
            if (evento.target == document.getElementById('modalEdicaoEquipamento')) {
                fecharModalEdicaoEquipamento();
            }
            if (evento.target == document.getElementById('modalAdicionarEquipamento')) {
                fecharModalAdicionarEquipamento();
            }
        }
    </script>
</body>

</html>
