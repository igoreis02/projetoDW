<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
require_once 'conexao_bd.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Solicitações de Clientes</title>
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
            width: 95%;
            max-width: 1200px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 2rem;
        }

        .header-container h2 {
            font-size: 2.2em;
            color: black;
            margin: 0;
        }

        .close-btn,
        .back-btn-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }

        .close-btn {
            right: 0;
        }

        .back-btn-icon {
            left: 0;
        }

        .close-btn:hover,
        .back-btn-icon:hover {
            color: #333;
        }

        .container-botao-adicionar-solicitacao {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-solicitacao {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .container-pesquisa {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 10px;
            width: 100%;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .container-pesquisa input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
        }

        .filter-container {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            min-height: 38px;
        }

        .filter-btn {
            padding: 8px 18px;
            font-size: 0.9em;
            color: #4b5563;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
        }

        .filter-btn.active {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .solicitacoes-container {
            width: 100%;
        }

        .city-group {
            margin-bottom: 2.5rem;
        }

        .city-group-title {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--cor-principal);
        }

        .solicitacoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .item-solicitacao {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }

        .solicitacao-header {
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #d1d5db;
            width: 100%;
        }

        .solicitacao-header h3 {
            font-size: 1.3em;
            color: #111827;
            margin: 0;
        }

        .solicitacao-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-grow: 1;
            width: 100%;
        }

        .detail-item {
            font-size: 0.95em;
            color: #374151;
            line-height: 1.5;
        }

        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }

        .item-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            width: 100%;
        }

        .item-btn {
            padding: 6px 12px;
            font-size: 0.9em;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }

        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }

        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            color: white;
        }

        .status-pendente {
            background-color: #3b82f6;
        }

        .status-em-andamento {
            background-color: #f59e0b;
        }

        .status-concluido {
            background-color: #22c55e;
        }

        .status-cancelado {
            background-color: #ef4444;
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .formulario-modal .botao-salvar {
            background-color: #4CAF50;
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

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s ease infinite;
            margin: auto;
        }

        .carregando {
            display: none;
        }

        .hidden {
            display: none !important;
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
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Solicitações Clientes</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="container-botao-adicionar-solicitacao">
            <button class="botao-adicionar-solicitacao" onclick="abrirModalAdicionarSolicitacao()">Adicionar Solicitação</button>
        </div>

        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por solicitante, cidade, usuário...">
        </div>

        <div id="filterContainer" class="filter-container"></div>
        <div id="solicitacoesContainer" class="solicitacoes-container">
            <div id="loadingMessage" class="spinner"></div>
        </div>
    </main>

    <div id="modalAdicionarSolicitacao" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarSolicitacao()">&times;</span>
            <h2>Adicionar Nova Solicitação</h2>
            <form id="formularioAdicionarSolicitacao" class="formulario-modal">
                <label for="idCidadeAdicionar">Cidade:</label>
                <select id="idCidadeAdicionar" name="id_cidade" required>
                    <option value="">Carregando...</option>
                </select>
                <label for="solicitanteAdicionar">Solicitante:</label>
                <input type="text" id="solicitanteAdicionar" name="solicitante" required>
                <label for="descSolicitacaoAdicionar">Descrição da Solicitação:</label>
                <textarea id="descSolicitacaoAdicionar" name="desc_solicitacao" rows="3" required></textarea>
                <label for="desdobramentoSoliAdicionar">Desdobramento da Solicitação (Opcional):</label>
                <textarea id="desdobramentoSoliAdicionar" name="desdobramento_soli" rows="3"></textarea>
                <div id="mensagemAdicionarSolicitacao" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoAdicionar">Adicionar e Concluir</span>
                    <span id="carregandoAdicionarSolicitacao" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <div id="modalEdicaoSolicitacao" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoSolicitacao()">&times;</span>
            <h2>Editar Solicitação</h2>
            <form id="formularioEdicaoSolicitacao" class="formulario-modal">
                <input type="hidden" id="idSolicitacaoEdicao" name="id_solicitacao">
                <label for="idUsuarioEdicao">Usuário que adicionou:</label>
                <select id="idUsuarioEdicao" name="id_usuario" required>
                    <option value="">Carregando...</option>
                </select>
                <label for="idCidadeEdicao">Cidade:</label>
                <select id="idCidadeEdicao" name="id_cidade" required>
                    <option value="">Carregando...</option>
                </select>
                <label for="solicitanteEdicao">Solicitante:</label>
                <input type="text" id="solicitanteEdicao" name="solicitante" required>
                <label for="descSolicitacaoEdicao">Descrição da Solicitação:</label>
                <textarea id="descSolicitacaoEdicao" name="desc_solicitacao" rows="3" required></textarea>
                <label for="desdobramentoSoliEdicao">Desdobramento da Solicitação:</label>
                <textarea id="desdobramentoSoliEdicao" name="desdobramento_soli" rows="3"></textarea>
                <label for="statusSolicitacaoEdicao">Status:</label>
                <select id="statusSolicitacaoEdicao" name="status_solicitacao" required>
                    <option value="pendente">Pendente</option>
                    <option value="em andamento">Em Andamento</option>
                    <option value="concluido">Concluído</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <div id="mensagemEdicaoSolicitacao" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span id="textoBotaoSalvarEdicao">Salvar Alterações</span>
                    <span id="carregandoEdicaoSolicitacao" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <script src="js/solicitacoesClientes.js"></script>
</body>

</html>