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
    <title>Ocorrências Pendentes</title>
    <link rel="stylesheet" href="css/ocorrenciasPendentes.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências Pendentes</h2>
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

        <div id="simplifiedView" class="hidden">
        </div>
        <div id="ocorrenciasContainer" class="ocorrencias-container">
            
        </div>
        <div id="pageLoader" class="main-loading-state">
                <div class="main-loading-spinner"></div>
                <span>Carregando ocorrências...</span>
            </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div id="cancelSelectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="cancelSelectionModalTitle">Selecionar para Cancelar</h3>
                <button class="modal-close" onclick="closeModal('cancelSelectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="cancelSelectionModalInfo"></div>
                <p>Selecione uma ou mais ocorrências que deseja cancelar:</p>
                <div id="cancelOcorrenciasContainer" class="choice-buttons-ocorrencias-container">
                </div>
            </div>
            <div class="modal-footer">
                <div id="cancelSelectionError" class="modal-error-message hidden"></div>
                <div id="cancelFooterButtons" class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('cancelSelectionModal')">Fechar</button>
                    <button id="confirmCancelBtn" class="modal-btn btn-primary" style="background-color: #ef4444;"
                        onclick="executeMultiCancel()">
                        <span>Confirmar Cancelamento</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="selectOcorrenciasModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="selectOcorrenciasModalTitle">Selecionar Ocorrências</h3>
                <button class="modal-close" onclick="closeAndCancelSelection()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="selectOcorrenciasModalInfo" class="modal-info-message"></div>
                <div class="form-group">
                    <label>Selecione as ocorrências que deseja incluir na atribuição:</label>
                    <div id="selectOcorrenciasContainer" class="choice-buttons-ocorrencias-container">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div id="selectOcorrenciasError" class="modal-error-message hidden"></div>
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeAndCancelSelection()">Cancelar</button>
                    <button class="modal-btn btn-primary" onclick="confirmOcorrenciaSelection()">Confirmar
                        Seleção</button>
                </div>
            </div>
        </div>
    </div>
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atribuir Técnico</h3>
                <button class="modal-close" onclick="closeModal('assignModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="assignModalInfo"></div>
                <div class="form-group">
                    <label>Selecione a data para execução:</label>
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="assignInicioReparo">Data início</label>
                            <input type="date" id="assignInicioReparo">
                        </div>
                        <div class="form-group">
                            <label for="assignFimReparo">Data fim</label>
                            <input type="date" id="assignFimReparo">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Selecione o(os) Técnico(s):</label>
                    <div id="assignTecnicosContainer" class="choice-buttons"></div>
                </div>
                <div class="form-group">
                    <label>Selecione o(os) Veículo(s):</label>
                    <div id="assignVeiculosContainer" class="choice-buttons"></div>
                </div>
            </div>
            <div class="modal-footer">
                <div id="assignErrorMessage" class="modal-error-message hidden"></div>
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('assignModal')">Fechar</button>
                    <button id="saveAssignmentBtn" class="modal-btn btn-primary" onclick="saveAssignment()">
                        <span>Salvar Atribuição</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="editOcorrenciaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Ocorrência</h3>
                <button class="modal-close" onclick="closeModal('editOcorrenciaModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="editOcorrenciaModalInfo"></div>
                <div class="form-group">
                    <label for="editOcorrenciaTextarea">Descrição da Ocorrência</label>
                    <textarea id="editOcorrenciaTextarea" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary"
                        onclick="closeModal('editOcorrenciaModal')">Cancelar</button>
                    <button class="modal-btn btn-primary" onclick="saveOcorrenciaUpdate()">Salvar Alteração</button>
                </div>
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
                <p id="confirmationMessage" class="modal-error-message hidden"></p>
            </div>
            <div id="confirmationFooter" class="modal-footer">
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                    <button id="confirmActionButton" class="modal-btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <div id="priorityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Nível de Prioridade</h3>
                <button class="modal-close" onclick="closeModal('priorityModal')">×</button>
            </div>
            <div class="modal-body">
                <p>Selecione o novo nível de prioridade para a(s) ocorrência(s) selecionada(s):</p>
                <div id="priorityModalInfo" class="modal-info-message"></div>
            </div>
            <div class="modal-footer">
                <div id="priorityErrorMessage" class="modal-error-message hidden"></div>
                <div class="modal-footer-buttons">
                    <button class="modal-btn" style="background-color: #ef4444; color: white;"
                        onclick="savePriority(1)">
                        <span>Urgente</span>
                        <div class="spinner hidden"></div>
                    </button>
                    <button class="modal-btn" style="background-color: #112058; color: white;"
                        onclick="savePriority(2)">
                        <span>Nível 1</span>
                        <div class="spinner hidden"></div>
                    </button>
                    <button class="modal-btn" style="background-color: #6b7280; color: white;"
                        onclick="savePriority(3)">
                        <span>Nível 2</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/ocorrenciasPendentes.js" defer></script>

    <button id="btnVoltarAoTopo" title="Voltar ao topo">
        <i class="fas fa-arrow-up"></i> </button>
</body>

</html>