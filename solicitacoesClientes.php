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
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="css/solicitacoesClientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
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
            <div id="pageLoader" class="main-loading-state">
                <div class="main-loading-spinner"></div>
                <span>Carregando solicitações...</span>
            </div>
        </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>

        <button id="btnVoltarAoTopo" title="Voltar ao topo">
            <i class="fas fa-arrow-up"></i>

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