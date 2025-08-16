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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            transition: background-color: 0.3s ease, transform 0.2s ease;
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

        /* Estilo para os ícones dentro dos botões */
        .page-button i {
            margin-right: 15px;
            font-size: 1.5em;
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
            transition: background-color: 0.3s ease;
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
            transition: background-color: 0.3s ease;
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
            text-align: center; /* Centralizado */
            width: 100%;
        }

        /* Classe para esconder elementos via JS */
        .hidden {
            display: none !important; /* Adicionado !important para garantir que sobrescreva outros displays */
        }

        /* === NOVOS ESTILOS PARA O FORMULÁRIO DE INSTALAÇÃO === */
        .install-equipment-section {
            display: none; /* Escondido por padrão */
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
            text-align: left;
        }

        .install-equipment-section input[type="text"],
        .install-equipment-section input[type="number"],
        .install-equipment-section select,
        .install-equipment-section textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        .install-equipment-section label {
            font-weight: bold;
            margin-bottom: -10px;
            display: block;
        }
        /* === FIM DOS NOVOS ESTILOS === */

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

    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Manutenções e Instalações</h2>

        <div class="page-buttons-container">
            <button id="matrizManutencaoBtn" class="page-button">
                <i class="fas fa-cogs"></i> Matriz Técnica
            </button>
            <button id="controleOcorrenciaBtn" class="page-button">
                <i class="fas fa-clipboard-list"></i> Controle de Ocorrência
            </button>
            <button id="instalarEquipamentoBtn" class="page-button">
                <i class="fas fa-tools"></i> Instalar equipamento
            </button>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <?php if ($redefinir_senha_obrigatoria): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.location.href = 'index.html';
        });
    </script>
    <?php endif; ?>

    <div id="cadastroManutencaoModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeCadastroManutencaoModal()">&times;</span>
            <h3 id="modalTitle" class="text-xl font-bold mb-4 text-gray-800">Cadastrar Operação</h3>

            <div id="citySelectionSection">
                <p class="mb-4 text-gray-700">Selecione a cidade para a nova operação:</p>
                <div id="cityButtonsContainer" class="city-buttons-container">
                    <p id="loadingCitiesMessage">Carregando cidades...</p>
                </div>
                <p id="cityErrorMessage" class="message error hidden"></p>
            </div>

            <div id="equipmentSelectionSection" class="equipment-selection-container">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <p class="mb-4 text-gray-700">Selecione o equipamento:</p>
                <input type="text" id="equipmentSearchInput" placeholder="Digite o nome do equipamento para filtrar...">
                <select id="equipmentSelect" size="5"></select>
                <p id="loadingEquipmentMessage" class="hidden"></p>
                <p id="equipmentErrorMessage" class="message error hidden"></p>
                
                <div id="problemDescriptionSection" class="problem-description-container">
                    <label for="problemDescription">Descrição do problema:</label>
                    <textarea id="problemDescription" rows="4" placeholder="Descreva o problema aqui..."></textarea>
                    <span id="problemDescriptionErrorMessage" class="selection-error-message hidden"></span>
                </div>

                <div id="repairDescriptionSection" class="problem-description-container">
                    <label for="repairDescription">Descrição do reparo:</label>
                    <textarea id="repairDescription" rows="4" placeholder="Descreva o reparo realizado aqui..."></textarea>
                    <span id="repairDescriptionErrorMessage" class="selection-error-message hidden"></span>
                </div>
                
                <span id="equipmentSelectionErrorMessage" class="selection-error-message hidden"></span>
                <button id="confirmEquipmentSelection" class="page-button" style="margin-top: 1rem;">
                    Confirmar Seleção
                    <span id="selectionSpinner" class="loading-spinner hidden"></span>
                </button>
            </div>

            <div id="installEquipmentAndAddressSection" class="install-equipment-section">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <h4 class="text-lg font-bold mb-3 text-gray-800">Dados do Novo Equipamento</h4>
                
                <label for="newEquipmentType">Tipo de Equipamento:</label>
                <select id="newEquipmentType">
                    <option value="">-- Selecione o Tipo --</option>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LOMBADA</option>
                </select>
                <span id="newEquipmentTypeError" class="selection-error-message hidden"></span>

                <label for="newEquipmentName">Nome / Identificador:</label>
                <input type="text" id="newEquipmentName" placeholder="Ex: MT300, D22">
                <span id="newEquipmentNameError" class="selection-error-message hidden"></span>

                <label for="newEquipmentReference">Referência / Local:</label>
                <input type="text" id="newEquipmentReference" placeholder="Ex: Próximo à Escola, Praça Central">
                <span id="newEquipmentReferenceError" class="selection-error-message hidden"></span>
                
                <div id="quantitySection" class="hidden" style="display: flex; flex-direction: column; gap: 15px;">
                    <label for="newEquipmentQuantity">Quantidade de Faixas:</label>
                    <input type="number" id="newEquipmentQuantity" placeholder="Ex: 2" min="1">
                    <span id="newEquipmentQuantityError" class="selection-error-message hidden"></span>
                </div>

                <h5 class="text-md font-bold mt-4 mb-2 text-gray-700">Dados do Endereço:</h5>
                <label for="addressLogradouro">Logradouro:</label>
                <input type="text" id="addressLogradouro" placeholder="Ex: Av. Principal, Qd. 10 Lt. 5">
                <span id="addressLogradouroError" class="selection-error-message hidden"></span>

                <label for="addressBairro">Bairro:</label>
                <input type="text" id="addressBairro" placeholder="Ex: Centro">
                <span id="addressBairroError" class="selection-error-message hidden"></span>

                <label for="addressCep">CEP:</label>
                <input type="text" id="addressCep" placeholder="Ex: 12345-678">
                <span id="addressCepError" class="selection-error-message hidden"></span>

                <label for="addressLatitude">Latitude (Opcional):</label>
                <input type="number" step="any" id="addressLatitude" placeholder="-23.55052">

                <label for="addressLongitude">Longitude (Opcional):</label>
                <input type="number" step="any" id="addressLongitude" placeholder="-46.63330">

                <label for="installationNotes">Observação da Instalação (Opcional):</label>
                <textarea id="installationNotes" rows="3" placeholder="Qualquer informação adicional sobre a instalação..."></textarea>

                <button id="confirmInstallEquipment" class="page-button mt-4">Avançar para Confirmação</button>
            </div>
            </div>
    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConfirmationModal()">&times;</span>
            <h3 class="text-xl font-bold mb-4 text-gray-800">Confirmação da Operação</h3>
            <div class="confirmation-details">
                <p><strong>Cidade:</strong> <span id="confirmCityName"></span></p>

                <div id="maintenanceConfirmationDetails" class="hidden">
                    <p><strong>Equipamento:</strong> <span id="confirmEquipmentName"></span></p>
                    <p><strong>Problema:</strong> <span id="confirmProblemDescription"></span></p>
                    <p id="confirmRepairDescriptionContainer" class="hidden"><strong>Reparo Realizado:</strong> <span id="confirmRepairDescription"></span></p>
                </div>
                
                <div id="installConfirmationDetails" class="hidden">
                    <h4 class="text-md font-bold mt-4 mb-2 text-gray-700">Detalhes do Novo Equipamento:</h4>
                    <p><strong>Tipo:</strong> <span id="confirmEquipmentType"></span></p>
                    <p><strong>Nome:</strong> <span id="confirmNewEquipmentName"></span></p>
                    <p><strong>Referência:</strong> <span id="confirmNewEquipmentRef"></span></p>
                    <p id="confirmQuantityContainer" class="hidden"><strong>Qtd. Faixas:</strong> <span id="confirmEquipmentQuantity"></span></p>
                    <p><strong>Logradouro:</strong> <span id="confirmAddressLogradouro"></span></p>
                    <p><strong>Bairro:</strong> <span id="confirmAddressBairro"></span></p>
                    <p><strong>CEP:</strong> <span id="confirmAddressCep"></span></p>
                    <p><strong>Observação:</strong> <span id="confirmInstallationNotes"></span></p>
                </div>

                <p><strong>Tipo de Operação:</strong> <span id="confirmMaintenanceType"></span></p>
                <p><strong>Status Inicial:</strong> <span id="confirmRepairStatus"></span></p>
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
    <script src="js/manutencoes_instalacoes.js"></script>
</body>
</html>