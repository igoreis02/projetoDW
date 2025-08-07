<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para index.html se user_id não estiver na sessão
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Opcional: Redirecionar se o tipo de usuário não tiver permissão para esta página
// Por exemplo, apenas administradores e provedores podem atribuir manutenções
// if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] !== 'administrador' && $_SESSION['tipo_usuario'] !== 'provedor')) {
//     header('Location: menu.php');
//     exit();
// }

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Atribuir Técnico</title>
    <style>
        /* Estilos do card e layout geral, consistentes com as outras páginas */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            text-align: center;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card:before {
            content: none; /* Remove o pseudo-elemento ::before do card */
        }

        .logoMenu {
            width: 150px;
            margin-bottom: 20px;
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        h2 {
            font-size: 2em;
            color: var(--cor-principal);
            margin-bottom: 30px;
            margin-top: 40px;
        }

        /* Contêiner para os botões iniciais da página */
        .initial-buttons-section {
            display: flex; /* Visível por padrão */
            flex-direction: column; /* Empilha os botões verticalmente */
            gap: 20px; /* Espaçamento entre os botões */
            width: 100%;
            padding: 20px 0;
        }

        /* Estilo para os botões da página */
        .page-button {
            padding: 25px;
            font-size: 1.3em;
            color: white;
            background-color: var(--cor-principal);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        /* Estilos para a seção de seleção de cidades */
        .city-selection-section {
            display: none; /* Escondido por padrão nesta página */
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            width: 100%;
        }

        .city-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 10px; /* Espaçamento entre os botões de cidade */
            max-height: 300px; /* Altura máxima para rolagem */
            overflow-y: auto; /* Adiciona rolagem se muitas cidades */
            padding-right: 10px; /* Espaço para a barra de rolagem */
        }

        .city-button {
            padding: 12px 20px;
            font-size: 1.1em;
            color: white;
            background-color: var(--cor-principal);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .city-button:hover {
            background-color: var(--cor-secundaria);
        }

        /* Mensagens de erro/carregamento */
        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .message.error {
            background-color: #fee2e2;
            color: #ef4444;
            border: 1px solid #fca5a5;
        }

        .message.success {
            background-color: #dcfce7;
            color: #22c55e;
            border: 1px solid #86efac;
        }

        .hidden {
            display: none !important;
        }

        /* Estilo para o botão "Voltar" (geral) */
        .voltar-btn {
            display: block;
            width: 50%;
            padding: 15px;
            margin-top: 30px;
            text-align: center;
            background-color: var(--botao-voltar);
            color: var(--cor-letra-botaoVoltar);
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .voltar-btn:hover {
            background-color: var(--botao-voltar-hover);
        }

        /* Estilo para o botão de voltar dentro das seções (seta) */
        .back-button-section {
            color: #aaa;
            position: absolute;
            top: 10px;
            left: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        .back-button-section:hover,
        .back-button-section:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Estilos para o footer */
        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        /* Estilos para a nova seção de itens pendentes (agora no modal) */
        .pending-items-list {
            max-height: 400px; /* Altura máxima para rolagem */
            overflow-y: auto; /* Adiciona rolagem se muitos itens */
            padding-right: 10px; /* Espaço para a barra de rolagem */
        }

        .pending-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer; /* Indica que é clicável */
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            text-align: left; /* Alinha o texto das informações à esquerda */
        }

        .pending-item:hover {
            background-color: #e9e9e9;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .pending-item p {
            margin-bottom: 0.5rem;
            font-size: 0.95em;
            color: #333;
        }

        /* Estilo para todas as tags strong dentro de .pending-item */
        .pending-item strong {
            color: var(--cor-principal); /* Cor padrão para strong */
        }

        /* Estilo específico para os labels de destaque */
        .pending-item strong.highlight-label {
            color: var(--cor-terciaria); /* Usa a nova cor para os labels específicos */
        }


        /* Estilos para o Modal de Atribuição e Modal de Técnicos */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }
        .modal.is-active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 700px; /* Aumentado para melhor visualização das ocorrências */
            position: relative;
            text-align: center;
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 1rem;
            color: var(--cor-principal);
        }

        .modal-content .close-button {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .modal-content .close-button:hover {
            color: black;
        }

        /* Estilos para a caixa de detalhes da manutenção/instalação no modal de técnicos */
        .selected-item-details-box {
            background-color: #e6f7ff; /* Um azul claro para a caixa */
            border: 1px solid #91d5ff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem; /* Espaçamento abaixo da caixa */
            text-align: left;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .selected-item-details-box p {
            margin-bottom: 0.3rem;
        }
        .selected-item-details-box strong {
            color: var(--cor-principal);
        }
        .selected-item-details-box strong.highlight-label {
            color: var(--cor-terciaria);
        }


        /* Estilos para os botões de técnico no modal */
        .technician-buttons-container {
            display: flex;
            flex-wrap: wrap; /* Permite que os botões quebrem para a próxima linha */
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
            max-height: 250px; /* Altura máxima para rolagem */
            overflow-y: auto;
            padding-right: 10px;
        }

        .technician-button {
            padding: 10px 15px;
            font-size: 1em;
            color: white;
            background-color: #6c757d; /* Cinza padrão para não selecionado */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            flex: 1 1 calc(50% - 10px); /* Dois botões por linha com gap */
            max-width: calc(50% - 10px); /* Garante que não exceda 50% menos o gap */
            text-align: center;
            box-sizing: border-box; /* Inclui padding e borda no cálculo da largura */
        }

        .technician-button.selected {
            background-color: var(--cor-principal); /* Cor principal quando selecionado */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .technician-button:hover:not(.selected) {
            background-color: #5a6268; /* Cinza mais escuro no hover */
        }

        .assign-button {
            padding: 12px 25px;
            font-size: 1.1em;
            background-color: var(--cor-principal);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
            width: 100%;
            display: flex; /* Para alinhar spinner e texto */
            align-items: center;
            justify-content: center;
        }

        .assign-button:hover {
            background-color: var(--cor-secundaria);
        }

        /* Estilos para o spinner */
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }


        /* Media Queries para responsividade */
        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
                width: 95%;
            }
            .logoMenu {
                width: 120px;
                top: -50px;
            }
            h2 {
                font-size: 1.8em;
                margin-top: 30px;
            }
            .page-button {
                padding: 20px;
                font-size: 1.1em;
            }
            .voltar-btn {
                width: 70%;
            }
            .pending-item {
                padding: 0.8rem;
                font-size: 0.9em;
            }
            .modal-content {
                padding: 1.5rem;
                max-width: 95%;
            }
            .technician-button {
                min-width: 100px;
                padding: 8px 12px;
                font-size: 0.9em;
                flex: 1 1 calc(50% - 10px); /* Mantém dois por linha */
                max-width: calc(50% - 10px);
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .card {
                width: 100%;
                height: auto;
                padding: 10px;
                margin: auto;
            }
            .technician-button {
                flex: 1 1 100%; /* Um botão por linha em telas muito pequenas */
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <img class="logoMenu" src="imagens/logo.png" alt="Logo" />
        <h2 id="mainTitle">Atribuir Manutenção/Instalação ao Técnico</h2>

        <div id="initialButtonsSection" class="initial-buttons-section">
            <button id="manutencoesBtn" class="page-button">Manutenções</button>
            <button id="instalacoesBtn" class="page-button">Instalações</button>
        </div>

        <div id="citySelectionSection" class="city-selection-section">
            <button class="back-button-section" onclick="goBackToInitialSelection()">&larr;</button>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Selecione a Cidade</h3>
            <p class="mb-4 text-gray-700">Selecione a cidade para atribuir:</p>
            <div id="cityButtonsContainer" class="city-buttons-container">
                <p id="loadingCitiesMessage">Carregando cidades...</p>
            </div>
            <p id="cityErrorMessage" class="message error hidden"></p>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <!-- Modal para Itens Pendentes de Atribuição -->
    <div id="pendingItemsModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closePendingItemsModal()">&times;</span>
            <h3 id="pendingItemsModalTitle" class="text-2xl font-bold mb-4 text-gray-800"></h3>
            <p id="pendingItemsModalDescription" class="mb-4 text-gray-700"></p>
            <div id="pendingItemsList" class="pending-items-list">
                <p id="loadingPendingItemsMessage">Carregando itens pendentes...</p>
                <p id="pendingItemsErrorMessage" class="message error hidden"></p>
            </div>
        </div>
    </div>

    <!-- Novo Modal para Seleção de Técnicos -->
    <div id="assignTechnicianModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAssignTechnicianModal()">&times;</span>
            <h3 id="assignTechnicianModalTitle" class="text-2xl font-bold mb-4 text-gray-800">Atribuir Técnicos</h3>
            <p class="mb-4 text-gray-700">Detalhes da Ocorrência:</p>
            <!-- Nova div para exibir os detalhes da manutenção/instalação selecionada -->
            <div id="selectedItemDetailsBox" class="selected-item-details-box">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <p class="mb-4 text-gray-700">Selecione o(s) técnico(s) para esta ocorrência:</p>
            <div id="technicianButtonsContainer" class="technician-buttons-container">
                <p id="loadingTechniciansMessage">Carregando técnicos...</p>
                <p id="techniciansErrorMessage" class="message error hidden"></p>
            </div>
            <button id="assignButton" class="assign-button">
                Atribuir
                <span id="assignSpinner" class="loading-spinner hidden"></span>
            </button>
            <p id="assignMessage" class="message hidden"></p>
        </div>
    </div>

    <script>
        // Referências aos elementos do DOM
        const mainTitle = document.getElementById('mainTitle');
        const initialButtonsSection = document.getElementById('initialButtonsSection');
        const manutencoesBtn = document.getElementById('manutencoesBtn');
        const instalacoesBtn = document.getElementById('instalacoesBtn');

        const citySelectionSection = document.getElementById('citySelectionSection');
        const cityButtonsContainer = document.getElementById('cityButtonsContainer');
        const loadingCitiesMessage = document.getElementById('loadingCitiesMessage');
        const cityErrorMessage = document.getElementById('cityErrorMessage');

        const pendingItemsModal = document.getElementById('pendingItemsModal');
        const pendingItemsModalTitle = document.getElementById('pendingItemsModalTitle');
        const pendingItemsModalDescription = document.getElementById('pendingItemsModalDescription');
        const pendingItemsList = document.getElementById('pendingItemsList');
        const loadingPendingItemsMessage = document.getElementById('loadingPendingItemsMessage');
        const pendingItemsErrorMessage = document.getElementById('pendingItemsErrorMessage');

        const assignTechnicianModal = document.getElementById('assignTechnicianModal');
        const selectedItemDetailsBox = document.getElementById('selectedItemDetailsBox'); // Nova referência
        const technicianButtonsContainer = document.getElementById('technicianButtonsContainer');
        const loadingTechniciansMessage = document.getElementById('loadingTechniciansMessage');
        const techniciansErrorMessage = document.getElementById('techniciansErrorMessage');
        const assignButton = document.getElementById('assignButton');
        const assignSpinner = document.getElementById('assignSpinner'); // Referência ao spinner
        const assignMessage = document.getElementById('assignMessage');


        let currentFlowType = ''; // 'maintenance' ou 'installation'
        let selectedCityId = null;
        let selectedCityName = '';
        let currentManutencaoId = null; // ID da manutenção/instalação selecionada para atribuição
        let selectedTechnicianIds = []; // Array para armazenar IDs dos técnicos selecionados

        // Funções de utilidade
        function showMessage(element, msg, type = '') {
            element.textContent = msg;
            element.className = `message ${type}`;
            element.classList.remove('hidden');
        }

        function hideMessage(element) {
            element.classList.add('hidden');
            element.textContent = '';
        }

        // Função para mostrar/esconder spinner e desabilitar/habilitar botão
        function toggleSpinner(button, spinner, show) {
            if (show) {
                spinner.classList.remove('hidden');
                button.disabled = true;
            } else {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        }

        // Função para carregar e exibir os botões das cidades
        async function loadCities() {
            cityButtonsContainer.innerHTML = ''; // Limpa botões anteriores
            loadingCitiesMessage.classList.remove('hidden');
            hideMessage(cityErrorMessage);

            console.log('Carregando cidades para atribuição...');

            try {
                const response = await fetch('get_cidades.php'); // Reutiliza o script existente
                const data = await response.json();

                console.log('Resposta de get_cidades.php:', data);

                loadingCitiesMessage.classList.add('hidden');

                if (data.success && data.cidades.length > 0) {
                    data.cidades.forEach(city => {
                        const button = document.createElement('button');
                        button.classList.add('city-button');
                        button.textContent = city.nome;
                        button.dataset.cityId = city.id_cidade;
                        button.dataset.cityName = city.nome;
                        button.addEventListener('click', () => openPendingItemsModal(city.id_cidade, city.nome, currentFlowType));
                        cityButtonsContainer.appendChild(button);
                    });
                } else {
                    cityErrorMessage.textContent = data.message || 'Nenhuma cidade encontrada.';
                    cityErrorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar cidades:', error);
                loadingCitiesMessage.classList.add('hidden');
                cityErrorMessage.textContent = 'Erro ao carregar cidades. Tente novamente.';
                cityErrorMessage.classList.remove('hidden');
            }
        }

        // Função para calcular o tempo em andamento
        function calculateTimeInProgress(startDateString) {
            if (!startDateString) return 'N/A';

            const startDate = new Date(startDateString);
            const now = new Date();

            const diffMs = now - startDate; // Diferença em milissegundos

            const diffSeconds = Math.floor(diffMs / 1000);
            const diffMinutes = Math.floor(diffSeconds / 60);
            const diffHours = Math.floor(diffMinutes / 60);
            const diffDays = Math.floor(diffHours / 24);

            let result = [];
            if (diffDays > 0) {
                result.push(`${diffDays} dia(s)`);
            }
            const remainingHours = diffHours % 24;
            if (remainingHours > 0) {
                result.push(`${remainingHours} hora(s)`);
            }
            const remainingMinutes = diffMinutes % 60;
            if (remainingMinutes > 0 && diffDays === 0) { // Mostra minutos se for menos de um dia
                result.push(`${remainingMinutes} minuto(s)`);
            } else if (diffMinutes === 0 && diffDays === 0 && remainingHours === 0) {
                result.push('poucos segundos');
            }

            return result.length > 0 ? result.join(', ') : 'N/A';
        }

        // Função para abrir o modal de itens pendentes e carregar os dados
        async function openPendingItemsModal(cityId, cityName, flowType) {
            selectedCityId = cityId; // Armazena para uso futuro se necessário
            selectedCityName = cityName; // Armazena para uso futuro se necessário
            currentFlowType = flowType; // Garante que o flowType está atualizado

            pendingItemsModal.classList.add('is-active');
            citySelectionSection.style.display = 'none'; // Esconde a seção de cidades

            // Atualiza o título e a descrição do modal
            if (flowType === 'maintenance') {
                pendingItemsModalTitle.textContent = `Manutenções Pendentes: ${cityName}`;
                pendingItemsModalDescription.textContent = 'Clique na manutenção para atribuir um técnico:';
            } else if (flowType === 'installation') {
                pendingItemsModalTitle.textContent = `Instalações Pendentes: ${cityName}`;
                pendingItemsModalDescription.textContent = 'Clique na instalação para atribuir um técnico:';
            }

            pendingItemsList.innerHTML = ''; // Limpa conteúdo anterior
            loadingPendingItemsMessage.classList.remove('hidden');
            hideMessage(pendingItemsErrorMessage);

            console.log(`Carregando itens pendentes para cidade ${cityName} (ID: ${cityId}) e fluxo ${flowType}...`);

            try {
                const url = `get_atribuicoes_pendentes.php?city_id=${cityId}&flow_type=${flowType}`;
                const response = await fetch(url);
                const data = await response.json();

                console.log('Resposta de get_atribuicoes_pendentes.php:', data);

                loadingPendingItemsMessage.classList.add('hidden');

                if (data.success && data.items.length > 0) {
                    data.items.forEach(item => {
                        const itemDiv = document.createElement('div');
                        itemDiv.classList.add('pending-item');
                        itemDiv.dataset.idManutencao = item.id_manutencao; // Armazena o ID da manutenção

                        const solicitacaoDate = new Date(item.inicio_reparo);
                        const formattedDate = solicitacaoDate.toLocaleDateString('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const tempoEmAndamento = calculateTimeInProgress(item.inicio_reparo);

                        // Ordem dos dados alterada e aplicação da classe highlight-label
                        itemDiv.innerHTML = `
                            <p><strong class="highlight-label">Nome do Equipamento:</strong> ${item.nome_equip}</p>
                            <p><strong class="highlight-label">Ocorrência do Reparo:</strong> ${item.ocorrencia_reparo || 'N/A'}</p>
                            <p><strong>Referência do Equipamento:</strong> ${item.referencia_equip}</p>
                            <p><strong>Data da Solicitação:</strong> ${formattedDate}</p>
                            <p><strong>Tempo em Andamento:</strong> ${tempoEmAndamento}</p>
                        `;
                        // Adicionar um event listener para cada item para abrir o modal de seleção de técnicos
                        itemDiv.addEventListener('click', () => {
                            currentManutencaoId = item.id_manutencao; // Define a manutenção atual
                            openAssignTechnicianModal(item); // Passa o item completo para o novo modal
                        });
                        pendingItemsList.appendChild(itemDiv);
                    });
                } else {
                    // MODIFICAÇÃO AQUI: Mensagem específica baseada no tipo de fluxo
                    if (currentFlowType === 'maintenance') {
                        pendingItemsErrorMessage.textContent = 'Sem manutenções no momento.';
                    } else if (currentFlowType === 'installation') {
                        pendingItemsErrorMessage.textContent = 'Sem instalações no momento.';
                    } else {
                        pendingItemsErrorMessage.textContent = data.message || 'Nenhum item pendente encontrado para esta cidade e tipo de fluxo.';
                    }
                    pendingItemsErrorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar itens pendentes:', error);
                loadingPendingItemsMessage.classList.add('hidden');
                pendingItemsErrorMessage.textContent = 'Erro ao carregar itens pendentes. Tente novamente.';
                pendingItemsErrorMessage.classList.remove('hidden');
            }
        }

        // Função para fechar o modal de itens pendentes
        function closePendingItemsModal() {
            pendingItemsModal.classList.remove('is-active');
            // Ao fechar o modal, volta para a seleção de cidades
            citySelectionSection.style.display = 'flex';
        }

        // Função para carregar e exibir os botões dos técnicos
        async function loadTechnicians() {
            technicianButtonsContainer.innerHTML = ''; // Limpa botões anteriores
            loadingTechniciansMessage.classList.remove('hidden');
            hideMessage(techniciansErrorMessage);
            selectedTechnicianIds = []; // Reseta a seleção de técnicos

            console.log('Carregando técnicos...');

            try {
                const response = await fetch('get_tecnicos.php');
                const data = await response.json();

                console.log('Resposta de get_tecnicos.php:', data);

                loadingTechniciansMessage.classList.add('hidden');

                if (data.success && data.tecnicos.length > 0) {
                    data.tecnicos.forEach(technician => {
                        const button = document.createElement('button');
                        button.classList.add('technician-button');
                        button.textContent = technician.nome;
                        button.dataset.idUsuario = technician.id_usuario;
                        button.addEventListener('click', () => toggleTechnicianSelection(button, technician.id_usuario));
                        technicianButtonsContainer.appendChild(button);
                    });
                } else {
                    techniciansErrorMessage.textContent = data.message || 'Nenhum técnico encontrado.';
                    techniciansErrorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar técnicos:', error);
                loadingTechniciansMessage.classList.add('hidden');
                techniciansErrorMessage.textContent = 'Erro ao carregar técnicos. Tente novamente.';
                techniciansErrorMessage.classList.remove('hidden');
            }
        }

        // Função para alternar a seleção de um técnico
        function toggleTechnicianSelection(button, technicianId) {
            const index = selectedTechnicianIds.indexOf(technicianId);
            if (index > -1) {
                // Já selecionado, remove da lista
                selectedTechnicianIds.splice(index, 1);
                button.classList.remove('selected');
            } else {
                // Não selecionado, adiciona à lista
                selectedTechnicianIds.push(technicianId);
                button.classList.add('selected');
            }
            console.log('Técnicos selecionados:', selectedTechnicianIds);
        }

        // Função para abrir o modal de seleção de técnicos
        async function openAssignTechnicianModal(item) { // Agora recebe o item completo
            pendingItemsModal.classList.remove('is-active'); // Esconde o modal de itens pendentes
            assignTechnicianModal.classList.add('is-active'); // Mostra o modal de técnicos
            hideMessage(assignMessage); // Esconde mensagens anteriores
            toggleSpinner(assignButton, assignSpinner, false); // Garante que o botão esteja habilitado e spinner escondido ao abrir
            assignButton.classList.remove('hidden'); // Garante que o botão esteja visível ao abrir o modal

            // Preenche os detalhes da manutenção/instalação na caixa
            selectedItemDetailsBox.innerHTML = `
                <p><strong class="highlight-label">Nome do Equipamento:</strong> ${item.nome_equip}</p>
                <p><strong class="highlight-label">Ocorrência do Reparo:</strong> ${item.ocorrencia_reparo || 'N/A'}</p>
                <p><strong>Referência do Equipamento:</strong> ${item.referencia_equip}</p>
            `;

            await loadTechnicians(); // Carrega os técnicos
        }

        // Função para fechar o modal de seleção de técnicos
        function closeAssignTechnicianModal() {
            assignTechnicianModal.classList.remove('is-active');
            // Ao fechar o modal de técnicos, voltamos para o modal de itens pendentes
            // para que o usuário possa continuar atribuindo outros itens da mesma cidade.
            if (selectedCityId && currentFlowType) {
                openPendingItemsModal(selectedCityId, selectedCityName, currentFlowType);
            } else {
                // Se não houver cidade selecionada (caso de erro ou fluxo inesperado), volta para a seleção de cidades
                citySelectionSection.style.display = 'flex';
                mainTitle.textContent = 'Atribuir Manutenção/Instalação ao Técnico'; // Volta o título original
                currentFlowType = ''; // Reseta o tipo de fluxo
            }
            assignButton.classList.remove('hidden'); // Garante que o botão esteja visível para a próxima vez
            toggleSpinner(assignButton, assignSpinner, false); // Garante que o spinner esteja escondido e o botão habilitado
        }

        // Lógica para o botão "Atribuir" no modal de técnicos
        assignButton.addEventListener('click', async function() {
            if (selectedTechnicianIds.length === 0) {
                showMessage(assignMessage, 'Selecione pelo menos um técnico.', 'error');
                return;
            }

            if (!currentManutencaoId) {
                showMessage(assignMessage, 'Nenhuma manutenção selecionada para atribuição.', 'error');
                console.error('Erro: currentManutencaoId é NULL.');
                return;
            }

            // --- NOVO LOG PARA DEPURAR O VALOR ENVIADO ---
            console.log('Sending to PHP: id_manutencao =', currentManutencaoId, 'selected_tecnicos_ids =', selectedTechnicianIds);
            // ---------------------------------------------

            showMessage(assignMessage, 'Atribuindo técnicos...', ''); // Mensagem de carregamento
            toggleSpinner(assignButton, assignSpinner, true); // Mostra spinner e desabilita botão

            try {
                const response = await fetch('atribuir_tecnicos_manutencao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_manutencao: currentManutencaoId,
                        selected_tecnicos_ids: selectedTechnicianIds
                    })
                });

                const data = await response.json();
                console.log('Resposta de atribuir_tecnicos_manutencao.php:', data);

                if (data.success) {
                    showMessage(assignMessage, data.message, 'success');
                    // Oculta o botão permanentemente para esta operação bem-sucedida
                    assignButton.classList.add('hidden');

                    setTimeout(() => {
                        closeAssignTechnicianModal(); // Isso reabilitará o botão para a próxima vez
                        // Recarrega o modal de itens pendentes para refletir a mudança
                        // (o item atribuído não deve mais aparecer)
                        openPendingItemsModal(selectedCityId, selectedCityName, currentFlowType);
                    }, 1500);
                } else {
                    showMessage(assignMessage, data.message, 'error');
                    toggleSpinner(assignButton, assignSpinner, false); // Reabilita o botão em caso de erro
                }
            } catch (error) {
                console.error('Erro ao atribuir técnicos:', error);
                showMessage(assignMessage, 'Ocorreu um erro ao tentar atribuir os técnicos. Tente novamente.', 'error');
                toggleSpinner(assignButton, assignSpinner, false); // Reabilita o botão em caso de erro
            }
            // O bloco finally foi removido aqui porque o toggleSpinner(false) é condicional dentro do try/catch
        });


        // Event listener para o botão "Manutenções"
        if (manutencoesBtn) {
            manutencoesBtn.addEventListener('click', function() {
                initialButtonsSection.style.display = 'none';
                citySelectionSection.style.display = 'flex';
                mainTitle.textContent = 'Atribuir Manutenção ao Técnico'; // Altera o título
                currentFlowType = 'maintenance';
                loadCities();
            });
        }

        // Event listener para o botão "Instalações"
        if (instalacoesBtn) {
            instalacoesBtn.addEventListener('click', function() {
                initialButtonsSection.style.display = 'none';
                citySelectionSection.style.display = 'flex';
                mainTitle.textContent = 'Atribuir Instalação ao Técnico'; // Altera o título
                currentFlowType = 'installation';
                loadCities();
            });
        }

        // Função para voltar à seleção inicial de botões (Manutenções / Instalações)
        function goBackToInitialSelection() {
            citySelectionSection.style.display = 'none';
            pendingItemsModal.classList.remove('is-active'); // Garante que o modal de itens pendentes esteja fechado
            assignTechnicianModal.classList.remove('is-active'); // Garante que o modal de técnicos esteja fechado
            initialButtonsSection.style.display = 'flex';
            mainTitle.textContent = 'Atribuir Manutenção/Instalação ao Técnico'; // Volta o título original
            currentFlowType = ''; // Reseta o tipo de fluxo
        }

        // Fecha os modais se o usuário clicar fora deles
        window.onclick = function(event) {
            if (event.target == pendingItemsModal) {
                closePendingItemsModal();
            }
            if (event.target == assignTechnicianModal) {
                closeAssignTechnicianModal();
            }
        }
    </script>
</body>

</html>
