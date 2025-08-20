<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// O bloco PHP que buscava as cidades foi removido daqui.
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Gerenciar Provedores</title>
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

        .container-botao-adicionar-provedor {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-provedor {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .botao-adicionar-provedor:hover {
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

        .lista-provedores {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-provedor {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-provedor h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-provedor p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-provedor .botao-editar {
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

        .item-provedor .botao-editar:hover {
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
        .formulario-modal select {
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        
        .formulario-modal select {
            color: #333;
        }
        
        .formulario-modal select option {
            color: #333;
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
            <h1 class="titulo-cabecalho">Gerenciar Provedores</h1>
        </header>
        <div class="container-botao-adicionar-provedor">
            <button class="botao-adicionar-provedor" onclick="abrirModalAdicionarProvedor()">Adicionar Provedor</button>
        </div>

        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou cidade...">
        </div>
        <div id="containerListaProvedores">
            </div>
    </main>

    <div id="modalEdicaoProvedor" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoProvedor()">&times;</span>
            <h2>Editar Provedor</h2>
            <form id="formularioEdicaoProvedor" class="formulario-modal">
                <input type="hidden" id="idProvedorEdicao" name="id_provedor">
                <label for="nomeProvedorEdicao">Nome:</label>
                <input type="text" id="nomeProvedorEdicao" name="nome_prov" required>
                
                <label for="cidadeProvedorEdicao">Cidade:</label>
                <select id="cidadeProvedorEdicao" name="id_cidade" required>
                    <option value="">Carregando...</option>
                </select>

                <div id="mensagemEdicaoProvedor" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar</span>
                    <span id="carregandoEdicaoProvedor" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <div id="modalAdicionarProvedor" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarProvedor()">&times;</span>
            <h2>Adicionar Novo Provedor</h2>
            <form id="formularioAdicionarProvedor" class="formulario-modal">
                <label for="nomeProvedorAdicionar">Nome:</label>
                <input type="text" id="nomeProvedorAdicionar" name="nome_prov" required>

                <label for="cidadeProvedorAdicionar">Cidade:</label>
                <select id="cidadeProvedorAdicionar" name="id_cidade" required>
                    <option value="">Carregando...</option>
                </select>

                <div id="mensagemAdicionarProvedor" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoAdicionar">Adicionar</span>
                    <span id="carregandoAdicionarProvedor" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <script>
        let dadosProvedores = [];

        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            const botaoTexto = botao.querySelector('span:not(.carregando)');
            if (mostrar) {
                botao.disabled = true;
                spinner.style.display = 'block';
                if (botaoTexto) botaoTexto.style.display = 'none';
            } else {
                botao.disabled = false;
                spinner.style.display = 'none';
                if (botaoTexto) botaoTexto.style.display = 'block';
            }
        }
        
        function fecharModalEdicaoProvedor() {
            document.getElementById('modalEdicaoProvedor').classList.remove('esta-ativo');
        }

        function fecharModalAdicionarProvedor() {
            document.getElementById('modalAdicionarProvedor').classList.remove('esta-ativo');
        }

        // **NOVO: Função para carregar cidades nos selects dos modais**
        async function carregarCidadesNosModais() {
            const selects = [
                document.getElementById('cidadeProvedorAdicionar'),
                document.getElementById('cidadeProvedorEdicao')
            ];

            try {
                const response = await fetch('get_cidades.php');
                const data = await response.json();
                
                selects.forEach(select => {
                    select.innerHTML = '<option value="">Selecione a Cidade</option>'; // Limpa e adiciona a opção padrão
                    if (data.success) {
                        data.cidades.forEach(cidade => {
                            const option = document.createElement('option');
                            option.value = cidade.id_cidade;
                            option.textContent = cidade.nome;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    }
                });
            } catch (error) {
                console.error('Erro ao buscar cidades:', error);
                selects.forEach(select => {
                    select.innerHTML = '<option value="">Erro de conexão</option>';
                });
            }
        }

        function abrirModalAdicionarProvedor() {
            const formulario = document.getElementById('formularioAdicionarProvedor');
            formulario.reset();
            document.getElementById('mensagemAdicionarProvedor').style.display = 'none';
            const botao = formulario.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarProvedor');
            alternarCarregamento(botao, spinner, false);
            botao.style.display = 'flex';
            document.getElementById('modalAdicionarProvedor').classList.add('esta-ativo');
        }

        function abrirModalEdicaoProvedor(idProvedor) {
            const provedor = dadosProvedores.find(p => p.id_provedor == idProvedor);
            
            if (provedor) {
                document.getElementById('idProvedorEdicao').value = provedor.id_provedor;
                document.getElementById('nomeProvedorEdicao').value = provedor.nome_prov;
                document.getElementById('cidadeProvedorEdicao').value = provedor.id_cidade;

                const formulario = document.getElementById('formularioEdicaoProvedor');
                const botao = formulario.querySelector('.botao-salvar');
                const spinner = document.getElementById('carregandoEdicaoProvedor');
                alternarCarregamento(botao, spinner, false);
                botao.style.display = 'flex';
                document.getElementById('mensagemEdicaoProvedor').style.display = 'none';

                document.getElementById('modalEdicaoProvedor').classList.add('esta-ativo');
            } else {
                alert('Provedor não encontrado.');
            }
        }

        async function buscarEExibirProvedores(termoPesquisa = '') {
            const containerListaProvedores = document.getElementById('containerListaProvedores');
            containerListaProvedores.innerHTML = 'Carregando provedores...';

            try {
                const url = new URL('get_provedor.php', window.location.href);
                url.searchParams.set('search', termoPesquisa);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    dadosProvedores = data.providers;
                    exibirListaProvedores(dadosProvedores);
                } else {
                    containerListaProvedores.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao buscar provedores:', error);
                containerListaProvedores.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os provedores.</div>`;
            }
        }

        function exibirListaProvedores(provedores) {
            const containerListaProvedores = document.getElementById('containerListaProvedores');
            if (provedores.length === 0) {
                containerListaProvedores.innerHTML = '<div class="mensagem">Nenhum provedor encontrado.</div>';
                return;
            }

            const htmlListaProvedores = provedores.map(provedor => `
                <div class="item-provedor">
                    <h3>${provedor.nome_prov}</h3>
                    <p><strong>Cidade:</strong> ${provedor.cidade_prov || 'N/A'}</p>
                    <button class="botao-editar" onclick="abrirModalEdicaoProvedor(${provedor.id_provedor})">Editar</button>
                </div>
            `).join('');

            containerListaProvedores.innerHTML = `<div class="lista-provedores">${htmlListaProvedores}</div>`;
        }

        document.getElementById('campoPesquisa').addEventListener('input', (evento) => {
            buscarEExibirProvedores(evento.target.value);
        });

        document.getElementById('formularioEdicaoProvedor').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagem = document.getElementById('mensagemEdicaoProvedor');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoProvedor');

            alternarCarregamento(botao, spinner, true);

            const dadosFormulario = new FormData(this);
            const provedor = Object.fromEntries(dadosFormulario.entries());
            provedor.id_provedor = document.getElementById('idProvedorEdicao').value;

            try {
                const response = await fetch('update_provedor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(provedor)
                });
                const data = await response.json();

                if (data.success) {
                    exibirMensagem(mensagem, 'Provedor salvo com sucesso!', 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        fecharModalEdicaoProvedor();
                        buscarEExibirProvedores('');
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, data.message || 'Erro ao atualizar.', 'erro');
                    alternarCarregamento(botao, spinner, false);
                }
            } catch (error) {
                exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        });

        document.getElementById('formularioAdicionarProvedor').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagem = document.getElementById('mensagemAdicionarProvedor');
            const botao = this.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoAdicionarProvedor');

            alternarCarregamento(botao, spinner, true);

            const dadosFormulario = new FormData(this);
            const provedor = Object.fromEntries(dadosFormulario.entries());

            try {
                const response = await fetch('add_provedor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(provedor)
                });
                const data = await response.json();

                if (data.success) {
                    exibirMensagem(mensagem, 'Provedor adicionado com sucesso!', 'sucesso');
                    botao.style.display = 'none';
                    setTimeout(() => {
                        fecharModalAdicionarProvedor();
                        buscarEExibirProvedores('');
                    }, 1500);
                } else {
                    exibirMensagem(mensagem, data.message || 'Erro ao adicionar.', 'erro');
                    alternarCarregamento(botao, spinner, false);
                }
            } catch (error) {
                exibirMensagem(mensagem, 'Ocorreu um erro. Tente novamente.', 'erro');
                alternarCarregamento(botao, spinner, false);
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            buscarEExibirProvedores();
            // **NOVO: Carrega as cidades assim que a página estiver pronta**
            carregarCidadesNosModais();
        });

        window.onclick = function(evento) {
            if (evento.target == document.getElementById('modalEdicaoProvedor')) {
                fecharModalEdicaoProvedor();
            }
            if (evento.target == document.getElementById('modalAdicionarProvedor')) {
                fecharModalAdicionarProvedor();
            }
        }
    </script>
</body>
</html>