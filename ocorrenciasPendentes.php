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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .ocorrencia-item[data-type="semaforica"] {
            border-left-color: #8b5cf6;
            /* Cor roxa para diferenciar */
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

        .choice-buttons-ocorrencias-container {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            max-height: 300px;
            overflow-y: auto;
            align-items: flex-start;
            padding-top: 1rem;

        }

        .choice-btn {
            color: #112058;
            font-weight: bold;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            background-color: #f9fafb;
        }

        .choice-btn.selected {
            background-color: #112058;
            color: white;
            border-color: #112058;
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

        .modal-error-message.success {
            color: #155724;
            /* Letras verdes escuras */
            background-color: #d4edda;
            font-weight: bold;
            width: 95%;
            text-align: center;
            margin-bottom: 1rem;
            /* Fundo verde claro */
            border: 1px solid #c3e6cb;
            /* Borda verde */
            padding: 0.75rem;
            /* Adicionando um espaçamento interno para destaque */
            border-radius: 8px;
        }

        .modal-error-message {
            color: #ef4444;
            background-color: #f8d7da;
            font-weight: bold;
            width: 95%;
            text-align: center;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
            /* Borda vermelha */
            padding: 0.75rem;
            /* Adicionando um espaçamento interno para destaque */
            border-radius: 8px;
        }

        .modal-btn .spinner {
            width: 20px;
            height: 20px;
            border-width: 3px;
            border-left-color: #fff;
            margin-left: 8px;
        }

        .modal-info-message {
            color: #112058;
            background-color: #efeeeeff;
            font-weight: bold;
            width: 95%;
            text-align: center;
            border: 1px solid #efeeeeff;
            /* Borda azul */
            padding: 0.75rem;
            /* Adicionando um espaçamento interno para destaque */
            border-radius: 8px;
        }

        #btnVoltarAoTopo {
            display: none;
            /* Eu começo com ele escondido */
            position: fixed;
            /* Fixo ele na tela, para que role junto com a página */
            bottom: 20px;
            /* Distância da parte de baixo da tela */
            right: 30px;
            /* Distância da parte direita da tela */
            z-index: 99;
            /* Garanto que ele fique acima de outros elementos */
            border: none;
            /* Tiro a borda padrão */
            outline: none;
            /* Tiro o contorno ao clicar */
            background-color: #213fadff;
            /* Uso a cor principal do meu projeto */
            color: white;
            /* Cor do ícone */
            cursor: pointer;
            /* Mudo o cursor para indicar que é clicável */
            padding: 15px;
            /* Espaçamento interno */
            border-radius: 50%;
            /* Deixo ele redondo */
            font-size: 18px;
            /* Tamanho do ícone */
            width: 50px;
            /* Largura fixa */
            height: 50px;
            /* Altura fixa */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            /* Adiciono uma sombra suave */
            transition: background-color 0.3s, opacity 0.5s;
            /* Adiciono transições suaves */
        }

        #btnVoltarAoTopo:hover {
            background-color: #12287eff;
            /* Escureço a cor quando passo o mouse */

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
        }

        .arrow-toggle {
            font-size: 0.8em;
            transition: transform 0.2s ease-in-out;
        }

        #simplifiedView ul {
            list-style-type: none;
            padding-left: 0;
        }

        #simplifiedView li {
            font-family: 'Courier New', Courier, monospace;
            /* Fonte monoespaçada para melhor alinhamento */
            font-size: 1.1em;
            padding: 0.8rem 0.2rem;
            border-radius: 4px;
        }

        #simplifiedView li:nth-child(odd) {
            background-color: #f9fafb;
            /* Cor de fundo alternada para melhor leitura */
        }

        /* [NOVO] Estilo para a lista de ocorrências concatenadas */
        .ocorrencia-list {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
            font-size: 0.95em;
        }

        .ocorrencia-list li {
            padding: 4px 0;
            border-bottom: 1px solid #f3f4f6;

        }

        .ocorrencia-list li:last-child {
            border-bottom: none;
        }

        #simplifiedView li.prioridade-urgente {
            color: #ef4444;
            /* Vermelho */
            border-left: 3px solid #ef4444;
            padding-left: 10px;
            margin-bottom: 0.5rem
        }

        #simplifiedView li.prioridade-padrao {
            color: #112058;
            /* Azul */
            border-left: 3px solid #112058;
            padding-left: 10px;
            margin-bottom: 0.5rem
        }

        #simplifiedView li.prioridade-sem-urgencia {
            color: #6b7280;
            /* Cinza Escuro */
            border-left: 3px solid #6b7280;
            padding-left: 10px;
            margin-bottom: 0.5rem
        }

        .section-divider {
            border: 0;
            height: 1px;
            background-color: #e5e7eb;
            /* Cinza claro, combinando com o design */
            margin: 2rem 0;
            /* Espaçamento vertical para a linha respirar */
        }

        .simplified-section-title {
            font-size: 1.1em;
            color: #4b5563;
            /* Cinza escuro para o texto */
            text-align: left;
            margin-top: 2.5rem;
            /* << A distância superior que você pediu */
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
                    <input type="text" id="searchInput"
                        placeholder="Pesquisar por nome, referência, ocorrência ou usuário...">
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
                        <span>Urgente (1)</span>
                        <div class="spinner hidden"></div>
                    </button>
                    <button class="modal-btn" style="background-color: #112058; color: white;"
                        onclick="savePriority(2)">
                        <span>Padrão (2)</span>
                        <div class="spinner hidden"></div>
                    </button>
                    <button class="modal-btn" style="background-color: #6b7280; color: white;"
                        onclick="savePriority(3)">
                        <span>Sem Urgência (3)</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/ocorrenciasPendentes.js"></script>

    <button id="btnVoltarAoTopo" title="Voltar ao topo">
        <i class="fas fa-arrow-up"></i> </button>
</body>

</html>