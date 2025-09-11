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
            max-width: 1400px;
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
        }

        .close-btn {
            right: 0;
        }

        .back-btn-icon {
            left: 0;
        }

        .top-controls-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .botao-adicionar-solicitacao {
            background-color: #112058;
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .date-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-filter label {
            font-weight: 600;
            color: #374151;
        }

        .date-filter input {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .container-pesquisa {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        #campoPesquisa {
            width: 100%;
            padding: 10px;
            border-radius: 20px; /* Bordas arredondadas */
            border: 1px solid #d1d5db;
            font-size: 1em;
            outline: none; /* Remove a borda de foco */
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        #campoPesquisa:focus {
            border-color: #112058;
            box-shadow: 0 0 0 3px rgba(17, 32, 88, 0.1);
        }

        .container-pesquisa input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 400px;
        }

        .status-filters {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
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

        /* ESTILOS ATUALIZADOS */
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
            color: white;
            border-color: transparent;
        }

        .filter-btn.active[data-status="todos"] {
            background-color: #6b7280;
        }

        .filter-btn.active[data-status="pendente"] {
            background-color: #3b82f6;
        }

        .filter-btn.active[data-status="concluido"] {
            background-color: #22c55e;
        }

        .filter-btn.active[data-status="cancelado"] {
            background-color: #ef4444;
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
            width: 100%;
        }

        .solicitacoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .item-solicitacao.status-pendente {
            border-left-color: #3b82f6;
        }

        .item-solicitacao.status-em-andamento {
            border-left-color: #f59e0b;
        }

        .item-solicitacao.status-concluido {
            border-left-color: #22c55e;
        }

        .item-solicitacao.status-cancelado {
            border-left-color: #ef4444;
        }

        .item-solicitacao {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left-width: 5px;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
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
            color: white;
        }

        .edit-btn {
            background-color: #3b82f6;
        }

        .concluir-btn {
            background-color: #22c55e;
        }

        .cancel-btn {
            background-color: #ef4444;
        }

        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            color: white;
        }

        .status-pendente .status-tag {
            background-color: #3b82f6;
        }

        .status-em-andamento .status-tag {
            background-color: #f59e0b;
        }

        .status-concluido .status-tag {
            background-color: #22c55e;
        }

        .status-cancelado .status-tag {
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

        .formulario-modal input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
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

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s ease infinite;
            margin: auto;
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

        .status-toggle {
            margin-top: 1rem;
        }

        .status-toggle strong {
            display: block;
            margin-bottom: 0.5rem;
        }

        .status-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .status-btn {
            padding: 8px 16px;
            border: 1px solid #ccc;
            border-radius: 20px;
            cursor: pointer;
            background-color: #f0f0f0;
        }

        .status-btn.active {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .desdobramento-container {
            display: none;
        }
        .voltar-btn {
            display: inline-block;
            width: 30%;
            padding: 15px;
            margin-top: 30px;
            text-align: center;
            background-color: #112058;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .voltar-btn:hover {
            background-color: #09143fff;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem 0;
            }

            .card {
                width: 100%;
                padding: 1.5rem;
                border-radius: 0;
                box-shadow: none;
            }

            .header-container h2 {
                font-size: 1.6em;
            }

            .close-btn,
            .back-btn-icon {
                font-size: 1.5em;
            }
            
            /* Controles superiores empilhados */
            .top-controls-container {
                flex-direction: column;
                align-items: stretch; /* Faz os itens ocuparem a largura total */
                gap: 1rem;
            }
            .status-filters {
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: center;
        }
            .botao-adicionar-solicitacao {
                width: 100%;
                padding: 15px;
                font-size: 1em;
            }

            .date-filter input {
                width: 100%;
            }

            .container-pesquisa input {
                max-width: none; /* Permite ocupar a largura total */
            }

            /* Grade de solicitações vira coluna única */
            .solicitacoes-grid {
                grid-template-columns: 1fr;
            }

            .city-group-title {
                font-size: 1.5em;
            }
            
            /* Botões de ação com melhor espaçamento */
            .item-actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .item-btn {
                flex-grow: 1; /* Faz os botões crescerem para preencher o espaço */
                text-align: center;
            }

            /* Modal ajustado para telas menores */
            .conteudo-modal {
                width: 95%;
                padding: 1.5rem 1rem;
            }

            .formulario-modal button {
                padding: 0.8rem;
                font-size: 1rem;
            }

            /* Botão Voltar ocupa largura total */
            .voltar-btn {
                width: 100%;
                padding: 12px;
                margin-top: 12px;
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

        <div class="top-controls-container">
            <button class="botao-adicionar-solicitacao" onclick="abrirModalAdicionarSolicitacao()">Adicionar
                Solicitação</button>
            <div class="date-filter">
                <label for="startDate">De:</label>
                <input type="date" id="startDate">
                <label for="endDate">Até:</label>
                <input type="date" id="endDate">
            </div>
        </div>

        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por solicitante, cidade, usuário...">
        </div>

        <div id="statusFilters" class="status-filters">
            <button class="filter-btn active" data-status="todos">Todos</button>
            <button class="filter-btn" data-status="pendente">Pendente</button>
            <button class="filter-btn" data-status="concluido">Concluído</button>
            <button class="filter-btn" data-status="cancelado">Cancelado</button>
        </div>

        <div id="filterContainer" class="filter-container"></div>
        <div id="solicitacoesContainer" class="solicitacoes-container">
            <div id="loadingMessage" class="spinner"></div>
        </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </main>

    <div id="modalAdicionarSolicitacao" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarSolicitacao()">&times;</span>
            <h2>Adicionar Nova Solicitação</h2>
            <form id="formularioAdicionarSolicitacao" class="formulario-modal">
                <input type="hidden" id="statusSolicitacaoAdicionar" name="status_solicitacao" value="pendente">
                <label for="idCidadeAdicionar">Cidade:</label>
                <select id="idCidadeAdicionar" name="id_cidade" required></select>
                <label for="solicitanteAdicionar">Solicitante:</label>
                <input type="text" id="solicitanteAdicionar" name="solicitante" required>
                <label for="tipoSolicitacaoAdicionar">Tipo de Solicitação:</label>
                <input type="text" id="tipoSolicitacaoAdicionar" name="tipo_solicitacao" required>
                <label for="descSolicitacaoAdicionar">Descrição da Solicitação:</label>
                <textarea id="descSolicitacaoAdicionar" name="desc_solicitacao" rows="3" required></textarea>

                <div class="status-toggle">
                    <strong>Status:</strong>
                    <div class="status-buttons">
                        <button type="button" class="status-btn active" data-status="pendente">Pendente</button>
                        <button type="button" class="status-btn" data-status="concluido">Concluída</button>
                    </div>
                </div>

                <div id="desdobramentoContainerAdicionar" class="desdobramento-container">
                    <label for="desdobramentoSoliAdicionar">Desdobramento da Solicitação:</label>
                    <textarea id="desdobramentoSoliAdicionar" name="desdobramento_soli" rows="3"></textarea>
                </div>

                <div id="mensagemAdicionarSolicitacao" class="mensagem" style="display: none;"></div>
                <button type="submit" id="botaoSalvarAdicionar" class="botao-salvar"><span>Adicionar</span></button>
            </form>
        </div>
    </div>
    <div id="modalEdicaoSolicitacao" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoSolicitacao()">&times;</span>
            <h2>Editar Solicitação</h2>
            <form id="formularioEdicaoSolicitacao" class="formulario-modal">
                <input type="hidden" id="idSolicitacaoEdicao" name="id_solicitacao">

                <label for="usuarioEdicao">Usuário que adicionou:</label>
                <input type="text" id="usuarioEdicao" name="nome_usuario" disabled>

                <label for="cidadeEdicao">Cidade:</label>
                <input type="text" id="cidadeEdicao" name="nome_cidade" disabled>

                <label for="solicitanteEdicao">Solicitante:</label>
                <input type="text" id="solicitanteEdicao" name="solicitante" required>
                <label for="tipoSolicitacaoEdicao">Tipo de Solicitação:</label>
                <input type="text" id="tipoSolicitacaoEdicao" name="tipo_solicitacao" required>
                <label for="descSolicitacaoEdicao">Descrição da Solicitação:</label>
                <textarea id="descSolicitacaoEdicao" name="desc_solicitacao" rows="3" required></textarea>
                <label for="statusSolicitacaoEdicao">Status:</label>
                <select id="statusSolicitacaoEdicao" name="status_solicitacao" required>
                    <option value="pendente">Pendente</option>
                    <option value="em andamento">Em Andamento</option>
                    <option value="concluido">Concluído</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <div id="mensagemEdicaoSolicitacao" class="mensagem" style="display: none;"></div>
                <button type="submit" id="botaoSalvarEdicao" class="botao-salvar"><span>Salvar
                        Alterações</span></button>
            </form>
        </div>
    </div>
    <div id="modalConcluirSolicitacao" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalConcluirSolicitacao()">&times;</span>
            <h2>Concluir Solicitação</h2>
            <form id="formularioConcluirSolicitacao" class="formulario-modal">
                <input type="hidden" id="idSolicitacaoConcluir" name="id_solicitacao">
                <p><strong>Solicitante:</strong> <span id="solicitanteConcluir"></span></p>
                <p><strong>Solicitação:</strong> <span id="descSolicitacaoConcluir"></span></p>
                <label for="desdobramentoSoliConcluir">Desdobramento da Solicitação (Obrigatório):</label>
                <textarea id="desdobramentoSoliConcluir" name="desdobramento_soli" rows="4" required></textarea>
                <div id="mensagemConcluirSolicitacao" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar"><span>Concluir</span></button>
            </form>
        </div>
    </div>

    <script src="js/solicitacoesClientes.js"></script>
</body>

</html>