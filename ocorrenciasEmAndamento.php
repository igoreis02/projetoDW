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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <title>Ocorrências em Andamento</title>
    <style>
        /* Estilos Gerais */
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

        /* Card Principal */
        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1400px;
            /* Aumentado para comportar novos filtros */
            text-align: center;
            position: relative;
        }

        /* Título e Botões de Navegação */
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

        /* --- NOVOS ESTILOS - CONTROLES --- */
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

        .search-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        #searchInput {
            width: 450px;
            max-width: 500px;
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

        #clearFiltersBtn {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 1rem;
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
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        /* --- FIM NOVOS ESTILOS --- */


        /* Botões de Ação (Manutenção/Instalação) */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            /* Removido margin-bottom para alinhar com o novo layout */
        }

        .action-btn {
            padding: 12px 25px;
            font-size: 1.1em;
            font-weight: 600;
            color: var(--cor-principal);
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

        /* Filtros de Cidade */
        .filter-container {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.8rem;
            min-height: 38px;
            /* Para evitar saltos de layout */
        }

        .filter-btn {
            padding: 8px 18px;
            font-size: 0.9em;
            color: #4b5563;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background-color: #e5e7eb;
        }

        .filter-btn.active {
            background-color: #6366f1;
            color: white;
            border-color: #6366f1;
        }

        /* Container de Grupos de Cidade */
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
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            /* Responsividade dos cards */
            gap: 1.5rem;
        }

        /* Item de Ocorrência Individual */
        .ocorrencia-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 5px solid var(--cor-principal);
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s, transform 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .ocorrencia-item[data-type="instalação"] {
            border-left-color: #f97316;
        }

        .ocorrencia-item[data-type="semaforica"] {
            border-left-color: #8b5cf6;
        }

        .ocorrencia-item:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        /* Layout do Cabeçalho do Item */
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

        .ocorrencia-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .ocorrencia-pendente {
            background-color: #f0f9ff;
            color: #f59e0b;
        }

        /* Layout dos Detalhes do Item */
        .ocorrencia-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
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

        .detail-item strong::after {
            content: ": ";
        }

        .detail-item span {
            word-break: break-word;
        }

        .detail-item.stacked strong {
            display: block;
        }

        .status-tag {
            padding: 2px 8px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .status-em-andamento {
            background-color: #fffbeb;
            color: #f59e0b;
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

        /* Ações do Item (Botões Editar/Cancelar) */
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

        .concluir-btn {
            background-color: #22c55e;
            /* Verde */
            color: white;
        }

        .concluir-btn:hover {
            background-color: #16a34a;
        }

        .status-btn {
            background-color: #3b82f6;
            /* Azul */
            color: white;
        }

        .status-btn:hover {
            background-color: #2563eb;
        }

        .cancel-btn {
            background-color: #ef4444;
            /* Vermelho */
            color: white;
        }

        .cancel-btn:hover {
            background-color: #dc2626;
        }

        /* Botão Voltar */
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
            transition: background-color 0.3s ease;
        }

        .voltar-btn:hover {
            background-color: #09143fff;
        }

        .hidden {
            display: none !important;
        }

        /* Estilos para Modais */
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

        .form-group p {
            background-color: #f3f4f6;
            min-height: 44px;
        }

        .date-inputs {
            display: flex;
            gap: 1rem;
        }

        .date-inputs .form-group {
            flex: 1;
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
            transition: all 0.2s;
        }

        .choice-btn.selected {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
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
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .confirmation-modal .modal-body {
            text-align: center;
            font-size: 1.1em;
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


        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-left-color: #fff;
            animation: spin 1s ease infinite;
        }


        .modal-btn .spinner {
            width: 20px;
            height: 20px;
            border-width: 3px;
            border-left-color: #fff;
            margin-left: 8px;
            display: none;
            /* Escondido por padrão */
        }

        .modal-error-message {
            color: #ef4444;
            font-weight: 600;
            font-size: 0.9em;
            margin-top: 0.5rem;
            text-align: left;
        }

        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .input-group input[type="text"] {
            flex-grow: 1;
        }

        .input-group .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .lacre-buttons button {
            padding: 8px 16px;
        }

        .lacre-fields {
            padding-left: 1rem;
            border-left: 3px solid #e5e7eb;
        }

        .cidade-header-simplificado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* Remove a borda do h3 e a coloca no contêiner */
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-top: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .cidade-header-simplificado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* Remove a borda do h3 e a coloca no contêiner */
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
            margin-top: 1.8rem;
            margin-bottom: 0.5rem;
        }


        /* --- NOVOS ESTILOS - VISÃO SIMPLIFICADA --- */
        #simplifiedView {
            text-align: left;
            padding: 1rem 0.2rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        #simplifiedView h2 {
            font-size: 1.5em;
            color: black;
            margin-bottom: 1.5rem;
        }

        #simplifiedView h3.cidade-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            /* Espaço entre a seta e o nome da cidade */
            user-select: none;
            /* Impede que o texto seja selecionado ao clicar */
            color: black;
            font-size: 1.4em;
            border-bottom: none;
            margin: 0;
            padding: 0;
        }

        .arrow-toggle {
            font-size: 0.8em;
            transition: transform 0.2s ease-in-out;
        }

        .toggle-dias-btn {
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            background-color: #f9fafb;
            color: #4b5563;
            white-space: nowrap;
        }

        #simplifiedView ul {
            list-style-type: none;
            padding-left: 0;
        }

        #simplifiedView li {
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.1em;
            padding: 0.4rem 0.2rem;
            border-radius: 4px;
        }

        #simplifiedView li:nth-child(odd) {
            background-color: #f9fafb;
        }

        #simplifiedView li.prioridade-urgente {
            color: #ef4444;
            border-left: 3px solid #ef4444;
            padding-left: 10px;
        }

        #simplifiedView li.prioridade-padrao {
            color: #112058;
            border-left: 3px solid #112058;
            padding-left: 10px;
            margin-bottom: 0.5rem;
        }

        #simplifiedView li.prioridade-sem-urgencia {
            color: #6b7280;
            border-left: 3px solid #6b7280;
            padding-left: 10px;
        }

        .section-divider {
            border: 0;
            height: 1px;
            background-color: #e5e7eb;
            margin: 2rem 0;
        }

        .simplified-section-title {
            font-size: 1.1em;
            color: #4b5563;
            text-align: left;
            margin-top: 2.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #d1d5db;
            font-weight: 600;
        }

        .card-dias-simplificado {
            float: right;
            text-align: right;
            font-size: 0.9em;
            color: #6b7280;
        }

        .dias-simplificado {
            font-weight: bold;
        }

        .filter-container.inline-view {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
            min-height: auto;
        }


        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
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

        @media (max-width: 1200px) {
            .city-ocorrencias-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-controls-container {
                flex-direction: column;
                /* Organiza os itens em coluna */
                align-items: center;
                /* Centraliza tudo */
                gap: 1rem;
                /* Adiciona um espaço entre as linhas */
                height: auto;
                /* Remove a altura mínima para permitir que cresça */
                margin-bottom: 1rem;
            }

            /* Remove o posicionamento absoluto para que os botões entrem no fluxo normal */
            #btnSimplificado,
            .date-filter-container {
                position: static !important;
                /* !important garante a sobreposição */
                transform: none !important;
                /* Remove o transform de centralização vertical */
                width: 100%;
                justify-content: center;
            }

            /* Força os botões de ação a ficarem na mesma linha, mesmo em coluna */
            .action-buttons {
                order: 1;
                /* Ordem em que aparece: primeiro */
            }

            #btnSimplificado {
                order: 2;
                /* Segundo */
                width: auto;
                /* Largura automática para o botão */
            }

            .date-filter-container {
                order: 3;
                /* Terceiro */
                flex-wrap: wrap;
                /* Permite que os campos de data quebrem a linha se necessário */
            }

            .search-container {
                margin-bottom: 1rem;
                /* Reduz a margem inferior */
            }

            .search-wrapper {
                display: flex;
                flex-direction: column;
                /* Coloca o input e o botão de limpar em coluna */
                align-items: center;
                gap: 0.75rem;
                /* Espaço entre o input e o botão */
                width: 100%;
            }

            #clearFiltersBtn {
                width: 90%;
                /* Ocupa 90% da largura do container */
                max-width: 450px;
                /* Mas não passa de 450px */
            }

            #clearFiltersBtn {
                position: static !important;
                /* !important garante a sobreposição */
                transform: none !important;
                margin-left: 0;
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
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <div class="header-container">
            <a href="menu.php" class="back-btn-icon" title="Voltar ao Menu">&larr;</a>
            <h2>Ocorrências em Andamento</h2>
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
                <h3>Concluir Reparo</h3>
                <button class="modal-close" onclick="closeModal('concluirModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4 id="concluirModalEquipName" style="font-size: 1.2em; text-align: center;"></h4>
                <div class="form-group">
                    <label>Ocorrência</label>
                    <p id="concluirOcorrenciaText"></p>
                </div>
                <div class="form-group">
                    <label>Datas de Execução</label>
                    <div class="date-inputs">
                        <div class="form-group">
                            <label for="concluirInicioReparo">Início</label>
                            <input type="date" id="concluirInicioReparo">
                        </div>
                        <div class="form-group">
                            <label for="concluirFimReparo">Fim</label>
                            <input type="date" id="concluirFimReparo">
                        </div>
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
                        <label class="checkbox-label">
                            <input type="checkbox" id="nenhumMaterialCheckbox"> Nenhum
                        </label>
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
                    <label for="numeroLacre">Número do lacre:</label>
                    <input type="text" id="numeroLacre" placeholder="Digite o número do lacre">
                    <label for="infoRompimento">Qual lacre foi rompido?</label>
                    <input type="text" id="infoRompimento" placeholder="Ex: Metrológico, switch">
                </div>

                <div class="form-group">
                    <label for="reparoFinalizado">Descrição do Reparo Realizado</label>
                    <textarea id="reparoFinalizado" rows="3" placeholder="Descreva o que foi feito..."></textarea>
                    <div id="reparoErrorMessage" class="modal-error-message hidden"></div>
                </div>
            </div>
            <div class="modal-footer">
                <p id="conclusionSuccessMessage" class="hidden"
                    style="color: green; font-weight: bold; width: 100%; text-align: center;"></p>
                <button class="modal-btn btn-secondary" onclick="closeModal('concluirModal')">Cancelar</button>
                <button id="saveConclusionBtn" class="modal-btn btn-primary" onclick="saveConclusion()">
                    Concluir Reparo
                    <div id="conclusionSpinner" class="spinner"></div>
                </button>
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


    <script src="js/ocorrenciasEmAndamento.js"></script>
</body>

</html>