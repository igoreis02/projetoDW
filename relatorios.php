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
    <title>Relatórios</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Eu copio o estilo base das suas outras páginas para manter a consistência */
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
        }

        .close-btn {
            right: 0;
        }

        .back-btn-icon {
            left: 0;
        }

        .page-button {
            padding: 20px 40px;
            font-size: 1.3em;
            color: white;
            background-color: #112058;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .page-button:hover {
            background-color: #09143f;
        }

        .voltar-btn {
            margin-top: 2rem;
        }

        /* [NOVO] Estilos para o meu novo formato de lista de relatório */
        .report-city-header {
            font-size: 1.8em;
            color: #374151;
            text-align: left;
            margin-top: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #112058;
        }

        .report-city-header:first-child {
            margin-top: 0;
        }

        .equipment-report-block {
            margin-bottom: 2.5rem;
        }

        .equipment-name {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }


        .maintenance-table th {
            border: 1px solid #b3b3b3;
            /* Borda para todas as células */
            padding: 8px 12px;
            text-align: left;
            vertical-align: middle;
            /* Alinho o conteúdo verticalmente */
        }

        .maintenance-table td {
            white-space: normal;
            /* Eu permito que o texto quebre a linha */
            word-wrap: break-word;
            /* Eu forço a quebra de palavras muito longas */
            vertical-align: top;
            /* Alinho o texto no topo da célula para melhor leitura */
        }


        .maintenance-table thead {
            background-color: #e9ecef;
            color: #343a40;
            font-weight: bold;
        }

        .maintenance-table thead th {
            text-align: center;
            /* Centralizo os cabeçalhos */
        }

        .maintenance-table th:nth-child(1), .maintenance-table td:nth-child(1) { width: 5%; }  /* Item */
.maintenance-table th:nth-child(2), .maintenance-table td:nth-child(2) { width: 12%; } /* Data Início */
.maintenance-table th:nth-child(3), .maintenance-table td:nth-child(3) { width: 30%; } /* Descrição Problema */
.maintenance-table th:nth-child(4), .maintenance-table td:nth-child(4) { width: 12%; } /* Data Fim */
.maintenance-table th:nth-child(5), .maintenance-table td:nth-child(5) { width: 30%; } /* Descrição Reparo */
.maintenance-table th:nth-child(6), .maintenance-table td:nth-child(6) { width: 5%; }  /* Dias */
.maintenance-table th:nth-child(7), .maintenance-table td:nth-child(7) { width: 6%; }  /* Técnico */

        .report-item {
            border: 1px solid #e5e7eb;
            border-left: 5px solid #6366f1;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .report-item p {
            margin: 0 0 0.8rem 0;
            font-size: 1em;
            line-height: 1.5;
        }

        .report-item p strong {
            font-weight: 600;
            color: #1f2937;
            display: block;
            /* Faz o título (ex: "Equipamento:") ficar em uma linha */
        }

        /* Estilos para o Modal de Filtros (reaproveitando o seu estilo) */
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
            max-width: 500px;
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
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            text-align: left;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1em;
            box-sizing: border-box;
        }

        .date-inputs {
            display: flex;
            gap: 1rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
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

        .hidden {
            display: none !important;
        }

        /* Estilos para a área de resultados do relatório */
        #reportResultContainer {
            margin-top: 2rem;
            text-align: left;
            width: 100%;
        }

        #reportHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        #reportHeader h3 {
            margin: 0;
            font-size: 1.5em;
        }

        #downloadExcelBtn {
            font-size: 1em;
            padding: 10px 20px;
        }


        #reportTable {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Relatórios</h2>
            <a href="menu.php" class="close-btn" title="Voltar ao Menu">&times;</a>
        </div>

        <button id="openReportModalBtn" class="page-button">
            <i class="fas fa-file-download"></i>
            Gerar Relatório
        </button>

        <div id="reportResultContainer" class="hidden">
            <div id="reportHeader">
                <h3>Resultado do Relatório</h3>
                <button id="downloadExcelBtn" class="page-button" style="background-color: #16a34a;">
                    <i class="fas fa-file-excel"></i>
                    Baixar Excel
                </button>
            </div>
            <div id="reportTable">
            </div>
        </div>

        <a href="menu.php" class="voltar-btn page-button">Voltar ao Menu</a>
    </div>

    <div id="reportFiltersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Filtros do Relatório</h3>
                <button class="modal-close" onclick="closeModal('reportFiltersModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="citySelect">Cidade</label>
                    <select id="citySelect">
                    </select>
                </div>

                <div class="date-inputs">
                    <div class="form-group">
                        <label for="startDate">Data de Início</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="form-group">
                        <label for="endDate">Data de Fim</label>
                        <input type="date" id="endDate">
                    </div>
                </div>

                <div class="form-group">
                    <label for="reportTypeSelect">Tipo de Relatório</label>
                    <select id="reportTypeSelect">
                        <option value="matriz_tecnica">Matriz Técnica</option>
                        <option value="rel_processamento">Rel. Processamento</option>
                        <option value="rel_provedor">Rel. Provedor</option>
                        <option value="controle_ocorrencia">Controle de Ocorrência</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="statusSelect">Status</label>
                    <select id="statusSelect">
                        <option value="todos">Todos</option>
                        <option value="concluido">Concluído</option>
                        <option value="pendente">Pendente</option>
                        <option value="em andamento">Em Andamento</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>

                <p id="modalErrorMessage" style="color: red; text-align: center;" class="hidden"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-secondary" onclick="closeModal('reportFiltersModal')">Cancelar</button>
                <button id="generateReportBtn" class="modal-btn btn-primary">Gerar</button>
            </div>
        </div>
    </div>

    <script src="js/relatorios.js" defer></script>
</body>

</html>