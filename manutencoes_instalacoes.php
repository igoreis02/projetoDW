<?php
session_start(); // Inicia ou resume a sessão
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
  // Redireciona para a página de login
  header("Location: index.html");
  exit;
}
$_SESSION['last_access'] = time(); // Atualiza o timestamp do último acesso

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Se o tipo de usuário for 'entregador', redirecione-o para a página de entrega,
// pois ele não deveria ter acesso direto a esta página.
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'entregador') {
    header('Location: lista_pedidos_em_entrega.html');
    exit();
}

// Verifica se a redefinição de senha é obrigatória (mantém a lógica para consistência)
$redefinir_senha_obrigatoria = isset($_SESSION['redefinir_senha_obrigatoria']) && $_SESSION['redefinir_senha_obrigatoria'] === true;

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Manutenções e Instalações</title>
    <style>
        /* Estilos do card e layout geral */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column; /* Alterado para empilhar os elementos verticalmente */
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card { /* Alterado de .cardsecundario para .card para consistência com o HTML existente */
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px; /* Ajuste a largura máxima conforme necessário */
            text-align: center;
            position: relative; /* Para posicionar o logo */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card:before {
            content: none; /* Remove o pseudo-elemento ::before do card se existir */
        }

        .logoMenu {
            width: 150px; /* Tamanho do logo no menu */
            margin-bottom: 20px;
            position: absolute;
            top: -60px; /* Ajuste para posicionar o logo acima do card */
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        h2 {
            font-size: 2em;
            color: var(--cor-principal);
            margin-bottom: 30px;
            margin-top: 40px; /* Espaço para o logo */
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
            margin-top: auto; /* Adicionado para empurrar o footer para o final do flex container */
            color: #888;
            font-size: 0.9em;
            width: 100%; /* Garante que o footer ocupe a largura total */
            text-align: center; /* Centraliza o texto do footer */
            padding-top: 20px; /* Adiciona um pouco de padding acima */
        }

        /* Estilos para o Modal de Cadastro de Manutenção e Confirmação */
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
            max-width: 500px; /* Ajuste a largura máxima do modal */
            position: relative; /* Essencial para posicionar o botão de fechar */
        }

        /* Centraliza o título do modal */
        .modal-content h3 {
            text-align: center;
            margin-bottom: 1rem; /* Ajuste o espaçamento se necessário */
        }

        .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Novo estilo para o botão de voltar no modal */
        .back-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            left: 15px; /* Posiciona no canto superior esquerdo */
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none; /* Remove fundo padrão de botão */
            border: none; /* Remove borda padrão de botão */
            padding: 0; /* Remove padding padrão de botão */
            line-height: 1; /* Alinha o "x" verticalmente */
        }
        .back-button:hover,
        .back-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }


        .city-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 10px; /* Espaçamento entre os botões de cidade */
            margin-top: 20px;
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

        /* Estilos para a seção de seleção de equipamentos */
        .equipment-selection-container {
            display: none; /* Escondido por padrão */
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .equipment-selection-container input[type="text"],
        .equipment-selection-container select,
        .equipment-selection-container textarea { /* Adicionado textarea */
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .equipment-selection-container select {
            min-height: 100px; /* Altura mínima para o select */
            overflow-y: auto; /* Rolagem se muitos itens */
        }

        /* Estilos para a seção de descrição do problema */
        .problem-description-container {
            display: none; /* Escondido por padrão */
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .problem-description-container label {
            font-weight: bold;
            text-align: left;
            width: 100%;
        }

        /* Estilos para o modal de confirmação */
        .confirmation-details {
            margin-bottom: 1rem; /* Espaçamento abaixo dos detalhes */
        }

        .confirmation-details p {
            text-align: left;
            margin-bottom: 0.5rem;
            font-size: 1.1em;
            color: #333;
        }

        .confirmation-details strong {
            color: var(--cor-principal);
        }

        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .confirmation-buttons button {
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            flex: 1; /* Para que os botões ocupem o espaço igualmente */
            max-width: 150px; /* Limita a largura dos botões */
            display: flex; /* Para alinhar spinner e texto */
            align-items: center;
            justify-content: center;
        }

        .confirmation-buttons .confirm-button {
            background-color: #28a745; /* Verde para confirmar */
            color: white;
        }

        .confirmation-buttons .confirm-button:hover {
            background-color: #218838;
        }

        .confirmation-buttons .cancel-button {
            background-color: #dc3545; /* Vermelho para cancelar */
            color: white;
        }

        .confirmation-buttons .cancel-button:hover {
            background-color: #c82333;
        }

        /* Estilo para a mensagem de erro acima do botão */
        .selection-error-message {
            color: #dc3545; /* Vermelho para erro */
            font-size: 0.9em;
            margin-top: -10px; /* Ajusta o espaçamento */
            margin-bottom: 10px;
            text-align: center;
        }

        /* Classe para esconder elementos via JS */
        .hidden {
            display: none !important; /* Adicionado !important para garantir que sobrescreva outros displays */
        }

        /* Estilos para a nova seção de cadastro de equipamento/endereço */
        .install-equipment-section {
            display: none; /* Escondido por padrão */
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            text-align: left; /* Alinha o texto dentro da seção à esquerda */
        }

        .install-equipment-section input[type="text"],
        .install-equipment-section input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .install-equipment-section label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block; /* Garante que o label ocupe sua própria linha */
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

        /* Mensagens de sucesso/erro no modal de confirmação */
        .confirmation-message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }
        .confirmation-message.error {
            background-color: #fee2e2;
            color: #ef4444;
            border: 1px solid #fca5a5;
        }
        .confirmation-message.success {
            background-color: #dcfce7;
            color: #22c55e;
            border: 1px solid #86efac;
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
            }
            .confirmation-buttons {
                flex-direction: column; /* Empilha botões em telas menores */
            }
            .confirmation-buttons button {
                max-width: 100%;
            }
        }

        @media (max-width: 480px) {
            body {
                /* Removido display: flex; flex-direction: column; pois já está no body principal */
                padding: 10px; /* Adjust padding for smaller screens */
            }
            .card {
                width: 100%; /* Full width on smaller screens */
                height: auto; /* Or auto to center */
                padding: 10px; /* Restore original padding for larger screens */
                margin: auto;
            }
            .footer {
              /* Removido display: none; para que o footer seja visível em telas pequenas */
              margin-top: auto; /* Garante que o footer seja empurrado para baixo */
            }
            .form {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Manutenções e Instalações</h2>

        <div class="page-buttons-container">
            <button id="matrizManutencaoBtn" class="page-button">Matriz Manutenção</button>
            <button id="controleOcorrenciaBtn" class="page-button">Controle de Ocorrência</button>
            <button id="instalarEquipamentoBtn" class="page-button">Instalar equipamento</button>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <?php if ($redefinir_senha_obrigatoria): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Se a redefinição de senha for obrigatória, redireciona para a página de login
            // onde o modal de redefinição será exibido.
            // Isso evita que o usuário "burle" a redefinição acessando outras páginas.
            window.location.href = 'index.html'; // Ou 'login.html' se for o nome do seu arquivo de login
        });
    </script>
    <?php endif; ?>

    <div id="cadastroManutencaoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeCadastroManutencaoModal()">&times;</span>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Cadastrar Manutenção</h3>

            <div id="citySelectionSection">
                <p class="mb-4 text-gray-700">Selecione a cidade para a nova manutenção:</p>
                <div id="cityButtonsContainer" class="city-buttons-container">
                    <p id="loadingCitiesMessage">Carregando cidades...</p>
                </div>
                <p id="cityErrorMessage" class="message error hidden"></p>
            </div>

            <div id="equipmentSelectionSection" class="equipment-selection-container">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <p class="mb-4 text-gray-700">Selecione o equipamento:</p>
                <input type="text" id="equipmentSearchInput" placeholder="Digite o nome do equipamento para filtrar...">
                <select id="equipmentSelect" size="5">
                    </select>
                <p id="loadingEquipmentMessage" class="hidden"></p>
                <p id="equipmentErrorMessage" class="message error hidden"></p>
                
                <div id="problemDescriptionSection" class="problem-description-container">
                    <label for="problemDescription">Informe o problema para reparo:</label>
                    <textarea id="problemDescription" rows="4" placeholder="Descreva o problema aqui..."></textarea>
                    <span id="problemDescriptionErrorMessage" class="selection-error-message hidden"></span>
                </div>
                
                <span id="equipmentSelectionErrorMessage" class="selection-error-message hidden"></span>

                <button id="confirmEquipmentSelection" class="page-button mt-4">Confirmar Seleção</button>
            </div>

            <!-- Nova Seção para Cadastro de Equipamento e Endereço (para Instalação) -->
            <div id="installEquipmentAndAddressSection" class="install-equipment-section">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <h4 class="text-lg font-bold mb-3 text-gray-800">Cadastrar Equipamento</h4>
                
                <label for="newEquipmentName">Nome do Equipamento:</label>
                <input type="text" id="newEquipmentName" placeholder="Ex: MT300" required>
                <span id="newEquipmentNameError" class="selection-error-message hidden"></span>

                <label for="newEquipmentReference">Referência do Equipamento:</label>
                <input type="text" id="newEquipmentReference" placeholder="Ex: Escola" required>
                <span id="newEquipmentReferenceError" class="selection-error-message hidden"></span>

                <h5 class="text-md font-bold mt-4 mb-2 text-gray-700">Dados do Endereço:</h5>
                <label for="addressLogradouro">Logradouro:</label>
                <input type="text" id="addressLogradouro" placeholder="Ex: Rua Principal" required>
                <span id="addressLogradouroError" class="selection-error-message hidden"></span>

                <label for="addressNumero">Número:</label>
                <input type="text" id="addressNumero" placeholder="Ex: 123" required>
                <span id="addressNumeroError" class="selection-error-message hidden"></span>

                <label for="addressBairro">Bairro:</label>
                <input type="text" id="addressBairro" placeholder="Ex: Centro" required>
                <span id="addressBairroError" class="selection-error-message hidden"></span>

                <label for="addressCep">CEP:</label>
                <input type="text" id="addressCep" placeholder="Ex: 12345-678" required>
                <span id="addressCepError" class="selection-error-message hidden"></span>

                <label for="addressComplemento">Complemento (Opcional):</label>
                <input type="text" id="addressComplemento" placeholder="Ex: Apartamento 101">

                <label for="addressLatitude">Latitude (Opcional):</label>
                <input type="number" step="any" id="addressLatitude" placeholder="-23.55052">

                <label for="addressLongitude">Longitude (Opcional):</label>
                <input type="number" step="any" id="addressLongitude" placeholder="-46.63330">

                <button id="confirmInstallEquipment" class="page-button mt-4">Cadastrar Equipamento e Endereço</button>
            </div>

        </div>
    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConfirmationModal()">&times;</span>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Confirmação da Manutenção</h3>
            <div class="confirmation-details">
                <p><strong>Cidade:</strong> <span id="confirmCityName"></span></p>
                <p><strong>Equipamento:</strong> <span id="confirmEquipmentName"></span></p>
                <p><strong>Problema:</strong> <span id="confirmProblemDescription"></span></p>
                <p><strong>Tipo de Manutenção:</strong> <span id="confirmMaintenanceType"></span></p>
                <p><strong>Status do Reparo:</strong> <span id="confirmRepairStatus"></span></p>
                <!-- Novos campos para confirmação de instalação -->
                <div id="installConfirmationDetails" class="hidden">
                    <h4 class="text-md font-bold mt-4 mb-2 text-gray-700">Detalhes da Instalação:</h4>
                    <p><strong>Nome Equipamento:</strong> <span id="confirmNewEquipmentName"></span></p>
                    <p><strong>Referência Equipamento:</strong> <span id="confirmNewEquipmentRef"></span></p>
                    <p><strong>Logradouro:</strong> <span id="confirmAddressLogradouro"></span></p>
                    <p><strong>Número:</strong> <span id="confirmAddressNumero"></span></p>
                    <p><strong>Bairro:</strong> <span id="confirmAddressBairro"></span></p>
                    <p><strong>CEP:</strong> <span id="confirmAddressCep"></span></p>
                    <p><strong>Complemento:</strong> <span id="confirmAddressComplemento"></span></p>
                    <p><strong>Latitude:</strong> <span id="confirmAddressLatitude"></span></p>
                    <p><strong>Longitude:</strong> <span id="confirmAddressLongitude"></span></p>
                </div>
            </div>
            <div class="confirmation-buttons">
                <button class="confirm-button" id="confirmSaveButton">
                    Confirmar
                    <span id="confirmSpinner" class="loading-spinner hidden"></span>
                </button>
                <button class="cancel-button" id="cancelSaveButton" onclick="cancelSaveManutencao()">Cancelar</button>
            </div>
            <p id="confirmMessage" class="confirmation-message hidden"></p>
        </div>
    </div>

    <script>
        // Funções de utilidade
        function showMessage(element, msg, type) {
            element.textContent = msg;
            element.className = `message ${type}`;
            element.classList.remove('hidden');
        }

        function hideMessage(element) {
            element.classList.add('hidden');
            element.textContent = '';
        }

        // Referências aos elementos do DOM
        const matrizManutencaoBtn = document.getElementById('matrizManutencaoBtn');
        const controleOcorrenciaBtn = document.getElementById('controleOcorrenciaBtn');
        const instalarEquipamentoBtn = document.getElementById('instalarEquipamentoBtn');
        const cadastroManutencaoModal = document.getElementById('cadastroManutencaoModal');
        const citySelectionSection = document.getElementById('citySelectionSection');
        const cityButtonsContainer = document.getElementById('cityButtonsContainer');
        const loadingCitiesMessage = document.getElementById('loadingCitiesMessage');
        const cityErrorMessage = document.getElementById('cityErrorMessage');

        const equipmentSelectionSection = document.getElementById('equipmentSelectionSection');
        const equipmentSearchInput = document.getElementById('equipmentSearchInput');
        const equipmentSelect = document.getElementById('equipmentSelect');
        const loadingEquipmentMessage = document.getElementById('loadingEquipmentMessage');
        const equipmentErrorMessage = document.getElementById('equipmentErrorMessage');
        const confirmEquipmentSelectionBtn = document.getElementById('confirmEquipmentSelection');

        const problemDescriptionSection = document.getElementById('problemDescriptionSection');
        const problemDescriptionInput = document.getElementById('problemDescription');

        // Novas referências para a seção de Instalação de Equipamento e Endereço
        const installEquipmentAndAddressSection = document.getElementById('installEquipmentAndAddressSection');
        const newEquipmentNameInput = document.getElementById('newEquipmentName');
        const newEquipmentReferenceInput = document.getElementById('newEquipmentReference');
        const addressLogradouroInput = document.getElementById('addressLogradouro');
        const addressNumeroInput = document.getElementById('addressNumero');
        const addressBairroInput = document.getElementById('addressBairro');
        const addressCepInput = document.getElementById('addressCep');
        const addressComplementoInput = document.getElementById('addressComplemento');
        const addressLatitudeInput = document.getElementById('addressLatitude');
        const addressLongitudeInput = document.getElementById('addressLongitude');
        const confirmInstallEquipmentBtn = document.getElementById('confirmInstallEquipment');

        // Referências para as mensagens de erro dos novos campos
        const newEquipmentNameError = document.getElementById('newEquipmentNameError');
        const newEquipmentReferenceError = document.getElementById('newEquipmentReferenceError');
        const addressLogradouroError = document.getElementById('addressLogradouroError');
        const addressNumeroError = document.getElementById('addressNumeroError');
        const addressBairroError = document.getElementById('addressBairroError');
        const addressCepError = document.getElementById('addressCepError');


        // Novas referências para o modal de confirmação
        const confirmationModal = document.getElementById('confirmationModal');
        const confirmCityNameSpan = document.getElementById('confirmCityName');
        const confirmEquipmentNameSpan = document.getElementById('confirmEquipmentName');
        const confirmProblemDescriptionSpan = document.getElementById('confirmProblemDescription');
        const confirmMaintenanceTypeSpan = document.getElementById('confirmMaintenanceType');
        const confirmRepairStatusSpan = document.getElementById('confirmRepairStatus');
        const installConfirmationDetails = document.getElementById('installConfirmationDetails');
        const confirmNewEquipmentNameSpan = document.getElementById('confirmNewEquipmentName');
        const confirmNewEquipmentRefSpan = document.getElementById('confirmNewEquipmentRef');
        const confirmAddressLogradouroSpan = document.getElementById('confirmAddressLogradouro');
        const confirmAddressNumeroSpan = document.getElementById('confirmAddressNumero');
        const confirmAddressBairroSpan = document.getElementById('confirmAddressBairro');
        const confirmAddressCepSpan = document.getElementById('confirmAddressCep');
        const confirmAddressComplementoSpan = document.getElementById('confirmAddressComplemento');
        const confirmAddressLatitudeSpan = document.getElementById('confirmAddressLatitude');
        const confirmAddressLongitudeSpan = document.getElementById('confirmAddressLongitude');

        const confirmSaveButton = document.getElementById('confirmSaveButton'); // Botão Confirmar
        const cancelSaveButton = document.getElementById('cancelSaveButton'); // Botão Cancelar
        const confirmSpinner = document.getElementById('confirmSpinner'); // Spinner do botão Confirmar
        const confirmMessage = document.getElementById('confirmMessage'); // Mensagem no modal de confirmação


        // Referências para as mensagens de erro existentes
        const equipmentSelectionErrorMessage = document.getElementById('equipmentSelectionErrorMessage');
        const problemDescriptionErrorMessage = document.getElementById('problemDescriptionErrorMessage');

        let selectedCityId = null;
        let selectedCityName = '';
        let selectedEquipmentId = null;
        let selectedEquipmentName = '';
        let selectedProblemDescription = '';
        let currentMaintenanceType = '';
        let currentRepairStatus = '';
        let currentFlow = '';

        let newlyCreatedEquipmentId = null;
        let newlyCreatedAddressId = null;

        // Variáveis temporárias para armazenar os dados do novo equipamento/endereço antes da confirmação final
        let tempNewEquipmentName = '';
        let tempNewEquipmentRef = '';
        let tempAddressLogradouro = '';
        let tempAddressNumero = '';
        let tempAddressBairro = '';
        let tempAddressCep = '';
        let tempAddressComplemento = '';
        let tempAddressLatitude = null;
        let tempAddressLongitude = null;


        // Função para abrir o modal de Manutenção (geral)
        async function openMaintenanceModal(type, status, flow) {
            currentMaintenanceType = type;
            currentRepairStatus = status;
            currentFlow = flow;

            cadastroManutencaoModal.classList.add('is-active');
            citySelectionSection.style.display = 'block';
            equipmentSelectionSection.style.display = 'none';
            installEquipmentAndAddressSection.style.display = 'none';
            problemDescriptionSection.style.display = 'none';

            // Esconde todas as mensagens de erro ao abrir o modal
            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');
            newEquipmentNameError.classList.add('hidden');
            newEquipmentReferenceError.classList.add('hidden');
            addressLogradouroError.classList.add('hidden');
            addressNumeroError.classList.add('hidden');
            addressBairroError.classList.add('hidden');
            addressCepError.classList.add('hidden');


            loadingCitiesMessage.classList.remove('hidden');
            cityErrorMessage.classList.add('hidden');
            cityButtonsContainer.innerHTML = '';

            console.log('Abrindo modal: Carregando cidades...');

            try {
                const response = await fetch('get_cidades.php');
                const data = await response.json();

                console.log('Resposta de get_cidades.php:', data);

                if (data.success && data.cidades.length > 0) {
                    loadingCitiesMessage.classList.add('hidden');
                    data.cidades.forEach(city => {
                        const button = document.createElement('button');
                        button.classList.add('city-button');
                        button.textContent = city.nome;
                        button.dataset.cityId = city.id_cidade;
                        button.dataset.cityName = city.nome;
                        button.addEventListener('click', () => handleCitySelection(city.id_cidade, city.nome));
                        cityButtonsContainer.appendChild(button);
                    });
                } else {
                    loadingCitiesMessage.classList.add('hidden');
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

        // Função para fechar o modal de Cadastro de Manutenção
        function closeCadastroManutencaoModal() {
            cadastroManutencaoModal.classList.remove('is-active');
            // Resetar o estado do modal para a próxima abertura
            citySelectionSection.style.display = 'block';
            equipmentSelectionSection.style.display = 'none';
            installEquipmentAndAddressSection.style.display = 'none';
            problemDescriptionSection.style.display = 'none';

            // Limpa campos e esconde mensagens de erro
            equipmentSearchInput.value = '';
            equipmentSelect.innerHTML = '';
            problemDescriptionInput.value = '';
            newEquipmentNameInput.value = '';
            newEquipmentReferenceInput.value = '';
            addressLogradouroInput.value = '';
            addressNumeroInput.value = '';
            addressBairroInput.value = '';
            addressCepInput.value = '';
            addressComplementoInput.value = '';
            addressLatitudeInput.value = '';
            addressLongitudeInput.value = '';

            hideMessage(loadingEquipmentMessage);
            hideMessage(equipmentErrorMessage);
            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');
            newEquipmentNameError.classList.add('hidden');
            newEquipmentReferenceError.classList.add('hidden');
            addressLogradouroError.classList.add('hidden');
            addressNumeroError.classList.add('hidden');
            addressBairroError.classList.add('hidden');
            addressCepError.classList.add('hidden');

            selectedCityId = null;
            selectedCityName = '';
            selectedEquipmentId = null;
            selectedEquipmentName = '';
            selectedProblemDescription = '';
            currentMaintenanceType = '';
            currentRepairStatus = '';
            currentFlow = '';
            newlyCreatedEquipmentId = null;
            newlyCreatedAddressId = null;

            // Limpar variáveis temporárias
            tempNewEquipmentName = '';
            tempNewEquipmentRef = '';
            tempAddressLogradouro = '';
            tempAddressNumero = '';
            tempAddressBairro = '';
            tempAddressCep = '';
            tempAddressComplemento = '';
            tempAddressLatitude = null;
            tempAddressLongitude = null;

            closeConfirmationModal();
        }

        // Função para lidar com a seleção de uma cidade
        function handleCitySelection(cityId, cityName) {
            selectedCityId = cityId;
            selectedCityName = cityName;
            citySelectionSection.style.display = 'none';

            // Com base no fluxo atual, mostra a próxima seção
            if (currentFlow === 'maintenance') {
                equipmentSelectionSection.style.display = 'flex';
                problemDescriptionSection.style.display = 'none';
                problemDescriptionInput.value = '';
                equipmentSelectionErrorMessage.classList.add('hidden');
                problemDescriptionErrorMessage.classList.add('hidden');
                loadEquipamentos(selectedCityId);
            } else if (currentFlow === 'installation') {
                installEquipmentAndAddressSection.style.display = 'flex';
                // Limpa os campos do formulário de instalação ao abrir
                newEquipmentNameInput.value = '';
                newEquipmentReferenceInput.value = '';
                addressLogradouroInput.value = '';
                addressNumeroInput.value = '';
                addressBairroInput.value = '';
                addressCepInput.value = '';
                addressComplementoInput.value = '';
                addressLatitudeInput.value = '';
                addressLongitudeInput.value = '';
                // Esconde mensagens de erro específicas da instalação
                newEquipmentNameError.classList.add('hidden');
                newEquipmentReferenceError.classList.add('hidden');
                addressLogradouroError.classList.add('hidden');
                addressNumeroError.classList.add('hidden');
                addressBairroError.classList.add('hidden');
                addressCepError.classList.add('hidden');
            }
            console.log('Cidade selecionada (ID):', selectedCityId, 'Nome:', selectedCityName);
        }

        // Função para carregar e filtrar equipamentos (para o fluxo de manutenção)
        async function loadEquipamentos(cityId, searchTerm = '') {
            equipmentSelect.innerHTML = '';
            loadingEquipmentMessage.classList.remove('hidden');
            hideMessage(equipmentErrorMessage);
            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');

            console.log('Carregando equipamentos para cidade ID:', cityId, 'Termo de busca:', searchTerm);

            try {
                const url = `get_equipamentos.php?city_id=${encodeURIComponent(cityId)}&search_term=${encodeURIComponent(searchTerm)}`;
                const response = await fetch(url);
                const data = await response.json();

                console.log('Resposta de get_equipamentos.php:', data);

                loadingEquipmentMessage.classList.add('hidden');

                if (data.success && data.equipamentos.length > 0) {
                    data.equipamentos.forEach(equip => {
                        const option = document.createElement('option');
                        option.value = equip.id_equipamento;
                        option.textContent = `${equip.nome_equip} (Ref: ${equip.referencia_equip})`;
                        option.dataset.equipmentName = equip.nome_equip;
                        option.dataset.equipmentRef = equip.referencia_equip;
                        equipmentSelect.appendChild(option);
                    });
                } else {
                    if (searchTerm !== '' || data.equipamentos.length === 0) {
                        equipmentErrorMessage.textContent = data.message || 'Nenhum equipamento encontrado para esta cidade ou termo de busca.';
                        equipmentErrorMessage.classList.remove('hidden');
                    } else {
                        hideMessage(equipmentErrorMessage);
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar equipamentos:', error);
                loadingEquipmentMessage.classList.add('hidden');
                equipmentErrorMessage.textContent = 'Erro ao carregar equipamentos. Tente novamente.';
                equipmentErrorMessage.classList.remove('hidden');
            }
        }

        // Função para lidar com a seleção de um equipamento (para o fluxo de manutenção)
        equipmentSelect.addEventListener('change', () => {
            const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex];
            if (selectedOption) {
                selectedEquipmentId = selectedOption.value;
                selectedEquipmentName = selectedOption.textContent;
                problemDescriptionSection.style.display = 'flex';
                problemDescriptionInput.focus();
                equipmentSelectionErrorMessage.classList.add('hidden');
                problemDescriptionErrorMessage.classList.add('hidden');
            } else {
                problemDescriptionSection.style.display = 'none';
                problemDescriptionInput.value = '';
                selectedEquipmentId = null;
                selectedEquipmentName = '';
                problemDescriptionErrorMessage.classList.add('hidden');
            }
        });

        // Event listener para o foco na caixa de seleção de equipamentos (para o fluxo de manutenção)
        equipmentSelect.addEventListener('focus', () => {
            problemDescriptionSection.style.display = 'none';
            problemDescriptionInput.value = '';
            equipmentSelect.selectedIndex = -1;
            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');
        });

        // Função para voltar para a seleção de cidades
        function goBackToCitySelection() {
            // Esconde todas as seções e limpa campos relevantes
            equipmentSelectionSection.style.display = 'none';
            installEquipmentAndAddressSection.style.display = 'none';
            problemDescriptionSection.style.display = 'none';
            equipmentSearchInput.value = '';
            equipmentSelect.innerHTML = '';
            problemDescriptionInput.value = '';
            newEquipmentNameInput.value = '';
            newEquipmentReferenceInput.value = '';
            addressLogradouroInput.value = '';
            addressNumeroInput.value = '';
            addressBairroInput.value = '';
            addressCepInput.value = '';
            addressComplementoInput.value = '';
            addressLatitudeInput.value = '';
            addressLongitudeInput.value = '';

            // Esconde todas as mensagens de erro
            hideMessage(loadingEquipmentMessage);
            hideMessage(equipmentErrorMessage);
            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');
            newEquipmentNameError.classList.add('hidden');
            newEquipmentReferenceError.classList.add('hidden');
            addressLogradouroError.classList.add('hidden');
            addressNumeroError.classList.add('hidden');
            addressBairroError.classList.add('hidden');
            addressCepError.classList.add('hidden');

            // Reseta variáveis de estado
            selectedCityId = null;
            selectedCityName = '';
            selectedEquipmentId = null;
            selectedEquipmentName = '';
            selectedProblemDescription = '';
            // currentMaintenanceType e currentRepairStatus não são resetados aqui
            // currentFlow não é resetado aqui
            newlyCreatedEquipmentId = null;
            newlyCreatedAddressId = null;

            // Limpar variáveis temporárias
            tempNewEquipmentName = '';
            tempNewEquipmentRef = '';
            tempAddressLogradouro = '';
            tempAddressNumero = '';
            tempAddressBairro = '';
            tempAddressCep = '';
            tempAddressComplemento = '';
            tempAddressLatitude = null;
            tempAddressLongitude = null;

            // Mostra a seção de seleção de cidade
            citySelectionSection.style.display = 'block';
        }

        // Event listener para o input de busca de equipamentos (para o fluxo de manutenção)
        equipmentSearchInput.addEventListener('input', () => {
            if (selectedCityId !== null && currentFlow === 'maintenance') {
                problemDescriptionSection.style.display = 'none';
                problemDescriptionInput.value = '';
                equipmentSelect.selectedIndex = -1;
                equipmentSelectionErrorMessage.classList.add('hidden');
                problemDescriptionErrorMessage.classList.add('hidden');

                loadEquipamentos(selectedCityId, equipmentSearchInput.value);
            }
        });

        // Event listener para o botão "Confirmar Seleção" (para o fluxo de manutenção)
        confirmEquipmentSelectionBtn.addEventListener('click', () => {
            const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex];
            selectedProblemDescription = problemDescriptionInput.value.trim();

            equipmentSelectionErrorMessage.classList.add('hidden');
            problemDescriptionErrorMessage.classList.add('hidden');

            if (!selectedOption) {
                equipmentSelectionErrorMessage.textContent = 'Por favor, selecione um equipamento.';
                equipmentSelectionErrorMessage.classList.remove('hidden');
                return;
            }

            if (selectedProblemDescription === '') {
                problemDescriptionErrorMessage.textContent = 'Por favor, informe o problema para o reparo.';
                problemDescriptionErrorMessage.classList.remove('hidden');
                problemDescriptionInput.focus();
                return;
            }

            // Preenche o modal de confirmação com os dados coletados e os parâmetros de manutenção
            confirmCityNameSpan.textContent = selectedCityName;
            confirmEquipmentNameSpan.textContent = selectedEquipmentName;
            confirmProblemDescriptionSpan.textContent = selectedProblemDescription;
            confirmMaintenanceTypeSpan.textContent = currentMaintenanceType.charAt(0).toUpperCase() + currentMaintenanceType.slice(1);
            confirmRepairStatusSpan.textContent = currentRepairStatus.charAt(0).toUpperCase() + currentRepairStatus.slice(1);

            // Esconde os detalhes de instalação se for um fluxo de manutenção
            installConfirmationDetails.classList.add('hidden');
            // Garante que o campo de problema esteja visível
            confirmProblemDescriptionSpan.closest('p').classList.remove('hidden');


            openConfirmationModal();
        });

        // Event listener para o botão "Cadastrar Equipamento e Endereço" (para o fluxo de instalação)
        confirmInstallEquipmentBtn.addEventListener('click', processInstallation);

        // Função para processar o cadastro de novo equipamento e endereço (apenas valida e prepara dados)
        async function processInstallation() {
            // Resetar mensagens de erro específicas da instalação
            newEquipmentNameError.classList.add('hidden');
            newEquipmentReferenceError.classList.add('hidden');
            addressLogradouroError.classList.add('hidden');
            addressNumeroError.classList.add('hidden');
            addressBairroError.classList.add('hidden');
            addressCepError.classList.add('hidden');

            // Validação dos campos
            const newEquipmentName = newEquipmentNameInput.value.trim();
            const newEquipmentRef = newEquipmentReferenceInput.value.trim();
            const addressLogradouro = addressLogradouroInput.value.trim();
            const addressNumero = addressNumeroInput.value.trim();
            const addressBairro = addressBairroInput.value.trim();
            const addressCep = addressCepInput.value.trim();
            const addressComplemento = addressComplementoInput.value.trim();
            const addressLatitude = addressLatitudeInput.value.trim();
            const addressLongitude = addressLongitudeInput.value.trim();

            let hasError = false;

            if (newEquipmentName === '') {
                showMessage(newEquipmentNameError, 'Por favor, informe o nome do equipamento.');
                hasError = true;
            }
            if (newEquipmentRef === '') {
                showMessage(newEquipmentReferenceError, 'Por favor, informe a referência do equipamento.');
                hasError = true;
            }
            if (addressLogradouro === '') {
                showMessage(addressLogradouroError, 'Por favor, informe o logradouro.');
                hasError = true;
            }
            if (addressNumero === '') {
                showMessage(addressNumeroError, 'Por favor, informe o número.');
                hasError = true;
            }
            if (addressBairro === '') {
                showMessage(addressBairroError, 'Por favor, informe o bairro.');
                hasError = true;
            }
            if (addressCep === '') {
                showMessage(addressCepError, 'Por favor, informe o CEP.');
                hasError = true;
            }

            if (hasError) {
                return; // Interrompe se houver erros de validação
            }

            // Armazenar os dados temporariamente
            tempNewEquipmentName = newEquipmentName;
            tempNewEquipmentRef = newEquipmentRef;
            tempAddressLogradouro = addressLogradouro;
            tempAddressNumero = addressNumero;
            tempAddressBairro = addressBairro;
            tempAddressCep = addressCep;
            tempAddressComplemento = addressComplemento;
            tempAddressLatitude = addressLatitude !== '' ? parseFloat(addressLatitude) : null;
            tempAddressLongitude = addressLongitude !== '' ? parseFloat(addressLongitude) : null;

            // Preenche o modal de confirmação com os dados da instalação
            confirmCityNameSpan.textContent = selectedCityName;
            confirmEquipmentNameSpan.textContent = tempNewEquipmentName + ' (Ref: ' + tempNewEquipmentRef + ')';
            confirmProblemDescriptionSpan.textContent = 'Instalação de novo equipamento';
            confirmMaintenanceTypeSpan.textContent = 'Instalação';
            confirmRepairStatusSpan.textContent = 'Pendente'; // Alterado para Pendente aqui

            confirmNewEquipmentNameSpan.textContent = tempNewEquipmentName;
            confirmNewEquipmentRefSpan.textContent = tempNewEquipmentRef;
            confirmAddressLogradouroSpan.textContent = tempAddressLogradouro;
            confirmAddressNumeroSpan.textContent = tempAddressNumero;
            confirmAddressBairroSpan.textContent = tempAddressBairro;
            confirmAddressCepSpan.textContent = tempAddressCep;
            confirmAddressComplementoSpan.textContent = tempAddressComplemento || 'N/A';
            confirmAddressLatitudeSpan.textContent = tempAddressLatitude !== null ? tempAddressLatitude : 'N/A';
            confirmAddressLongitudeSpan.textContent = tempAddressLongitude !== null ? tempAddressLongitude : 'N/A';

            // Mostra a seção de detalhes de instalação
            installConfirmationDetails.classList.remove('hidden');
            // Esconde os campos de problema para o fluxo de instalação
            confirmProblemDescriptionSpan.closest('p').classList.add('hidden');

            openConfirmationModal(); // Abre o modal de confirmação final
        }


        // Funções para o novo modal de confirmação
        function openConfirmationModal() {
            confirmationModal.classList.add('is-active');
            // Garante que os botões e a mensagem estejam no estado inicial ao abrir o modal
            confirmSaveButton.classList.remove('hidden');
            cancelSaveButton.classList.remove('hidden');
            confirmSaveButton.disabled = false;
            cancelSaveButton.disabled = false;
            confirmSpinner.classList.add('hidden');
            hideMessage(confirmMessage);
        }

        function closeConfirmationModal() {
            confirmationModal.classList.remove('is-active');
            // Garante que a seção de detalhes de instalação esteja oculta ao fechar o modal
            installConfirmationDetails.classList.add('hidden');
            // Garante que o campo de problema esteja visível novamente para outros fluxos
            confirmProblemDescriptionSpan.closest('p').classList.remove('hidden');
            // Garante que os botões estejam visíveis e habilitados para a próxima vez
            confirmSaveButton.classList.remove('hidden');
            cancelSaveButton.classList.remove('hidden');
            confirmSaveButton.disabled = false;
            cancelSaveButton.disabled = false;
            confirmSpinner.classList.add('hidden');
            hideMessage(confirmMessage);
        }

        // Função para controlar o estado dos botões de confirmação e spinner
        function toggleConfirmationButtons(showSpinner, msg = '', type = '') {
            if (showSpinner) {
                confirmSpinner.classList.remove('hidden');
                confirmSaveButton.disabled = true;
                cancelSaveButton.disabled = true;
                confirmMessage.classList.remove('hidden');
                confirmMessage.textContent = msg;
                confirmMessage.className = `confirmation-message ${type}`;
            } else {
                confirmSpinner.classList.add('hidden');
                confirmSaveButton.disabled = false;
                cancelSaveButton.disabled = false;
                confirmMessage.textContent = msg;
                confirmMessage.className = `confirmation-message ${type}`;
                confirmMessage.classList.remove('hidden');
            }
        }


        // Função para confirmar e salvar a manutenção no banco de dados (chamada do modal de confirmação)
        confirmSaveButton.addEventListener('click', async function() {
            toggleConfirmationButtons(true, 'Registrando...', ''); // Inicia o carregamento

            if (currentFlow === 'installation') {
                console.log('Tentando salvar equipamento, endereço e manutenção de instalação...');
                try {
                    // 1. Salvar Endereço
                    console.log('Salvando endereço...');
                    const addressResponse = await fetch('save_endereco.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            logradouro: tempAddressLogradouro,
                            numero: tempAddressNumero,
                            bairro: tempAddressBairro,
                            cep: tempAddressCep,
                            complemento: tempAddressComplemento,
                            latitude: tempAddressLatitude,
                            longitude: tempAddressLongitude
                        })
                    });
                    const addressData = await addressResponse.json();
                    console.log('Resposta save_endereco.php:', addressData);

                    if (!addressData.success) {
                        toggleConfirmationButtons(false, 'Erro ao cadastrar endereço: ' + (addressData.message || 'Erro desconhecido.'), 'error');
                        return;
                    }
                    newlyCreatedAddressId = addressData.id_endereco;

                    // 2. Salvar Equipamento
                    console.log('Salvando equipamento...');
                    const equipmentResponse = await fetch('save_equipamento.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            nome_equip: tempNewEquipmentName,
                            referencia_equip: tempNewEquipmentRef,
                            id_cidade: selectedCityId,
                            id_endereco: newlyCreatedAddressId
                        })
                    });
                    const equipmentData = await equipmentResponse.json();
                    console.log('Resposta save_equipamento.php:', equipmentData);

                    if (!equipmentData.success) {
                        toggleConfirmationButtons(false, 'Erro ao cadastrar equipamento: ' + (equipmentData.message || 'Erro desconhecido.'), 'error');
                        return;
                    }
                    newlyCreatedEquipmentId = equipmentData.id_equipamento;

                    // 3. Salvar Manutenção (Instalação)
                    console.log('Salvando manutenção de instalação...');
                    const maintenanceResponse = await fetch('save_manutencao.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            city_id: selectedCityId,
                            equipment_id: newlyCreatedEquipmentId,
                            problem_description: 'Instalação de novo equipamento', // Descrição padrão para instalação
                            tipo_manutencao: 'instalação',
                            status_reparo: 'pendente' // Alterado para Pendente aqui
                        })
                    });
                    const maintenanceData = await maintenanceResponse.json();
                    console.log('Resposta save_manutencao.php (instalação):', maintenanceData);

                    if (maintenanceData.success) {
                        toggleConfirmationButtons(false, 'Manutenção cadastrada com sucesso!', 'success');
                        confirmSaveButton.classList.add('hidden'); // Oculta o botão Confirmar
                        cancelSaveButton.classList.add('hidden'); // Oculta o botão Cancelar (se ainda visível)
                        setTimeout(() => {
                            closeConfirmationModal();
                            closeCadastroManutencaoModal(); // Fecha o modal principal de cadastro
                        }, 1500);
                    } else {
                        toggleConfirmationButtons(false, 'Erro ao registrar manutenção de instalação: ' + (maintenanceData.message || 'Erro desconhecido.'), 'error');
                    }

                } catch (error) {
                    console.error('Erro no processo de instalação:', error);
                    toggleConfirmationButtons(false, 'Ocorreu um erro ao tentar registrar o equipamento e a instalação. Tente novamente.', 'error');
                }
            } else {
                // Lógica de salvamento para os fluxos de Matriz Manutenção e Controle de Ocorrência
                console.log('Tentando salvar manutenção...');
                console.log('Dados a serem enviados:', {
                    city_id: selectedCityId,
                    equipment_id: selectedEquipmentId,
                    problem_description: selectedProblemDescription,
                    tipo_manutencao: currentMaintenanceType,
                    status_reparo: currentRepairStatus
                });

                try {
                    const response = await fetch('save_manutencao.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            city_id: selectedCityId,
                            equipment_id: selectedEquipmentId,
                            problem_description: selectedProblemDescription,
                            tipo_manutencao: currentMaintenanceType,
                            status_reparo: currentRepairStatus
                        })
                    });

                    const data = await response.json();
                    console.log('Resposta do servidor:', data);

                    if (data.success) {
                        toggleConfirmationButtons(false, 'Manutenção cadastrada com sucesso!', 'success');
                        confirmSaveButton.classList.add('hidden'); // Oculta o botão Confirmar
                        cancelSaveButton.classList.add('hidden'); // Oculta o botão Cancelar (se ainda visível)
                        setTimeout(() => {
                            closeConfirmationModal();
                            closeCadastroManutencaoModal(); // Fecha o modal principal de cadastro
                        }, 1500);
                    } else {
                        toggleConfirmationButtons(false, 'Erro ao registrar manutenção: ' + (data.message || 'Erro desconhecido.'), 'error');
                    }
                } catch (error) {
                    console.error('Erro na requisição de salvamento:', error);
                    toggleConfirmationButtons(false, 'Ocorreu um erro ao tentar registrar a manutenção. Tente novamente.', 'error');
                }
            }
        });

        // Função para cancelar a confirmação
        function cancelSaveManutencao() {
            console.log('Confirmação de manutenção cancelada.');
            closeConfirmationModal();
        }

        // Adiciona os event listeners aos botões
        if (matrizManutencaoBtn) {
            matrizManutencaoBtn.addEventListener('click', () => openMaintenanceModal('corretiva', 'pendente', 'maintenance'));
        }
        if (controleOcorrenciaBtn) {
            controleOcorrenciaBtn.addEventListener('click', () => openMaintenanceModal('preditiva', 'concluido', 'maintenance'));
        }
        if (instalarEquipamentoBtn) {
            instalarEquipamentoBtn.addEventListener('click', () => openMaintenanceModal('instalação', 'pendente', 'installation')); // Alterado para 'pendente'
        }
    </script>
</body>

</html>
