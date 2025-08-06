<?php
session_start(); // Inicia ou resume a sessão
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
  // Redireciona para a página de login
  header("Location: index.html");
  exit;
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Opcional: Redirecionar se o tipo de usuário não tiver permissão para gerenciar usuários
if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] !== 'administrador' && $_SESSION['tipo_usuario'] !== 'provedor')) {
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
    <title>Gerenciar Usuários</title>
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
        
        /* Layout atualizado para o cabeçalho e botões */
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
            flex-grow: 1; /* Garante que o título ocupe o espaço central */
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
            position: absolute; /* Posição absoluta para o botão voltar */
            top: 2rem;
            left: 5%;
        }

        .botao-voltar:hover {
            background-color: var(--cor-secundaria);
        }

        .container-botao-adicionar-usuario {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-usuario {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .botao-adicionar-usuario:hover {
            background-color: var(--cor-secundaria);
        }
        
        /* Manter o layout original da pesquisa para o resto do conteúdo */
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

        .lista-usuarios {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-usuario {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-usuario h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-usuario p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-usuario .botao-editar {
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

        .item-usuario .botao-editar:hover {
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
            
            /* Novo estilo para centralizar o conteúdo do botão */
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
            margin: auto; /* Adicionado para centralizar o spinner */
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
            <h1 class="titulo-cabecalho">Gerenciar Usuários</h1>
        </header>
        <!-- O botão de adicionar usuário agora está em seu próprio container para ser alinhado à esquerda -->
        <div class="container-botao-adicionar-usuario">
            <button class="botao-adicionar-usuario" onclick="abrirModalAdicionarUsuario()">Adicionar Usuário</button>
        </div>

        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou e-mail...">
        </div>
        <div id="containerListaUsuarios">
            <!-- A lista de usuários será renderizada aqui pelo JavaScript -->
        </div>
    </main>

    <!-- Modal para Editar Usuário -->
    <div id="modalEdicaoUsuario" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoUsuario()">&times;</span>
            <h2>Editar Usuário</h2>
            <form id="formularioEdicaoUsuario" class="formulario-modal">
                <input type="hidden" id="idUsuarioEdicao">
                <label for="nomeEdicao">Nome:</label>
                <input type="text" id="nomeEdicao" name="nome" required>
                <label for="emailEdicao">E-mail:</label>
                <input type="email" id="emailEdicao" name="email" required>
                <label for="telefoneEdicao">Telefone:</label>
                <input type="text" id="telefoneEdicao" name="telefone">
                <label for="tipoUsuarioEdicao">Tipo de Usuário:</label>
                <select id="tipoUsuarioEdicao" name="tipo_usuario" required>
                    <option value="administrador">Administrador</option>
                    <option value="tecnico">Técnico</option>
                    <option value="provedor">Provedor</option>
                    <option value="comum">Comum</option>
                </select>
                <label for="statusUsuarioEdicao">Status do Usuário:</label>
                <select id="statusUsuarioEdicao" name="status_usuario" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
                <div id="mensagemEdicaoUsuario" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar</span>
                    <span id="carregandoEdicaoUsuario" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para Adicionar Usuário -->
    <div id="modalAdicionarUsuario" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarUsuario()">&times;</span>
            <h2>Adicionar Novo Usuário</h2>
            <form id="formularioAdicionarUsuario" class="formulario-modal">
                <label for="nomeAdicionar">Nome:</label>
                <input type="text" id="nomeAdicionar" name="nome" required>
                <label for="emailAdicionar">E-mail:</label>
                <input type="email" id="emailAdicionar" name="email" required readonly>
                <label for="telefoneAdicionar">Telefone:</label>
                <input type="text" id="telefoneAdicionar" name="telefone">
                <label for="tipoUsuarioAdicionar">Tipo de Usuário:</label>
                <select id="tipoUsuarioAdicionar" name="tipo_usuario" required>
                    <option value="administrador">Administrador</option>
                    <option value="tecnico">Técnico</option>
                    <option value="provedor">Provedor</option>
                    <option value="comum">Comum</option>
                </select>
                <div id="mensagemAdicionarUsuario" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoAdicionar">Adicionar</span>
                    <span id="carregandoAdicionarUsuario" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Variável global para armazenar a lista de usuários
        let dadosUsuarios = [];

        // Funções de utilidade para mostrar mensagens e spinners
        function exibirMensagem(elemento, mensagem, tipo) {
            elemento.textContent = mensagem;
            elemento.className = `mensagem ${tipo}`;
            elemento.style.display = 'block';
        }

        function alternarCarregamento(botao, spinner, mostrar) {
            if (mostrar) {
                botao.disabled = true;
                spinner.style.display = 'block';
                botao.querySelector('span').style.display = 'none'; // Esconde o texto do botão
            } else {
                botao.disabled = false;
                spinner.style.display = 'none';
                botao.querySelector('span').style.display = 'block'; // Mostra o texto do botão como um elemento de bloco
            }
        }
        
        // Função para fechar o modal de edição
        function fecharModalEdicaoUsuario() {
            document.getElementById('modalEdicaoUsuario').classList.remove('esta-ativo');
            document.getElementById('mensagemEdicaoUsuario').style.display = 'none';
        }

        // Função para fechar o modal de adição
        function fecharModalAdicionarUsuario() {
            document.getElementById('modalAdicionarUsuario').classList.remove('esta-ativo');
            document.getElementById('mensagemAdicionarUsuario').style.display = 'none';
            document.getElementById('formularioAdicionarUsuario').reset();
        }

        /**
         * Abre o modal de adição de usuário.
         * Reseta o estado do formulário e do botão antes de abrir.
         */
        function abrirModalAdicionarUsuario() {
            const formularioAdicionar = document.getElementById('formularioAdicionarUsuario');
            const botaoEnviar = formularioAdicionar.querySelector('.botao-salvar');
            const carregandoAdicionarUsuario = document.getElementById('carregandoAdicionarUsuario');
            const mensagemAdicionarUsuario = document.getElementById('mensagemAdicionarUsuario');
            const campoNomeAdicionar = document.getElementById('nomeAdicionar');
            const campoEmailAdicionar = document.getElementById('emailAdicionar');

            formularioAdicionar.reset();
            alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
            botaoEnviar.style.display = 'flex'; // Usar flex para centralizar o spinner
            mensagemAdicionarUsuario.style.display = 'none';

            // Adiciona um listener para preencher o email com base no nome
            campoNomeAdicionar.addEventListener('input', (evento) => {
                const nomeCompleto = evento.target.value.trim();
                if (nomeCompleto) {
                    // Extrai o primeiro nome, remove espaços e converte para minúsculas
                    const primeiroNome = nomeCompleto.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
                    campoEmailAdicionar.value = `${primeiroNome}@deltaway.com.br`;
                } else {
                    campoEmailAdicionar.value = '';
                }
            });

            document.getElementById('modalAdicionarUsuario').classList.add('esta-ativo');
        }

        /**
         * Abre o modal de edição e preenche com os dados do usuário.
         * A função busca o usuário na lista já carregada na página.
         * @param {number} idUsuario - O ID do usuário a ser editado.
         */
        function abrirModalEdicaoUsuario(idUsuario) {
            // Busca o usuário na lista de dados global
            const usuario = dadosUsuarios.find(u => u.id_usuario == idUsuario);
            
            if (usuario) {
                // Resetar o estado do formulário e do botão antes de preencher
                const formularioEdicao = document.getElementById('formularioEdicaoUsuario');
                const botaoEnviar = formularioEdicao.querySelector('.botao-salvar');
                const carregandoEdicaoUsuario = document.getElementById('carregandoEdicaoUsuario');
                const mensagemEdicaoUsuario = document.getElementById('mensagemEdicaoUsuario');

                alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, false);
                botaoEnviar.style.display = 'flex'; // Usar flex para centralizar o spinner
                mensagemEdicaoUsuario.style.display = 'none';

                // Preenche os campos do formulário com os dados do usuário encontrado
                document.getElementById('idUsuarioEdicao').value = usuario.id_usuario;
                document.getElementById('nomeEdicao').value = usuario.nome;
                document.getElementById('emailEdicao').value = usuario.email;
                // Use a string vazia se o telefone for nulo
                document.getElementById('telefoneEdicao').value = usuario.telefone || ''; 
                document.getElementById('tipoUsuarioEdicao').value = usuario.tipo_usuario;
                document.getElementById('statusUsuarioEdicao').value = usuario.status_usuario;

                // Mostra o modal
                document.getElementById('modalEdicaoUsuario').classList.add('esta-ativo');
            } else {
                // Caso o usuário não seja encontrado, exibe uma mensagem
                exibirMensagem(document.getElementById('mensagemEdicaoUsuario'), 'Usuário não encontrado. Recarregue a página e tente novamente.', 'erro');
            }
        }

        // Função principal para buscar e renderizar a lista de usuários
        async function buscarEExibirUsuarios(termoPesquisa = '') {
            const containerListaUsuarios = document.getElementById('containerListaUsuarios');
            containerListaUsuarios.innerHTML = 'Carregando usuários...';

            try {
                // Correção: Cria uma URL robusta para evitar erros de análise de caminho
                const url = new URL('get_usuario.php', window.location.href);
                url.searchParams.set('search', termoPesquisa);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    // Armazena os dados na variável global
                    dadosUsuarios = data.users;
                    exibirListaUsuarios(dadosUsuarios);
                } else {
                    containerListaUsuarios.innerHTML = `<div class="mensagem erro">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Erro ao buscar usuários:', error);
                containerListaUsuarios.innerHTML = `<div class="mensagem erro">Ocorreu um erro ao buscar os usuários.</div>`;
            }
        }

        // Função para renderizar a lista de usuários na tela
        function exibirListaUsuarios(usuarios) {
            const containerListaUsuarios = document.getElementById('containerListaUsuarios');
            if (usuarios.length === 0) {
                containerListaUsuarios.innerHTML = '<div class="mensagem">Nenhum usuário encontrado.</div>';
                return;
            }

            const htmlListaUsuarios = usuarios.map(usuario => `
                <div class="item-usuario">
                    <h3>${usuario.nome}</h3>
                    <p><strong>E-mail:</strong> ${usuario.email}</p>
                    <p><strong>Telefone:</strong> ${usuario.telefone || 'N/A'}</p>
                    <p><strong>Tipo:</strong> ${usuario.tipo_usuario}</p>
                    <p><strong>Status:</strong> ${usuario.status_usuario}</p>
                    <!-- O onclick agora chama a função abrirModalEdicaoUsuario com o ID do usuário -->
                    <button class="botao-editar" onclick="abrirModalEdicaoUsuario(${usuario.id_usuario})">Editar</button>
                </div>
            `).join('');

            containerListaUsuarios.innerHTML = `<div class="lista-usuarios">${htmlListaUsuarios}</div>`;
        }

        // Event listener para o campo de pesquisa
        document.getElementById('campoPesquisa').addEventListener('input', (evento) => {
            buscarEExibirUsuarios(evento.target.value);
        });

        // Event listener para o formulário de edição de usuário
        document.getElementById('formularioEdicaoUsuario').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagemEdicaoUsuario = document.getElementById('mensagemEdicaoUsuario');
            const botaoEnviar = this.querySelector('.botao-salvar');
            const carregandoEdicaoUsuario = document.getElementById('carregandoEdicaoUsuario');

            const dadosFormulario = new FormData(this);
            const usuario = Object.fromEntries(dadosFormulario.entries());
            usuario.id = document.getElementById('idUsuarioEdicao').value;
            
            exibirMensagem(mensagemEdicaoUsuario, 'Salvando...', 'sucesso');
            alternarCarregamento(botaoEnviar, carregandoEdicaoUsuario, true);

            // Simulação de chamada de API
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Sucesso
            exibirMensagem(mensagemEdicaoUsuario, 'Usuário salvo com sucesso!', 'sucesso');
            botaoEnviar.style.display = 'none'; // Esconde o botão após o sucesso

            setTimeout(() => {
                fecharModalEdicaoUsuario();
                buscarEExibirUsuarios(''); // Recarrega a lista
                botaoEnviar.style.display = 'flex'; // Mostra o botão novamente para a próxima edição
            }, 1500);
        });

        // Event listener para o formulário de adição de usuário
        document.getElementById('formularioAdicionarUsuario').addEventListener('submit', async function(evento) {
            evento.preventDefault();
            const mensagemAdicionarUsuario = document.getElementById('mensagemAdicionarUsuario');
            const botaoEnviar = this.querySelector('.botao-salvar');
            const carregandoAdicionarUsuario = document.getElementById('carregandoAdicionarUsuario');

            const dadosFormulario = new FormData(this);
            const usuario = Object.fromEntries(dadosFormulario.entries());

            alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, true);

            try {
                // Faz a chamada real para o novo script PHP
                const response = await fetch('add_usuario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(usuario)
                });
                const data = await response.json();

                if (data.success) {
                    exibirMensagem(mensagemAdicionarUsuario, 'Usuário adicionado com sucesso!', 'sucesso');
                    botaoEnviar.style.display = 'none'; // Esconde o botão após o sucesso

                    setTimeout(() => {
                        fecharModalAdicionarUsuario();
                        buscarEExibirUsuarios(''); // Recarrega a lista
                        botaoEnviar.style.display = 'flex'; // Mostra o botão novamente
                    }, 1500);
                } else {
                    exibirMensagem(mensagemAdicionarUsuario, data.message || 'Erro ao adicionar usuário.', 'erro');
                    alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
                }
            } catch (error) {
                console.error('Erro ao adicionar usuário:', error);
                exibirMensagem(mensagemAdicionarUsuario, 'Ocorreu um erro ao adicionar o usuário. Tente novamente.', 'erro');
                alternarCarregamento(botaoEnviar, carregandoAdicionarUsuario, false);
            }
        });

        /**
         * Aplica a máscara de telefone (62) 9 9292-9292 em tempo real.
         * Remove todos os caracteres não numéricos e formata a string.
         * @param {string} valor - O valor do campo de input.
         * @returns {string} - O valor formatado.
         */
        function mascararTelefone(valor) {
            if (!valor) return "";
            valor = valor.replace(/\D/g, ''); // Remove tudo que não é dígito
            
            // (XX) 9 XXXX-XXXX (11 dígitos para celular)
            if (valor.length > 10) {
                valor = valor.replace(/^(\d{2})(\d)(\d{4})(\d{4}).*/, '($1) $2 $3-$4');
            } 
            // (XX) XXXX-XXXX (10 dígitos para telefone fixo)
            else if (valor.length > 6) {
                valor = valor.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            }
            // (XX) XXXX
            else if (valor.length > 2) {
                valor = valor.replace(/^(\d{2})(\d{4})/, '($1) $2');
            } 
            // (XX)
            else if (valor.length > 0) {
                valor = valor.replace(/^(\d{2})/, '($1)');
            }
            
            return valor;
        }

        // Carrega a lista de usuários quando a página é carregada
        document.addEventListener('DOMContentLoaded', () => {
            buscarEExibirUsuarios();
            
            // Adiciona o event listener para a máscara de telefone
            const campoTelefoneAdicionar = document.getElementById('telefoneAdicionar');
            const campoTelefoneEdicao = document.getElementById('telefoneEdicao');

            campoTelefoneAdicionar.addEventListener('input', (evento) => {
                evento.target.value = mascararTelefone(evento.target.value);
            });

            campoTelefoneEdicao.addEventListener('input', (evento) => {
                evento.target.value = mascararTelefone(evento.target.value);
            });
        });

        // Fecha os modais se o usuário clicar fora
        window.onclick = function(evento) {
            if (evento.target == document.getElementById('modalEdicaoUsuario')) {
                fecharModalEdicaoUsuario();
            }
            if (evento.target == document.getElementById('modalAdicionarUsuario')) {
                fecharModalAdicionarUsuario();
            }
        }
    </script>
</body>

</html>
