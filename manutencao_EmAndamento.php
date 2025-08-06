<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para index.html se user_id não estiver na sessão
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Opcional: Redirecionar se o tipo de usuário não tiver permissão para esta página
// Exemplo: if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] !== 'tecnico') {
//             header('Location: menu.php');
//             exit();
//         }

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Manutenção em Andamento</title>
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

        /* Contêiner para os botões da página */
        .page-buttons-container {
            display: flex;
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

        /* Estilo para o botão "Voltar" */
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

        /* Estilos para o footer */
        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        /* Estilos para a seção de seleção de cidades */
        .city-selection-section {
            display: none; /* Escondido por padrão */
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

        /* Estilo para o botão de voltar dentro das seções */
        .back-button {
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
        .back-button:hover,
        .back-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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

        .hidden {
            display: none !important;
        }

        /* Estilos para o Modal de Ocorrências */
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

        .ocorrencia-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: left;
        }

        .ocorrencia-item p {
            margin-bottom: 0.5rem;
            font-size: 0.95em;
            color: #333;
        }

        .ocorrencia-item strong {
            color: var(--cor-principal);
        }
        /* Novo estilo para os labels destacados */
        .ocorrencia-item strong.highlight-label {
            color: var(--cor-terciaria);
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
            .modal-content {
                padding: 1.5rem;
                max-width: 95%; /* Ocupa mais espaço em telas menores */
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
            .ocorrencia-item {
                padding: 0.8rem;
                font-size: 0.9em;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <img class="logoMenu" src="imagens/logo.png" alt="Logo" />
        <h2 id="mainTitle">Manutenção/Instalação em Andamento</h2>

        <div id="initialButtonsSection" class="page-buttons-container">
            <button id="manutencoesBtn" class="page-button">Manutenções</button>
            <button id="instalacoesBtn" class="page-button">Instalações</button>
        </div>

        <div id="citySelectionSection" class="city-selection-section">
            <button class="back-button" onclick="goBackToInitialSelection()">&larr;</button>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Selecione a Cidade</h3>
            <p class="mb-4 text-gray-700">Selecione a cidade para ver as ocorrências:</p>
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

    <!-- Modal de Ocorrências em Andamento -->
    <div id="ocorrenciasModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeOcorrenciasModal()">&times;</span>
            <h3 id="ocorrenciasModalTitle"></h3>
            <div id="ocorrenciasList">
                <p id="loadingOcorrenciasMessage">Carregando ocorrências...</p>
                <p id="ocorrenciasErrorMessage" class="message error hidden"></p>
            </div>
        </div>
    </div>

    <script>
        // Referências aos elementos do DOM
        const mainTitle = document.getElementById('mainTitle'); // Nova referência para o H2
        const initialButtonsSection = document.getElementById('initialButtonsSection');
        const manutencoesBtn = document.getElementById('manutencoesBtn');
        const instalacoesBtn = document.getElementById('instalacoesBtn');

        const citySelectionSection = document.getElementById('citySelectionSection');
        const cityButtonsContainer = document.getElementById('cityButtonsContainer');
        const loadingCitiesMessage = document.getElementById('loadingCitiesMessage');
        const cityErrorMessage = document.getElementById('cityErrorMessage');

        const ocorrenciasModal = document.getElementById('ocorrenciasModal');
        const ocorrenciasModalTitle = document.getElementById('ocorrenciasModalTitle');
        const ocorrenciasList = document.getElementById('ocorrenciasList');
        const loadingOcorrenciasMessage = document.getElementById('loadingOcorrenciasMessage');
        const ocorrenciasErrorMessage = document.getElementById('ocorrenciasErrorMessage');

        let currentFlowType = ''; // 'maintenance' ou 'installation'

        // Funções de utilidade
        function showMessage(element, msg) {
            element.textContent = msg;
            element.classList.remove('hidden');
        }

        function hideMessage(element) {
            element.classList.add('hidden');
            element.textContent = '';
        }

        // Função para carregar e exibir os botões das cidades
        async function loadCities() {
            cityButtonsContainer.innerHTML = ''; // Limpa botões anteriores
            loadingCitiesMessage.classList.remove('hidden');
            hideMessage(cityErrorMessage);

            console.log('Carregando cidades...');

            try {
                const response = await fetch('get_cidades.php');
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
                        button.addEventListener('click', () => openOcorrenciasModal(city.id_cidade, city.nome, currentFlowType));
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

        // Função para abrir o modal de ocorrências e carregar os dados
        async function openOcorrenciasModal(cityId, cityName, flowType) {
            ocorrenciasModal.classList.add('is-active');
            ocorrenciasModalTitle.textContent = `Ocorrências em Andamento - ${cityName}`;
            ocorrenciasList.innerHTML = ''; // Limpa conteúdo anterior
            loadingOcorrenciasMessage.classList.remove('hidden');
            hideMessage(ocorrenciasErrorMessage);

            console.log(`Carregando ocorrências para cidade ${cityName} (ID: ${cityId}) e fluxo ${flowType}...`);

            try {
                const url = `get_ocorrencias_em_andamento.php?city_id=${cityId}&flow_type=${flowType}`;
                const response = await fetch(url);
                const data = await response.json();

                console.log('Resposta de get_ocorrencias_em_andamento.php:', data);

                loadingOcorrenciasMessage.classList.add('hidden');

                if (data.success && data.ocorrencias.length > 0) {
                    data.ocorrencias.forEach(ocorrencia => {
                        const ocorrenciaItem = document.createElement('div');
                        ocorrenciaItem.classList.add('ocorrencia-item');

                        // Formata a data de solicitação
                        const solicitacaoDate = new Date(ocorrencia.inicio_reparo);
                        const formattedDate = solicitacaoDate.toLocaleDateString('pt-BR', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const tempoEmAndamento = calculateTimeInProgress(ocorrencia.inicio_reparo);

                        // Alterado para aplicar a classe highlight-label aos campos desejados e remover Tipo/Status
                        let content = `
                            <p><strong class="highlight-label">Nome do Equipamento:</strong> ${ocorrencia.nome_equip}</p>
                            <p><strong class="highlight-label">Ocorrência do Reparo:</strong> ${ocorrencia.ocorrencia_reparo || 'N/A'}</p>
                            <p><strong class="highlight-label">Referência do Equipamento:</strong> ${ocorrencia.referencia_equip}</p>
                            <p><strong class="highlight-label">Técnico(s) Responsável(is):</strong> ${ocorrencia.tecnicos_nomes || 'Não atribuído'}</p>
                            <p><strong>Data da Solicitação:</strong> ${formattedDate}</p>
                            <p><strong>Tempo em Andamento:</strong> ${tempoEmAndamento}</p>
                        `;
                        
                        // Adicionar mais detalhes para instalações se necessário
                        if (ocorrencia.tipo_manutencao === 'instalação') {
                            // Você pode adicionar campos específicos de instalação aqui se tiver no DB
                            // Ex: Endereço completo, Latitude/Longitude, etc.
                            // Por enquanto, os campos já são genéricos o suficiente.
                        }

                        ocorrenciaItem.innerHTML = content;
                        ocorrenciasList.appendChild(ocorrenciaItem);
                    });
                } else {
                    ocorrenciasErrorMessage.textContent = data.message || 'Nenhuma ocorrência encontrada.';
                    ocorrenciasErrorMessage.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar ocorrências:', error);
                loadingOcorrenciasMessage.classList.add('hidden');
                ocorrenciasErrorMessage.textContent = 'Erro ao carregar ocorrências. Tente novamente.';
                ocorrenciasErrorMessage.classList.remove('hidden');
            }
        }

        // Função para fechar o modal de ocorrências
        function closeOcorrenciasModal() {
            ocorrenciasModal.classList.remove('is-active');
        }

        // Event listener para o botão "Manutenções"
        if (manutencoesBtn) {
            manutencoesBtn.addEventListener('click', function() {
                initialButtonsSection.style.display = 'none';
                citySelectionSection.style.display = 'flex';
                mainTitle.textContent = 'Manutenção em Andamento'; // Altera o título para Manutenção
                currentFlowType = 'maintenance';
                loadCities();
            });
        }

        // Event listener para o botão "Instalações"
        if (instalacoesBtn) {
            instalacoesBtn.addEventListener('click', function() {
                initialButtonsSection.style.display = 'none';
                citySelectionSection.style.display = 'flex';
                mainTitle.textContent = 'Instalação em Andamento'; // Altera o título para Instalação
                currentFlowType = 'installation';
                loadCities();
            });
        }

        // Função para voltar à seleção inicial de botões
        function goBackToInitialSelection() {
            citySelectionSection.style.display = 'none';
            initialButtonsSection.style.display = 'flex';
            mainTitle.textContent = 'Manutenção/Instalação em Andamento'; // Volta o título original
            currentFlowType = ''; // Reseta o tipo de fluxo
        }

        // Fecha o modal se o usuário clicar fora dele
        window.onclick = function(event) {
            if (event.target == ocorrenciasModal) {
                closeOcorrenciasModal();
            }
        }
    </script>
</body>

</html>
