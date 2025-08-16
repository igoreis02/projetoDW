<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
require_once 'conexao_bd.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Cidades</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .container-botao-adicionar-cidade {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-cidade {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-adicionar-cidade:hover {
            background-color: var(--cor-secundaria);
        }
        
        /* Novo estilo para a lista de cidades */
        .lista-cidades {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-cidade {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-cidade h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-cidade p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-cidade .btn-group {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 5px;
        }
        
        .item-cidade .botao-editar,
        .item-cidade .botao-excluir {
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .item-cidade .botao-editar {
            background-color: #ffc107;
            color: #333;
        }

        .item-cidade .botao-excluir {
            background-color: #dc3545;
        }

        .item-cidade .botao-editar:hover {
            background-color: #e0a800;
        }
        
        .item-cidade .botao-excluir:hover {
            background-color: #c82333;
        }
        
        /* Estilos do modal */
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
            <h1 class="titulo-cabecalho">Gerenciar Cidades</h1>
        </header>
        <div class="container-botao-adicionar-cidade">
            <button class="botao-adicionar-cidade" onclick="abrirModalAdicionarCidade()">Adicionar Cidade</button>
        </div>
        <div id="containerListaCidades">
            </div>
    </main>

    <div id="modalAdicionarCidade" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarCidade()">&times;</span>
            <h2>Adicionar Nova Cidade</h2>
            <form id="formularioAdicionarCidade" class="formulario-modal">
                <label for="nomeCidadeAdicionar">Nome da Cidade:</label>
                <input type="text" id="nomeCidadeAdicionar" name="nome" required>
                <div id="mensagemAdicionarCidade" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoAdicionar">Adicionar</span>
                    <span id="carregandoAdicionarCidade" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <div id="modalEdicaoCidade" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoCidade()">&times;</span>
            <h2>Editar Cidade</h2>
            <form id="formularioEdicaoCidade" class="formulario-modal">
                <input type="hidden" id="idCidadeEdicao" name="id_cidade">
                <label for="nomeCidadeEdicao">Nome da Cidade:</label>
                <input type="text" id="nomeCidadeEdicao" name="nome" required>
                <div id="mensagemEdicaoCidade" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar Alterações</span>
                    <span id="carregandoEdicaoCidade" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <script>
        let dadosCidades = [];

        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            if (mostrar) {
                botao.disabled = true;
                spinner.style.display = 'block';
                botao.querySelector('span').style.display = 'none';
            } else {
                botao.disabled = false;
                spinner.style.display = 'none';
                botao.querySelector('span').style.display = 'block';
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

        function abrirModalEdicaoCidade(cidade) {
            const formulario = document.getElementById('formularioEdicaoCidade');
            const botao = formulario.querySelector('.botao-salvar');
            const spinner = document.getElementById('carregandoEdicaoCidade');
            const mensagem = document.getElementById('mensagemEdicaoCidade');

            alternarCarregamento(botao, spinner, false);
            mensagem.style.display = 'none';

            document.getElementById('idCidadeEdicao').value = cidade.id_cidade;
            document.getElementById('nomeCidadeEdicao').value = cidade.nome;

            document.getElementById('modalEdicaoCidade').classList.add('esta-ativo');
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
                    <h3>${cidade.nome}</h3>
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
                const response = await fetch('get_cidades.php');
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
                    const response = await fetch('delete_city.php', {
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
                const response = await fetch('add_city.php', {
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
                const response = await fetch('update_city.php', {
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

    </script>
</body>

</html>