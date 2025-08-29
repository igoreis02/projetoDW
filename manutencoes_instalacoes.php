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

if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'entregador') {
    header('Location: lista_pedidos_em_entrega.html');
    exit();
}

$redefinir_senha_obrigatoria = isset($_SESSION['redefinir_senha_obrigatoria']) && $_SESSION['redefinir_senha_obrigatoria'] === true;

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <title>Ocorrências e Instalações</title>
    <style>
        /* Estilos do card e layout geral */
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
            content: none;
        }

        h2 {
            font-size: 2em;
            color: var(--cor-principal);
            margin-bottom: 30px;
        }

        .page-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            padding: 20px 0;
        }

        .page-button {
            padding: 25px;
            font-size: 1.3em;
            color: white;
            background-color: #112058;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .page-button:hover {
            background-color: #09143fff;
            transform: translateY(-5px);
        }

        .page-button i {
            margin-right: 15px;
            font-size: 1.5em;
        }

        .voltar-btn {
            display: block;
            width: 50%;
            padding: 15px;
            margin-top: 30px;
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

        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        /* Estilos do Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 1rem;
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
        }

        /* Estilos das seções do modal */
        .city-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .city-button {
            padding: 12px 20px;
            font-size: 1.1em;
            color: white;
            background-color: #112058;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
        }

        .equipment-selection-container,
        .install-equipment-section {
            display: none;
            flex-direction: column;
            gap: 15px;
            margin-top: 20px;
        }

        .equipment-selection-container input,
        .equipment-selection-container select,
        .equipment-selection-container textarea,
        .install-equipment-section input,
        .install-equipment-section select,
        .install-equipment-section textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .equipment-selection-container select {
            min-height: 100px;
        }

        .problem-description-container,
        .choice-container {
            display: none;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .problem-description-container label,
        .choice-container label {
            font-weight: bold;
            text-align: left;
        }

        .choice-buttons {
            display: flex;
            gap: 10px;
        }

        .choice-buttons button {
            flex: 1;
            font-size: 1em;
            padding: 15px;
        }

        .hidden {
            display: none !important;
        }

        .choice-buttons .selected {
            background-color: #09143fff !important;
            transform: translateY(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) inset;
        }

        .confirmation-details p {
            text-align: left;
        }

        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .confirmation-buttons button {
            flex: 1;
            max-width: 150px;
        }

        .confirm-button{
            
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 1em;
        }
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
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .message,
        .selection-error-message {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
        }

        .message.error,
        .selection-error-message {
            background-color: #f8d7da;
            /* Caixa vermelha clara */
            color: #721c24;
            /* Letras vermelhas escuras */
            border: 1px solid #f5c6cb;
            /* Borda vermelha */
            font-weight: bold;
            /* Letras em negrito */
        }

        .message.success {
            background-color: #d4edda;
            /* Caixa verde clara */
            color: #155724;
            /* Letras verdes escuras */
            border: 1px solid #c3e6cb;
            /* Borda verde */
            font-weight: bold;
            /* Letras em negrito */
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Manutenções e Instalações</h2>

        <div class="page-buttons-container">
            <button id="matrizManutencaoBtn" class="page-button"><i class="fas fa-cogs"></i> Matriz Técnica</button>
            <button id="matrizSemaforicaBtn" class="page-button"><i class="fas fa-traffic-light"></i> Matriz Semafórica</button>
            <button id="controleOcorrenciaBtn" class="page-button"><i class="fas fa-clipboard-list"></i> Controle de Ocorrência</button>
            <button id="instalarEquipamentoBtn" class="page-button"><i class="fas fa-tools"></i> Instalar equipamento</button>
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
            <h3 id="modalTitle">Cadastrar Operação</h3>

            <div id="citySelectionSection">
                <p>Selecione a cidade:</p>
                <div id="cityButtonsContainer" class="city-buttons-container">
                    <p id="loadingCitiesMessage">Carregando...</p>
                </div>
                <p id="cityErrorMessage" class="message error hidden"></p>
            </div>

            <div id="equipmentSelectionSection" class="equipment-selection-container">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <p>Selecione o equipamento:</p>
                <input type="text" id="equipmentSearchInput" placeholder="Digite para filtrar...">
                <select id="equipmentSelect" size="5"></select>
                <p id="loadingEquipmentMessage" class="hidden"></p>
                <p id="equipmentErrorMessage" class="message error hidden"></p>

                <div id="problemDescriptionSection" class="problem-description-container">
                    <label for="problemDescription">Descrição do problema:</label>
                    <textarea id="problemDescription" rows="4" placeholder="Descreva o problema..."></textarea>
                </div>

                <div id="realizadoPorSection" class="choice-container">
                    <label>Realizado por:</label>
                    <div class="choice-buttons">
                        <button id="btnProcessamento" class="page-button">Processamento</button>
                        <button id="btnProvedor" class="page-button">Provedor</button>
                    </div>
                </div>

                <div id="reparoConcluidoSection" class="choice-container">
                    <label>Concluído o reparo?</label>
                    <div class="choice-buttons">
                        <button id="btnReparoSim" class="page-button">Sim</button>
                        <button id="btnReparoNao" class="page-button">Não</button>
                    </div>
                </div>
                
                <div id="tecnicoInLocoSection" class="choice-container">
                    <label>Precisa de técnico In Loco:</label>
                    <div class="choice-buttons">
                        <button id="btnTecnicoSim" class="page-button">Sim</button>
                        <button id="btnTecnicoNao" class="page-button">Não</button>
                    </div>
                </div>

                <div id="repairDescriptionSection" class="problem-description-container">
                    <label for="repairDescription">Descrição do reparo:</label>
                    <textarea id="repairDescription" rows="4" placeholder="Descreva o reparo realizado..."></textarea>
                </div>
                
                <span id="equipmentSelectionErrorMessage" class="selection-error-message hidden"></span>
                <button id="confirmEquipmentSelection" class="page-button" style="margin-top: 1rem;">
                    Avançar <span id="selectionSpinner" class="loading-spinner hidden"></span>
                </button>
            </div>

            <div id="installEquipmentAndAddressSection" class="install-equipment-section">
                <button class="back-button" onclick="goBackToCitySelection()">&larr;</button>
                <h4>Dados do Novo Equipamento</h4>

                <label for="newEquipmentType">Tipo de Equipamento:</label>
                <select id="newEquipmentType">
                    <option value="">-- Selecione o Tipo --</option>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LOMBADA</option>
                </select>

                <label for="newEquipmentName">Nome / Identificador:</label>
                <input type="text" id="newEquipmentName" placeholder="Ex: MT300, D22">

                <label for="newEquipmentReference">Referência / Local:</label>
                <input type="text" id="newEquipmentReference" placeholder="Ex: Próximo à Escola">

                <div id="quantitySection" class="hidden" style="display: flex; flex-direction: column; gap: 15px;">
                    <label for="newEquipmentQuantity">Quantidade de Faixas:</label>
                    <input type="number" id="newEquipmentQuantity" placeholder="Ex: 2" min="1">
                </div>

                <h5>Dados do Endereço:</h5>
                <label for="addressLogradouro">Logradouro:</label>
                <input type="text" id="addressLogradouro" placeholder="Ex: Av. Principal, Qd. 10">

                <label for="addressBairro">Bairro:</label>
                <input type="text" id="addressBairro" placeholder="Ex: Centro">

                <label for="addressCep">CEP:</label>
                <input type="text" id="addressCep" placeholder="Ex: 12345-678">

                <label for="addressLatitude">Latitude (Opcional):</label>
                <input type="number" step="any" id="addressLatitude" placeholder="-16.75854">

                <label for="addressLongitude">Longitude (Opcional):</label>
                <input type="number" step="any" id="addressLongitude" placeholder="-49.25569">

                <label for="installationNotes">Observação da Instalação (Opcional):</label>
                <textarea id="installationNotes" rows="3" placeholder="Informação adicional..."></textarea>

                <button id="confirmInstallEquipment" class="page-button" style="margin-top: 1rem;">Avançar para Confirmação</button>
            </div>
        </div>
    </div>

    <div id="pendingMaintenanceModal" class="modal">
        <div class="modal-content">
            <h3>Aviso de Manutenção Pendente</h3>
            <p>
                Esse equipamento já tem manutenção cadastrada.
                <br><br>
                <strong>Deseja adicionar esse problema à ocorrência existente?</strong>
            </p>
            <div class="confirmation-buttons">
                <button class="confirm-button page-button" id="confirmAppendProblem">Sim</button>
                <button class="cancel-button page-button" id="cancelAppendProblem">Não</button>
            </div>
        </div>
    </div>

   <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeConfirmationModal()">&times;</span>
            <h3 id="modalTitleConfirm">Confirmação da Operação</h3>
            <div class="confirmation-details">
                <p><strong>Cidade:</strong> <span id="confirmCityName"></span></p>

                <div id="maintenanceConfirmationDetails" class="hidden">
                    <p><strong>Equipamento:</strong> <span id="confirmEquipmentName"></span></p>
                    <p><strong>Problema:</strong> <span id="confirmProblemDescription"></span></p>
                    <p id="confirmRepairDescriptionContainer" class="hidden"><strong>Reparo Realizado:</strong> <span id="confirmRepairDescription"></span></p>
                </div>

                <div id="installConfirmationDetails" class="hidden">
                    <h4>Detalhes do Novo Equipamento:</h4>
                    <p><strong>Tipo:</strong> <span id="confirmEquipmentType"></span></p>
                    <p><strong>Nome:</strong> <span id="confirmNewEquipmentName"></span></p>
                    <p><strong>Referência:</strong> <span id="confirmNewEquipmentRef"></span></p>
                    
                    <p id="confirmQuantityContainer" class="hidden"><strong>Qtd. Faixas:</strong> <span id="confirmEquipmentQuantity"></span></p>
                    <p><strong>Logradouro:</strong> <span id="confirmAddressLogradouro"></span></p>
                    <p><strong>Bairro:</strong> <span id="confirmAddressBairro"></span></p>
                    <p><strong>CEP:</strong> <span id="confirmAddressCep"></span></p>
                    <p><strong>Observação:</strong> <span id="confirmInstallationNotes"></span></p>
                </div>

                <div id="confirmProviderContainer" class="hidden">
                    <p><strong>Problema:</strong> <span id="confirmProviderProblem"></span></p>
                    <p><strong>Ação:</strong> <span>Atribuído ao provedor <strong id="confirmProviderName"></strong>, aguardando manutenção.</span></p>
                </div>

                <p><strong>Tipo de Operação:</strong> <span id="confirmMaintenanceType"></span></p>
                <p><strong>Status Inicial:</strong> <span id="confirmRepairStatus"></span></p>
            </div>
            <div class="confirmation-buttons">
                <button class="confirm-button page-button" id="confirmSaveButton">
                    Confirmar <span id="confirmSpinner" class="loading-spinner hidden"></span>
                </button>
                <button class="cancel-button page-button" id="cancelSaveButton" onclick="cancelSaveManutencao()">Cancelar</button>
            </div>
            <p id="confirmMessage" class="message hidden"></p>
        </div>
    </div>

    
    <script src="js/manutencoes_instalacoes.js"></script>
</body>

</html>