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
    <title>Ocorrências de Processamento</title>
    <link rel="stylesheet" href="css/ocorrenciaProcessamento.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências Processamento</h2>
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
            <div class="search-spacer"></div>
            <input type="text" id="searchInput" placeholder="Pesquisar por nome, referência, problema ou endereço...">
            <button id="clearFiltersBtn">Limpar Filtros</button>
        </div>

        <div id="statusFilterContainer" class="filter-container">
            <button class="filter-btn active todos" data-status="todos">Todos</button>
            <button id="btnValidacao" class="filter-btn validacao" data-status="validacao" style="display: none;">Validação</button>
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
                <h3>Concluir Ocorrência de Processamento</h3>
                <button class="modal-close" onclick="closeModal('concluirModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="concluirModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Problema Reportado</label>
                    <p id="concluirOcorrenciaText"></p>
                </div>
                <div class="form-group">
                    <label for="reparoRealizadoTextarea">Descrição da Solução / Reparo</label>
                    <textarea id="reparoRealizadoTextarea" placeholder="Descreva a solução aplicada..."></textarea>
                    <p class="error-message" id="reparoRealizadoError"></p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('concluirModal')">Cancelar</button>
                    <button class="modal-btn btn-primary" onclick="handleConclusion()">Confirmar Conclusão</button>
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
                    <label for="editReparoTextarea">Descrição da Solução</label>
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
                <h3 id="confirmationModalTitle"></h3><button class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; font-size: 1.1em;">
                <p id="confirmationModalText"></p>
            </div>
            <div class="modal-footer">
                <p id="confirmationMessage" class="message hidden"></p>
                <div id="confirmationButtons" class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                    <button id="confirmActionButton" class="modal-btn btn-primary">
                        <span id="confirmActionText"></span>
                        <span id="confirmSpinner" class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="validarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Validar Reparo Técnico</h3>
                <button class="modal-close" onclick="closeModal('validarModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="validarModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Problema Reportado</label>
                    <p id="validarOcorrenciaText"></p>
                </div>
                <div class="form-group">
                    <label>Reparo Realizado pelo Técnico</label>
                    <p id="validarReparoText"></p>
                </div>
            </div>
            <div class="modal-footer">
                <p id="validarMessage" class="message hidden"></p>
                <div id="validarButtons" class="modal-footer-buttons">
                    <button id="btnNaoValidar" class="modal-btn btn-secondary" onclick="openRetornarModal()">Não Validar</button>
                    <button id="btnConfirmarValidacao" class="modal-btn btn-primary" onclick="handleValidar()">
                        <span>Validar</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="retornarModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Retornar Ocorrência</h3>
                <button class="modal-close" onclick="closeModal('retornarModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="retornarModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label for="retornarMotivoTextarea">Descreva o motivo do retorno (será a nova ocorrência)</label>
                    <textarea id="retornarMotivoTextarea" placeholder="Ex: O problema persiste, a solução não foi eficaz..."></textarea>
                    <p class="error-message" id="retornarMotivoError"></p>
                </div>
            </div>
            <div class="modal-footer">
                <p id="retornarMessage" class="message hidden"></p>
                <div id="retornarButtons" class="modal-footer-buttons">
                    <button id="btnCancelarRetorno" class="modal-btn btn-secondary" onclick="closeModal('retornarModal')">Cancelar</button>
                    <button id="btnConfirmarRetorno" class="modal-btn btn-primary cancel" onclick="handleRetornar()">
                        <span>Confirmar Retorno</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="editSelectionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Qual ocorrência você deseja editar?</h3>
                <button class="modal-close" onclick="closeModal('editSelectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="editSelectionEquipName" style="font-size: 1.2em; text-align: center; margin-top: 0;"></h4>
                <div id="editSelectionContainer">
                </div>
            </div>
        </div>
    </div>

    <div id="etiquetaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Concluir Fabricação de Etiqueta</h3>
                <button class="modal-close" onclick="closeModal('etiquetaModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="etiquetaModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Etiquetas finalizadas?</label>
                    <div class="choice-buttons">
                        <button id="etiquetaSimBtn" class="modal-btn btn-secondary">Sim</button>
                        <button id="etiquetaNaoBtn" class="modal-btn btn-secondary">Não</button>
                    </div>
                </div>
                <div id="etiquetaDataGroup" class="form-group hidden">
                    <label for="etiquetaDataInput">Data de Fabricação:</label>
                    <input type="date" id="etiquetaDataInput" style="padding: 0.75rem; border: 1px solid var(--cor-borda-principal); border-radius: 0.5rem;">
                    <p class="error-message" id="etiquetaDataError"></p>
                </div>
            </div>
            <div class="modal-footer">
                <p id="etiquetaMessage" class="message hidden"></p>
                <div id="etiquetaButtons" class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('etiquetaModal')">Cancelar</button>
                    <button id="saveEtiquetaBtn" class="modal-btn btn-primary hidden">
                        <span>Confirmar</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="provedorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Concluir contato com o provedor para instalação</h3>
                <button class="modal-close" onclick="closeModal('provedorModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="provedorModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>

                <div class="form-group">
                    <label>Contrato foi assinado?</label>
                    <div class="choice-buttons" id="contratoChoice">
                        <button class="modal-btn btn-secondary" data-value="true">Sim</button>
                        <button class="modal-btn btn-secondary" data-value="false">Não</button>
                    </div>
                    <p class="error-message" id="contratoError"></p>
                </div>

                <div id="abrirOcGroup" class="form-group">
                    <label>Abrir ocorrência de instalação?</label>
                    <div class="choice-buttons" id="abrirOcChoice">
                        <button class="modal-btn btn-secondary" data-value="true">Sim</button>
                        <button class="modal-btn btn-secondary" data-value="false">Não</button>
                    </div>
                    <p class="error-message" id="abrirOcError"></p>
                </div>

            </div>
            <div class="modal-footer">
                <p id="provedorMessage" class="message hidden"></p>
                <div id="provedorButtons" class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('provedorModal')">Cancelar</button>
                    <button id="saveProvedorBtn" class="modal-btn btn-primary">
                        <span>Confirmar</span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="instalacaoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Finalizar Checklist de Instalação</h3>
            <button class="modal-close" onclick="closeModal('instalacaoModal')">&times;</button>
        </div>
        <div class="modal-body">
            <h4 id="instalacaoModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
            
            <div class="form-group">
                <label>Câmeras adicionadas no VMS?</label>
                <div class="choice-buttons" id="vmsChoice">
                    <button class="modal-btn btn-secondary" data-value="true">Sim</button>
                    <button class="modal-btn btn-secondary" data-value="false">Não</button>
                </div>
            </div>

            <div class="form-group">
                <label>Atualizada lista terminal Login?</label>
                <div class="choice-buttons" id="terminalChoice">
                    <button class="modal-btn btn-secondary" data-value="true">Sim</button>
                    <button class="modal-btn btn-secondary" data-value="false">Não</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>Atualizada lista Coleta?</label>
                <div class="choice-buttons" id="coletaChoice">
                    <button class="modal-btn btn-secondary" data-value="true">Sim</button>
                    <button class="modal-btn btn-secondary" data-value="false">Não</button>
                </div>
            </div>

        </div>
        <div class="modal-footer">
             <p id="instalacaoMessage" class="message hidden"></p>
            <div id="instalacaoButtons" class="modal-footer-buttons">
                <button class="modal-btn btn-secondary" onclick="closeModal('instalacaoModal')">Cancelar</button>
                <button id="saveInstalacaoBtn" class="modal-btn btn-primary">
                    <span>Confirmar</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="js/ocorrenciaProcessamento.js"></script>
</body>

</html>