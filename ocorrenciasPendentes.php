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
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <style>
        /* SEU ESTILO ORIGINAL (INTACTO) */
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
            max-width: 1200px;
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

        .controls-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .main-controls-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            width: 100%;
            position: relative;
            min-height: 48px;
        }

        .date-filter-container {
            right: 0;
            top: 50%;
            position: absolute;
            display: flex;
            transform: translateY(-50%);
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
            font-family: 'Inter', sans-serif;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 0;
        }

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .search-wrapper {
            position: relative;
            /* Essencial para o posicionamento do botão */
            display: flex;
            /* Alinha o input e o botão na mesma linha */
            align-items: center;
        }

        #searchInput {
            width: 450px;
            max-width: 500px;
            /* Mantém o limite de largura que você já tinha */
            padding: 10px 15px;
            font-size: 1em;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        #searchInput:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border-left-color: #fff;
            animation: spin 1s ease infinite;
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

        .action-btn.active {
            background-color: #112058;
            color: white;
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

        .ocorrencias-container {
            width: 100%;
        }

        .city-group {
            margin-bottom: 2.5rem;
        }

        .city-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--cor-principal);
        }

        .city-group-title {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin: 0;
            padding-bottom: 0.5rem;
        }

        .atribuir-cidade-btn {
            background-color: #112058;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .city-ocorrencias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .ocorrencia-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            cursor: pointer;
        }

        .ocorrencia-item.selected {
            background-color: #eef2ff;
            border-color: #6366f1;
            transform: translateY(-2px);
        }

        .ocorrencia-item[data-type="instalação"] {
            border-left-color: #f97316;
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

        .ocorrencia-details {
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
            text-align: left;
        }

        .detail-item strong {
            font-weight: 600;
            color: #1f2937;
        }

        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .status-pendente {
            background-color: #eff6ff;
            color: #3b82f6;
        }

        .status-value.instalado {
            color: #16a34a;
            font-weight: 600;
        }

        .status-value.aguardando {
            color: #ef4444;
            font-weight: 600;
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
            transition: all 0.2s ease;
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

        .voltar-btn:hover {
            background-color: #0d1b2a;
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            text-align: left;
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
            color: #111827;
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
            color: #374151;
        }

        .form-group input,
        .form-group textarea,
        .form-group p {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1em;
            box-sizing: border-box;
            margin: 0;
        }

        .date-inputs {
            display: flex;
            gap: 1rem;
        }

        .choice-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .choice-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            background-color: #f9fafb;
        }

        .choice-btn.selected {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
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
        }

        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .modal-error-message {
            color: #ef4444;
            font-weight: bold;
            width: 100%;
            text-align: center;
            margin-bottom: 1rem;
        }

        .modal-btn .spinner {
            width: 20px;
            height: 20px;
            border-width: 3px;
            border-left-color: #fff;
            margin-left: 8px;
        }

        @media (max-width: 1200px) {
            .city-ocorrencias-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .city-ocorrencias-grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1.5rem;
            }

            .header-container h2 {
                font-size: 1.8em;
            }

            .action-buttons,
            .filter-container {
                flex-direction: column;
            }
        }

        /* <-- NOVOS ESTILOS ADICIONADOS SEM ALTERAR OS SEUS --> */
        #pageLoader {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 0;
            gap: 1.5rem;
        }

        .page-spinner {
            border: 8px solid rgba(0, 0, 0, 0.1);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border-left-color: #112058;
            animation: spin 1s ease infinite;
        }

        #pageLoader p {
            font-size: 1.5em;
            color: #374151;
            font-weight: 600;
        }

        #clearFiltersBtn {
            position: absolute;
            /* Tira o botão do fluxo normal do layout */
            left: 100%;
            /* Começa a se posicionar exatamente onde o input termina */
            top: 50%;
            /* Alinha o topo do botão com o meio do wrapper */
            transform: translateY(-50%);
            /* Ajuste fino para centralizar verticalmente perfeito */
            margin-left: 1rem;
            /* Cria o espaço entre o input e o botão */

            /* Estilos que você já tinha */
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

        #btnSimplificado {
            position: absolute;
            /* Posiciona o botão de forma absoluta */
            left: 0;
            /* Alinha no canto esquerdo */
            top: 50%;
            /* Alinha verticalmente com os outros botões */
            transform: translateY(-50%);
            background-color: #f9fafb;
            /* Cor de fundo diferente para destaque */
            border: 1px solid #d1d5db;
            color: #374151;
        }

        #btnSimplificado.active {
            background-color: #112058;
            color: white;
        }

        /* Estilos para a área do resumo */
        #simplifiedView {
            text-align: left;
            padding: 1rem 0;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        #simplifiedView h2 {
            font-size: 1.5em;
            color: black;
            margin-bottom: 1.5rem;
        }

        #simplifiedView h3 {
            font-size: 1.2em;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        #simplifiedView ul {
            list-style-type: none;
            padding-left: 0;
        }

        #simplifiedView li {
            font-family: 'Courier New', Courier, monospace;
            /* Fonte monoespaçada para melhor alinhamento */
            font-size: 1.1em;
            padding: 0.4rem 0.2rem;
            border-radius: 4px;
        }

        #simplifiedView li:nth-child(odd) {
            background-color: #f9fafb;
            /* Cor de fundo alternada para melhor leitura */
        }
    </style>
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
                    <button id="btnManutencoes" class="action-btn active" data-type="manutencao">Manutenções</button>
                    <button id="btnInstalacoes" class="action-btn" data-type="instalação">Instalações</button>
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
                    <input type="text" id="searchInput" placeholder="Pesquisar por nome, referência, ocorrência ou usuário...">
                    <button id="clearFiltersBtn">Limpar Filtros</button>
                </div>
            </div>
        </div>

        <div id="filterContainer" class="filter-container"></div>
        <div id="simplifiedView" class="hidden">
        </div>
        <div id="ocorrenciasContainer" class="ocorrencias-container">
            <div id="pageLoader">
                <div class="page-spinner"></div>
                <p>Carregando ocorrências...</p>
            </div>
        </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
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
                    <label>Selecione a data para execução</label>
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="assignInicioReparo">Início Reparo</label>
                            <input type="date" id="assignInicioReparo">
                        </div>
                        <div class="form-group">
                            <label for="assignFimReparo">Fim Reparo</label>
                            <input type="date" id="assignFimReparo">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Técnicos</label>
                    <div id="assignTecnicosContainer" class="choice-buttons"></div>
                </div>
                <div class="form-group">
                    <label>Veículos</label>
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
                    <button class="modal-btn btn-secondary" onclick="closeModal('editOcorrenciaModal')">Cancelar</button>
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
                <p id="confirmationMessage" class="hidden" style="margin-top: 1rem;"></p>
            </div>
            <div id="confirmationFooter" class="modal-footer">
                <div class="modal-footer-buttons">
                    <button class="modal-btn btn-secondary" onclick="closeModal('confirmationModal')">Não</button>
                    <button id="confirmActionButton" class="modal-btn btn-primary">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/ocorrenciasPendentes.js"></script>
</body>

</html>