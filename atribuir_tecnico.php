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
            height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .main-title {
            color: #333;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .button-section,
        .city-selection-section {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            color: #ffffff;
            background-color: #007bff;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
            min-width: 200px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-maintenance {
            background-color: #28a745;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }

        .btn-maintenance:hover {
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.3);
        }

        .back-btn {
            background-color: #6c757d;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
            margin-top: 20px;
        }

        .back-btn:hover {
            box-shadow: 0 6px 15px rgba(108, 117, 125, 0.3);
        }

        .city-card {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            width: 150px;
            transition: background-color 0.2s;
            font-size: 0.9rem;
        }

        .city-card:hover {
            background-color: #d8dbe0;
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
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            text-align: left;
            position: relative;
        }

        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.5rem;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: #333;
        }

        .pending-items-list {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
            padding-right: 10px;
        }

        .pending-item-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-align: left;
        }

        .pending-item-card:hover {
            background-color: #e9ecef;
        }

        .pending-item-card h4 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #495057;
        }

        .pending-item-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .assign-form-group {
            margin-bottom: 15px;
        }

        .assign-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .assign-form-group select,
        .assign-form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            box-sizing: border-box;
        }
        .assign-form-group select[multiple] {
            height: 100px; /* Altura padrão para multi-select */
        }
    </style>
</head>

