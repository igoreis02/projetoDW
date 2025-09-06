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
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos gerais (sem alterações) */
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

        .item-cidade .botao-editar {
            background-color: #ffc107;
            color: #333;
        }

        .item-cidade .botao-excluir {
            background-color: #dc3545;
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

        .item-cidade .botao-editar:hover {
            background-color: #e0a800;
        }

        .item-cidade .botao-excluir:hover {
            background-color: #c82333;
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

        /* NOVO: Estilo para checkboxes */
        .checkbox-container {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
            align-items: center;
        }

        .checkbox-container label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: normal;
            margin-top: 0;
        }

        .checkbox-container input[type="checkbox"] {
            width: auto;
            height: 1.2em;
            width: 1.2em;
        }

        .validation-message-cidades {
            color: #721c24;
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="titulo-cabecalho">Gerenciar Cidades</h1>
        </header>
        <div class="container-botao-adicionar-cidade">
            <button class="botao-adicionar-cidade" onclick="abrirModalAdicionarCidade()">Adicionar Cidade</button>
        </div>
        <div id="containerListaCidades"></div>
    </main>

    <div id="modalAdicionarCidade" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarCidade()">&times;</span>
            <h2>Adicionar Nova Cidade</h2>
            <form id="formularioAdicionarCidade" class="formulario-modal">
                <label for="nomeCidadeAdicionar">Nome da Cidade:</label>
                <input type="text" id="nomeCidadeAdicionar" required>

                <label for="siglaCidadeAdicionar">Sigla da Cidade:</label>
                <input type="text" id="siglaCidadeAdicionar" required>

                <label for="codCidadeAdicionar">Código da Cidade:</label>
                <input type="text" id="codCidadeAdicionar" required>

                <label>Possui manutenção para:</label>
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="addSemaforica"> Semáforo
                    </label>
                    <label>
                        <input type="checkbox" id="addRadares"> Radar
                    </label>
                </div>
                <div class="validation-message-cidades" id="addValidationMessage">Selecione ao menos uma opção.</div>

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
                <input type="hidden" id="idCidadeEdicao">

                <label for="nomeCidadeEdicao">Nome da Cidade:</label>
                <input type="text" id="nomeCidadeEdicao" required>

                <label for="siglaCidadeEdicao">Sigla da Cidade:</label>
                <input type="text" id="siglaCidadeEdicao" required>

                <label for="codCidadeEdicao">Código da Cidade:</label>
                <input type="text" id="codCidadeEdicao" required>

                <label>Possui manutenção para:</label>
                <div class="checkbox-container">
                    <label>
                        <input type="checkbox" id="editSemaforica"> Semáforo
                    </label>
                    <label>
                        <input type="checkbox" id="editRadares"> Radar
                    </label>
                </div>
                <div class="validation-message-cidades" id="editValidationMessage">Selecione ao menos uma opção.</div>

                <div id="mensagemEdicaoCidade" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar Alterações</span>
                    <span id="carregandoEdicaoCidade" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <script src="js/cidades.js"></script>
</body>

</html>