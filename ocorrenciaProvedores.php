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
    <title>Ocorrências de Provedores</title>
    <link rel="stylesheet" href="css/ocorrenciasProvedores.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências de Provedores</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <div class="controls-wrapper">
        <div class="main-controls-container">
            <div class="action-buttons" id="typeFilterContainer">
                <button class="action-btn active" data-type="manutencao">Ocorrências</button>
                <button class="action-btn" data-type="instalacao">Instalações</button>
            </div>
            <div class="date-filter-container">
                <label for="startDate">De:</label>
                <input type="date" id="startDate">
                <label for="endDate">Até:</label>
                <input type="date" id="endDate">
            </div>
        </div>
        </div>

        <div class="search-container">
            <div class="search-spacer"></div> <input type="text" id="searchInput" placeholder="Pesquisar por nome, referência, problema ou endereço...">
            <button id="clearFiltersBtn">Limpar Filtros</button>
        </div>

        <div id="statusFilterContainer" class="filter-container">
            <button class="filter-btn active todos" data-status="todos">Todos</button>
            <button class="filter-btn pendente" data-status="pendente">Pendente</button>
            <button class="filter-btn concluido" data-status="concluido">Concluído</button>
            <button class="filter-btn cancelado" data-status="cancelado">Cancelado</button>
        </div>

        <div id="cityFilterContainer" class="filter-container" style="padding-top: 0; margin-bottom: 2rem;"></div>

        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <div id="pageLoader" class="main-loading-state">
                <div class="main-loading-spinner"></div>
                <span>Carregando ocorrências...</span>
            </div>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>

        <button id="btnVoltarAoTopo" title="Voltar ao topo">
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
    <div id="concluirModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Concluir Ocorrência</h3>
                <button class="modal-close" onclick="closeModal('concluirModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="concluirModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Problema Reportado</label>
                    <p id="concluirOcorrenciaText"></p>
                </div>

                <div class="conclusion-options">
                    <button id="btnProvedor" class="option-btn">Provedor</button>
                    <button id="btnInLoco" class="option-btn">Técnico inLoco</button>
                    <button id="btnSemIntervencao" class="option-btn">Sem Intervenção</button>
                    <button id="btnTecnicoDw" class="option-btn">Técnico DW</button>
                </div>
                <p class="error-message" id="concluirModalError"></p>

                <div id="reparoRealizadoContainer" class="form-group">
                    <label for="reparoRealizadoTextarea">Descrição do Reparo</label>
                    <textarea id="reparoRealizadoTextarea" placeholder="Descreva o serviço que foi realizado..."></textarea>
                </div>
                <p class="error-message" id="reparoRealizadoError"></p>

                <div id="problemaTecnicoDwContainer" class="form-group hidden">
                    <label for="problemaTecnicoDwTextarea">Reportar Problema para Técnico DW</label>
                    <textarea id="problemaTecnicoDwTextarea" placeholder="Descreva o problema a ser resolvido pelo técnico..."></textarea>
                </div>
                <p class="error-message" id="problemaTecnicoDwError"></p>
            </div>
            <div class="modal-footer">
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('concluirModal')">Cancelar</button>
                    <button class="modal-btn btn-primary" onclick="handleConclusion()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>


    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Ocorrência</h3><button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="editModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label for="editOcorrenciaTextarea">Descrição do Problema</label>
                    <textarea id="editOcorrenciaTextarea"></textarea>
                </div>
                <div id="editReparoGroup" class="form-group hidden">
                    <label for="editReparoTextarea">Descrição do Reparo</label>
                    <textarea id="editReparoTextarea"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <p id="editMessage" class="message hidden"></p>
                <div id="editButtons" class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                    <button id="saveEditBtn" class="modal-btn btn-primary edit" onclick="saveOcorrenciaEdit()">
                        <span>Salvar Alterações</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="confirmationModalTitle"></h3><button class="modal-close"
                    onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; font-size: 1.1em;">
                <p id="confirmationModalText"></p><strong id="confirmReparoText" class="hidden"
                    style="display: block; margin-top: 10px; font-style: italic;"></strong>
            </div>
            <div class="modal-footer">
                <p id="confirmationMessage" class="message hidden"></p>
                <div id="confirmationButtons" class="modal-footer-buttons"><button class="modal-btn btn-secondary"
                        onclick="closeModal('confirmationModal')">Não</button><button id="confirmActionButton"
                        class="modal-btn btn-primary"><span id="confirmActionText"></span><span id="confirmSpinner"
                            class="spinner"></span></button></div>
            </div>
        </div>
    </div>

    <script src="js/ocorrenciasProvedores.js"></script>
</body>

</html>