<body>
    <div class="card">
        <h1 class="main-title" id="mainTitle">Atribuir Manutenção/Instalação ao Técnico</h1>

        <!-- Seção inicial com botões de Manutenção e Instalação -->
        <div class="button-section" id="initialButtonsSection">
            <button class="btn btn-maintenance" id="maintenanceBtn">Manutenções</button>
            <button class="btn" id="instalacoesBtn">Instalações</button>
        </div>

        <!-- Seção de seleção de cidade (oculto por padrão) -->
        <div class="city-selection-section" id="citySelectionSection" style="display: none;">
            <!-- Cidades serão carregadas aqui via JavaScript -->
        </div>

        <!-- Botão de Voltar (oculto por padrão) -->
        <button class="btn back-btn" id="backBtn" style="display: none;">Voltar</button>
    </div>

    <!-- Modal para exibir ocorrências pendentes -->
    <div class="modal" id="pendingItemsModal">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="closePendingItemsModal()">&times;</span>
            <h3 id="pendingItemsModalTitle">Ocorrências Pendentes</h3>
            <div id="pendingItemsList" class="pending-items-list">
                <!-- Itens pendentes serão carregados aqui via JS -->
            </div>
        </div>
    </div>

    <!-- Modal para atribuir técnicos e veículos -->
    <div class="modal" id="assignTechnicianModal">
        <div class="modal-content">
            <span class="modal-close-btn" onclick="closeAssignTechnicianModal()">&times;</span>
            <h3>Atribuir Ocorrência</h3>
            <div id="assignItemDetails">
                <!-- Detalhes do item selecionado -->
            </div>

            <form id="assignForm">
                <input type="hidden" id="selectedItemId">
                <div class="assign-form-group">
                    <label for="technicianSelect">Selecionar Técnico(s):</label>
                    <select id="technicianSelect" multiple required></select>
                </div>
                <div class="assign-form-group">
                    <label for="vehicleSelect">Selecionar Veículo(s):</label>
                    <select id="vehicleSelect" multiple required></select>
                </div>
                <div class="assign-form-group">
                    <label for="startDatetime">Data e Hora de Início:</label>
                    <input type="datetime-local" id="startDatetime" required>
                </div>
                <div class="assign-form-group">
                    <label for="endDatetime">Data e Hora de Fim:</label>
                    <input type="datetime-local" id="endDatetime" required>
                </div>
                <button type="submit" class="btn">Atribuir</button>
            </form>
        </div>
    </div>

    <script>
        // Variáveis globais
        const mainTitle = document.getElementById('mainTitle');
        const initialButtonsSection = document.getElementById('initialButtonsSection');
        const maintenanceBtn = document.getElementById('maintenanceBtn');
        const instalacoesBtn = document.getElementById('instalacoesBtn');
        const citySelectionSection = document.getElementById('citySelectionSection');
        const backBtn = document.getElementById('backBtn');
        const pendingItemsModal = document.getElementById('pendingItemsModal');
        const pendingItemsList = document.getElementById('pendingItemsList');
        const assignTechnicianModal = document.getElementById('assignTechnicianModal');
        const assignItemDetails = document.getElementById('assignItemDetails');
        const technicianSelect = document.getElementById('technicianSelect');
        const vehicleSelect = document.getElementById('vehicleSelect');
        const assignForm = document.getElementById('assignForm');
        const selectedItemIdInput = document.getElementById('selectedItemId');
        let currentFlowType = '';
        let citiesData = [];
        let pendingItemsData = [];
        let techniciansData = [];
        let vehiclesData = [];

        // Adiciona event listeners aos botões iniciais
        if (maintenanceBtn) {
            maintenanceBtn.addEventListener('click', function() {
                startFlow('maintenance');
            });
        }
        if (instalacoesBtn) {
            instalacoesBtn.addEventListener('click', function() {
                startFlow('installation');
            });
        }

        // Adiciona event listener ao botão de voltar
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                goBackToInitialSelection();
            });
        }

        // Adiciona event listener ao formulário de atribuição
        if (assignForm) {
            assignForm.addEventListener('submit', function(event) {
                event.preventDefault();
                handleAssignFormSubmit();
            });
        }

        // Função para iniciar o fluxo de Manutenção ou Instalação
        function startFlow(flowType) {
            currentFlowType = flowType;
            initialButtonsSection.style.display = 'none';
            citySelectionSection.style.display = 'flex';
            backBtn.style.display = 'block';
            mainTitle.textContent = flowType === 'maintenance' ? 'Atribuir Manutenção ao Técnico' : 'Atribuir Instalação ao Técnico';
            loadCities();
        }

        // Carrega a lista de cidades
        function loadCities() {
            fetch('get_cidades.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        citiesData = data.cidades;
                        displayCities(citiesData);
                    } else {
                        console.error('Erro ao carregar cidades:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de cidades:', error);
                });
        }

        // Exibe a lista de cidades
        function displayCities(cities) {
            citySelectionSection.innerHTML = '';
            cities.forEach(city => {
                const cityCard = document.createElement('div');
                cityCard.classList.add('city-card');
                cityCard.textContent = city.nome;
                cityCard.addEventListener('click', () => {
                    loadPendingItems(city.id_cidade);
                });
                citySelectionSection.appendChild(cityCard);
            });
        }

        // Carrega a lista de itens pendentes para a cidade selecionada
        function loadPendingItems(cityId) {
            fetch(`get_atribuicoes_pendentes.php?city_id=${cityId}&flow_type=${currentFlowType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        pendingItemsData = data.items;
                        displayPendingItems(pendingItemsData);
                        openPendingItemsModal();
                    } else {
                        pendingItemsList.innerHTML = `<p>${data.message}</p>`;
                        openPendingItemsModal();
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de itens pendentes:', error);
                });
        }

        // Exibe os itens pendentes no modal
        function displayPendingItems(items) {
            pendingItemsList.innerHTML = '';
            items.forEach(item => {
                const itemCard = document.createElement('div');
                itemCard.classList.add('pending-item-card');
                itemCard.innerHTML = `
                    <h4>${item.nome_equip}</h4>
                    <p>Referência: ${item.referencia_equip}</p>
                    <p>Ocorrência: ${item.ocorrencia_reparo}</p>
                    <p>Cidade: ${item.cidade_nome}</p>
                `;
                itemCard.addEventListener('click', () => {
                    openAssignTechnicianModal(item);
                });
                pendingItemsList.appendChild(itemCard);
            });
        }

        // Abre o modal de itens pendentes
        function openPendingItemsModal() {
            pendingItemsModal.style.display = 'flex';
        }

        // Fecha o modal de itens pendentes
        function closePendingItemsModal() {
            pendingItemsModal.style.display = 'none';
        }

        // Carrega a lista de técnicos e veículos
        function loadTechniciansAndVehicles() {
            // Promise para carregar os técnicos
            const fetchTechnicians = fetch('get_tecnicos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        techniciansData = data.tecnicos;
                    } else {
                        console.error('Erro ao carregar técnicos:', data.message);
                    }
                });

            // Promise para carregar os veículos
            const fetchVehicles = fetch('get_veiculos.php')
                .then(response => response.json())
                .then(data => {
                    // CORREÇÃO: Verifica se a propriedade 'veiculos' existe, caso contrário, assume que a 'data' é o array.
                    // Isso é necessário porque get_veiculos.php retorna o array diretamente.
                    vehiclesData = data.veiculos || data;
                    if (!vehiclesData || !Array.isArray(vehiclesData)) {
                        console.error('Erro ao carregar veículos: dados inválidos.');
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de veículos:', error);
                });

            return Promise.all([fetchTechnicians, fetchVehicles]);
        }

        // Abre o modal de atribuição
        function openAssignTechnicianModal(item) {
            // Fecha o modal de itens pendentes
            closePendingItemsModal();

            // Carrega os dados de técnicos e veículos antes de abrir o modal
            loadTechniciansAndVehicles().then(() => {
                // Preenche os detalhes do item
                assignItemDetails.innerHTML = `
                    <h4>${item.nome_equip}</h4>
                    <p>Referência: ${item.referencia_equip}</p>
                    <p>Ocorrência: ${item.ocorrencia_reparo}</p>
                    <hr>
                `;

                // Preenche o campo de seleção de técnicos
                technicianSelect.innerHTML = '';
                techniciansData.forEach(tech => {
                    const option = document.createElement('option');
                    option.value = tech.id_usuario; // Usando id_usuario conforme a tabela
                    option.textContent = tech.nome;
                    technicianSelect.appendChild(option);
                });

                // Preenche o campo de seleção de veículos
                vehicleSelect.innerHTML = '';
                vehiclesData.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id_veiculo;
                    option.textContent = `${vehicle.nome} (${vehicle.placa})`;
                    vehicleSelect.appendChild(option);
                });

                // Define o ID do item a ser atribuído
                selectedItemIdInput.value = item.id_manutencao;

                // Abre o modal de atribuição
                assignTechnicianModal.style.display = 'flex';
            }).catch(error => {
                console.error('Erro ao carregar técnicos e veículos:', error);
                // Exibe uma mensagem de erro na tela para o usuário
                const messageBox = document.createElement('div');
                messageBox.textContent = 'Erro ao carregar a lista de técnicos e veículos.';
                messageBox.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#f44336;color:white;padding:20px;border-radius:10px;z-index:9999;';
                document.body.appendChild(messageBox);
                setTimeout(() => messageBox.remove(), 3000);
            });
        }

        // Fecha o modal de atribuição
        function closeAssignTechnicianModal() {
            assignTechnicianModal.style.display = 'none';
        }

        // Lida com o envio do formulário de atribuição
        function handleAssignFormSubmit() {
            const itemId = selectedItemIdInput.value;
            const technicianIds = Array.from(technicianSelect.selectedOptions).map(option => option.value);
            const vehicleIds = Array.from(vehicleSelect.selectedOptions).map(option => option.value);
            const startDatetime = document.getElementById('startDatetime').value;
            const endDatetime = document.getElementById('endDatetime').value;

            // Validações simples
            if (technicianIds.length === 0 || vehicleIds.length === 0 || !startDatetime || !endDatetime) {
                const messageBox = document.createElement('div');
                messageBox.textContent = 'Por favor, preencha todos os campos.';
                messageBox.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#f44336;color:white;padding:20px;border-radius:10px;z-index:9999;';
                document.body.appendChild(messageBox);
                setTimeout(() => messageBox.remove(), 3000);
                return;
            }

            const assignmentData = {
                item_id: itemId,
                technician_ids: technicianIds,
                vehicle_ids: vehicleIds,
                start_datetime: startDatetime,
                end_datetime: endDatetime
            };

            fetch('atribuir_tecnicos_manutencao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(assignmentData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Use um modal ou caixa de mensagem personalizada em vez de alert
                        const messageBox = document.createElement('div');
                        messageBox.textContent = 'Atribuição realizada com sucesso!';
                        messageBox.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#4CAF50;color:white;padding:20px;border-radius:10px;z-index:9999;';
                        document.body.appendChild(messageBox);
                        setTimeout(() => messageBox.remove(), 3000); // Remove a mensagem após 3 segundos
                        
                        closeAssignTechnicianModal();
                        goBackToInitialSelection();
                    } else {
                        // Use um modal ou caixa de mensagem personalizada em vez de alert
                        const messageBox = document.createElement('div');
                        messageBox.textContent = 'Erro ao atribuir: ' + data.message;
                        messageBox.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#f44336;color:white;padding:20px;border-radius:10px;z-index:9999;';
                        document.body.appendChild(messageBox);
                        setTimeout(() => messageBox.remove(), 3000);
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de atribuição:', error);
                    // Use um modal ou caixa de mensagem personalizada em vez de alert
                    const messageBox = document.createElement('div');
                    messageBox.textContent = 'Erro na requisição de atribuição.';
                    messageBox.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#f44336;color:white;padding:20px;border-radius:10px;z-index:9999;';
                    document.body.appendChild(messageBox);
                    setTimeout(() => messageBox.remove(), 3000);
                });
        }

        // Função para voltar à seleção inicial de botões (Manutenções / Instalações)
        function goBackToInitialSelection() {
            citySelectionSection.style.display = 'none';
            backBtn.style.display = 'none';
            pendingItemsModal.style.display = 'none'; // Garante que o modal de itens pendentes esteja fechado
            assignTechnicianModal.style.display = 'none'; // Garante que o modal de técnicos esteja fechado
            initialButtonsSection.style.display = 'flex';
            mainTitle.textContent = 'Atribuir Manutenção/Instalação ao Técnico'; // Volta o título original
            currentFlowType = ''; // Reseta o tipo de fluxo
        }
    </script>
</body>

</html>
