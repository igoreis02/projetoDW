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
    <div class="background"></div>
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

    <script src="js/usuarios.js"></script>
</body>

</html>
