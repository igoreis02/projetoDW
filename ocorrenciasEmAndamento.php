<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/ocorrenciasEmAndamento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <title>Serviços em Andamento</title>

</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Serviços em Andamento</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="controls-wrapper">
            <div class="main-controls-container">
                <button id="btnSimplificado" class="action-btn">Simplificado</button>
                <div class="action-buttons">
                    <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Ocorrências</button>
                    <button id="btnInstalacoes" class="action-btn" data-type="instalação">Instalações</button>
                    <button id="btSemaforica" class="action-btn hidden" data-type="semaforica">Semafóricas</button>
                </div>
                <div class="date-filter-container">
                    <label for="startDate">De:</label>
                    <input type="date" id="startDate">
                    <label for="endDate">Até:</label>
                    <input type="date" id="endDate">
                </div>
            </div>
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" id="searchInput" placeholder="Pesquisar por nome, referência ou ocorrência...">
                    <button id="clearFiltersBtn">Limpar Filtros</button>
                </div>
            </div>
        </div>

        <div id="filterContainer" class="filter-container"></div>

        <div id="simplifiedView" class="hidden"></div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">

        </div>
        <div id="pageLoader" class="main-loading-state">
            <div class="main-loading-spinner"></div>
            <span>Carregando ocorrências...</span>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>

        <button id="btnVoltarAoTopo" title="Voltar ao topo">
            <i class="fas fa-arrow-up"></i>
        </button>

    </div>

    <div id="concluirModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="concluirModalTitle">Concluir Reparo</h3>
                <button class="modal-close" onclick="closeModal('concluirModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="concluirModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>

                <div id="camposCorretiva">
                    <div class="form-group">
                        <label>Ocorrência</label>
                        <p id="concluirOcorrenciaText"></p>
                    </div>
                    <div class="form-group">
                        <label>Datas de Execução</label>
                        <div class="date-inputs">
                            <div class="form-group"><label for="concluirInicioReparo">Início</label><input type="date" id="concluirInicioReparo"></div>
                            <div class="form-group"><label for="concluirFimReparo">Fim</label><input type="date" id="concluirFimReparo"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Técnicos Envolvidos</label>
                        <div id="concluirTecnicosContainer" class="choice-buttons"></div>
                    </div>
                    <div class="form-group">
                        <label>Veículos Utilizados</label>
                        <div id="concluirVeiculosContainer" class="choice-buttons"></div>
                    </div>
                    <div class="form-group">
                        <label for="materiaisUtilizados">Materiais Utilizados</label>
                        <div class="input-group">
                            <input type="text" id="materiaisUtilizados" placeholder="Ex: Switch, conector, etc.">
                            <label class="checkbox-label"><input type="checkbox" id="nenhumMaterialCheckbox"> Nenhum</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Houve rompimento de lacre?</label>
                        <div class="choice-buttons lacre-buttons">
                            <button id="lacreSimBtn" class="choice-btn">Sim</button>
                            <button id="lacreNaoBtn" class="choice-btn selected">Não</button>
                        </div>
                    </div>
                    <div id="lacreFieldsContainer" class="form-group lacre-fields hidden">
                        <label for="numeroLacre">Número do lacre:</label><input type="text" id="numeroLacre" placeholder="Digite o número do lacre">
                        <label for="infoRompimento">Qual lacre foi rompido?</label><input type="text" id="infoRompimento" placeholder="Ex: Metrológico, switch">
                    </div>
                    <div class="form-group">
                        <label for="reparoFinalizado">Descrição do Reparo Realizado</label>
                        <textarea id="reparoFinalizado" rows="3" placeholder="Descreva o que foi feito..."></textarea>
                    </div>
                </div>

                <div id="camposInstalacao" class="hidden">
                    <p class="rotulo-pergunta">Informe a data de conclusão de cada etapa:</p>
                    <div class="instalacao-checklist">
                        <div class="item-checklist"><label for="dataBase">Data Instalação<br><b>Base:</b></label><input type="date" id="dataBase"></div>
                        <div class="item-checklist"><label for="dataLaco">Data Instalação<br><b>Laço:</b></label><input type="date" id="dataLaco"></div>
                        <div class="item-checklist"><label for="dataInfra">Data Instalação<br><b>Infraestrutura:</b></label><input type="date" id="dataInfra"></div>
                        <div class="item-checklist"><label for="dataEnergia">Data Instalação<br><b>Energia:</b></label><input type="date" id="dataEnergia"></div>
                        <div class="item-checklist"><label for="dataProvedor">Data Instalação<br><b>Provedor:</b></label><input type="date" id="dataProvedor"></div>
                    </div>
                </div>

                <div id="reparoErrorMessage" class="modal-error-message hidden"></div>
            </div>

            <div class="modal-footer" id="footerCorretiva">
                <p id="conclusionSuccessMessage" class="hidden" style="color: green; font-weight: bold; width: 100%; text-align: center;"></p>
                <button class="modal-btn btn-secondary" onclick="closeModal('concluirModal')">Cancelar</button>
                <button id="saveConclusionBtn" class="modal-btn btn-primary" onclick="saveConclusion()">
                    Concluir Reparo <div id="conclusionSpinner" class="spinner" style="display: none;"></div>
                </button>
            </div>

            <div class="modal-footer hidden" id="footerInstalacao">
                <button id="btnSalvarProgresso" class="modal-btn btn-secondary">Salvar Progresso</button>
                <button id="btnConcluirInstalacao" class="modal-btn btn-primary">Concluir Instalação</button>
            </div>
        </div>
    </div>

    <div id="partialConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <h3>Confirmar Instalação Parcial</h3>
            <p>Você está concluindo a instalação de:</p>
            <ul id="listaItensConcluidos" style="list-style-type: disc; margin: 15px 0 20px 30px; text-align: left;"></ul>
            <p>As outras etapas permanecerão pendentes e esta OS voltará para a base. Deseja continuar?</p>
            <div class="modal-footer" style="border-top: none; justify-content: center;">
                <button id="btnCancelarParcial" class="modal-btn btn-secondary">Cancelar</button>
                <button id="btnConfirmarParcial" class="modal-btn btn-primary">Sim, Confirmar</button>
            </div>
        </div>
    </div>

    <div id="fullConfirmModal" class="modal">
        <div class="modal-content" style="max-width: 450px; text-align: center;">
            <h3>Confirmar Conclusão Total</h3>
            <p>Todas as etapas necessárias foram preenchidas. Deseja concluir totalmente esta instalação?</p>
            <div class="modal-footer" style="border-top: none; justify-content: center;">
                <button id="btnCancelarTotal" class="modal-btn btn-secondary">Cancelar</button>
                <button id="btnConfirmarTotal" class="modal-btn btn-primary">Sim, Concluir</button>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal confirmation-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirmationModalTitle"></h3>
                <button class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmationModalText"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                <button id="confirmActionButton" class="modal-btn btn-primary">Confirmar</button>
            </div>
        </div>
    </div>


    <script src="js/ocorrenciasEmAndamento.js" defer></script>
</body>

</html>