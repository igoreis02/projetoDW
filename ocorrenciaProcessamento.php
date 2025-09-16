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
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos (sem alterações) */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1400px;
            text-align: center;
            position: relative;
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

        .main-controls-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            position: relative;
            min-height: 48px;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            color: black;
            background-color: #eef2ff;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background-color: #e0e7ff;
        }

        .action-btn.active {
            background-color: #112058;
            color: white;
        }

        .date-filter-container {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .date-filter-container label {
            font-weight: 600;
            color: #374151;
        }

        .date-filter-container input[type="date"] {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        #searchInput {
            width: 100%;
            max-width: 500px;
            padding: 10px 15px;
            font-size: 1em;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        #clearFiltersBtn {
            padding: 10px 20px;
            font-size: 0.9em;
            font-weight: 600;
            color: #374151;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        #clearFiltersBtn:hover {
            background-color: #e5e7eb;
        }

        .search-spacer {
            visibility: hidden;
        }

        .filter-container {
            margin-bottom: 1.5rem;
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

        .filter-btn.active.validacao {
            background-color: #f97316;
            color: white;
            border-color: #f97316;
        }

        .filter-btn.active.todos {
            background-color: #6b7280;
            color: white;
            border-color: #6b7280;
        }

        .filter-btn.active.pendente {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .filter-btn.active.concluido {
            background-color: #22c55e;
            color: white;
            border-color: #22c55e;
        }

        .filter-btn.active.cancelado {
            background-color: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .filter-btn.active.city {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        .ocorrencias-container {
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

        .city-ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .ocorrencia-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            height: 100%;
            box-sizing: border-box;
        }

        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            flex-grow: 1;
            width: 100%;
        }

        .ocorrencia-item.status-validacao {
            border-left: 5px solid #f97316;
        }

        .ocorrencia-item.status-pendente {
            border-left: 5px solid #3b82f6;
        }

        .ocorrencia-item.status-concluido {
            border-left: 5px solid #22c55e;
        }

        .ocorrencia-item.status-cancelado {
            border-left: 5px solid #ef4444;
        }

        .ocorrencia-header {
            text-align: left;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #d1d5db;
            width: 100%;
        }

        .ocorrencia-header h3 {
            font-size: 1.3em;
            color: #111827;
            margin: 0;
        }

        .detail-item {
            font-size: 0.95em;
            color: #374151;
            line-height: 1.5;
            text-align: left;
        }

        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }

        .detail-item strong::after {
            content: ": ";
        }

        .detail-item.reparo-info span {
            color: #15803d;
            font-style: italic;
        }


        .status-tag {
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: capitalize;
        }

        .status-tag.validacao {
            background-color: #ffedd5;
            color: #f97316;
        }

        .status-tag.pendente {
            background-color: #eff6ff;
            color: #3b82f6;
        }

        .status-tag.concluido {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-tag.cancelado {
            background-color: #fee2e2;
            color: #b91c1c;
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
            padding: 8px 16px;
            font-size: 0.9em;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .validar-btn {
            background-color: #f97316;
            color: white;
        }

        .concluir-btn {
            background-color: #22c55e;
            color: white;
        }

        .edit-btn {
            background-color: #3b82f6;
            color: white;
        }

        .cancel-btn {
            background-color: #ef4444;
            color: white;
        }

        .voltar-btn {
            display: inline-block;
            width: auto;
            min-width: 200px;
            padding: 15px 30px;
            margin-top: 3rem;
            text-align: center;
            background-color: #112058;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
        }

        .hidden {
            display: none !important;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
        }

        .modal.is-active {
            display: flex;
        }

        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 600px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5em;
        }

        .modal-close {
            font-size: 2rem;
            font-weight: bold;
            color: #9ca3af;
            cursor: pointer;
            background: none;
            border: none;
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group textarea,
        .form-group p {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1em;
            box-sizing: border-box;
            min-height: 100px;
            margin: 0;
        }

        .form-group p {
            background-color: #f3f4f6;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.875em;
            display: none;
        }

        .modal-footer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .modal-footer-buttons {
            display: flex;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background-color: #22c55e;
            color: white;
        }

        .btn-primary.edit {
            background-color: #3b82f6;
        }

        .btn-primary.cancel {
            background-color: #ef4444;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .message {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            width: 100%;
        }

        .message.success {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .message.error {
            background-color: #fee2e2;
            color: #ef4444;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
            display: none;
            margin-left: 8px;
        }

        .spinner.is-active {
            display: inline-block;
        }

        #btnVoltarAoTopo {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 30px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #213fadff;
            color: white;
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            width: 50px;
            height: 50px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s, opacity 0.5s;
        }

        #btnVoltarAoTopo:hover {
            background-color: #12287eff;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #pageLoader.main-loading-state {
            display: none;
            /* Começa escondido, o JS controla a exibição */
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
            color: #555;
            padding: 40px 0;
        }

        #pageLoader .main-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #112058;
            /* Cor principal do tema */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        .ocorrencia-list-container ol {
            color: black;
            /* Garante que os índices da lista sejam pretos */
            padding-left: 20px;
            /* Alinha a lista corretamente */
        }

        .ocorrencia-list-container.collapsed ol li:nth-child(n+3) {
            display: none;
            /* Esconde os itens da lista a partir do terceiro */
        }

        .toggle-ocorrencias-btn {
            display: block;
            margin: 1rem auto 0;
            /* Centraliza o botão */
            padding: 8px 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background-color: #dcfce7;
            /* Tom de verde claro */
            color: #166534;
            /* Tom de verde escuro */
            transition: background-color 0.2s;
        }

        .toggle-ocorrencias-btn:hover {
            background-color: #bbf7d0;
        }

        /* Estilo para os botões de seleção no novo modal de edição */
        .edit-selection-container {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            max-height: 40vh;
            overflow-y: auto;
        }

        .edit-selection-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1em;
            text-align: left;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .edit-selection-btn:hover {
            background-color: #e5e7eb;
            border-color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências de Processamento</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

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

    <script src="js/ocorrenciaProcessamento.js"></script>
</body>

</html>