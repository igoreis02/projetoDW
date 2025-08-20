<?php
session_start();
// Ativa a exibição de erros para depuração (REMOVER EM PRODUÇÃO)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

require_once 'conexao_bd.php';

$cities = []; // Array para armazenar as cidades

try {
    // Consulta SQL para obter as cidades para os dropdowns
    $sql_cidades = "SELECT id_cidade, nome FROM cidades ORDER BY nome ASC";
    $result_cidades = $conn->query($sql_cidades);

    if ($result_cidades === false) {
        throw new Exception("Erro ao executar a consulta de cidades: " . $conn->error);
    }

    if ($result_cidades->num_rows > 0) {
        while ($row = $result_cidades->fetch_assoc()) {
            $cities[] = $row;
        }
    }

} catch (Exception $e) {
    error_log("Erro em info_equipamentos.php: " . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Gerenciar Equipamentos</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 2rem 0;
        }

        .card {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1000px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .card h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }

        .cabecalho {
            width: 90%;
            max-width: 1000px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .conteudo-cabecalho {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .titulo-cabecalho {
            flex-grow: 1;
            text-align: center;
            margin: 0;
        }

        .botao-voltar {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 50%;
            color: white;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 2rem;
            left: 5%;
        }

        .botao-voltar:hover {
            background-color: var(--cor-secundaria);
        }

        .container-botao-adicionar-equipamento {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar-equipamento {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-adicionar-equipamento:hover {
            background-color: var(--cor-secundaria);
        }

        .container-pesquisa {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 10px;
        }

        .container-pesquisa input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 100%;
            max-width: 400px;
        }
        
        .city-buttons-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .city-button {
            background-color: #e2e8f0;
            color: #4a5568;
            padding: 8px 16px;
            border: 1px solid #cbd5e0;
            border-radius: 9999px; /* rounded-full */
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-size: 0.9em;
        }

        .city-button:hover {
            background-color: #cbd5e0;
        }

        .city-button.active {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
        }
        
        .main-loading-state {
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
            color: #555;
            padding: 40px 0;
        }
        
        .main-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--cor-principal);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        .equipment-list-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            width: 100%;
        }

        .city-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: left;
        }
        
        .city-section h3 {
            margin: 0;
            color: var(--cor-secundaria);
        }
        
        .equipment-grid {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-equipamento {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-equipamento h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-equipamento p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-equipamento .botao-editar {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .item-equipamento .botao-editar:hover {
            background-color: #0056b3;
        }

        .status-cell {
            font-weight: normal;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .status-ativo {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .status-inativo {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .status-remanejado {
            background-color: #fffbeb;
            color: #f97316;
        }

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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: flex-start;
        }

        .modal.is-active {
            display: flex;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            position: relative;
            text-align: left;
            margin-top: 5%;
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333333;
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content select {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .modal-content .form-buttons {
            display: flex;
            justify-content: space-around;
            gap: 15px;
            margin-top: 1.5rem;
        }

        .modal-content .form-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content .form-buttons .save-button {
            background-color: #28a745;
            color: white;
        }

        .modal-content .form-buttons .save-button:hover {
            background-color: #218838;
        }

        .modal-content .form-buttons .cancel-button {
            background-color: #dc3545;
            color: white;
        }

        .modal-content .form-buttons .cancel-button:hover {
            background-color: #c82333;
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

        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
            display: none;
            vertical-align: middle;
            margin-left: 8px;
        }

        .loading-spinner.is-active {
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .hidden {
            display: none !important;
        }

        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
                width: 95%;
            }

            .titulo-cabecalho {
                font-size: 1.5rem;
            }

            .botao-voltar {
                position: static;
                margin-right: 1rem;
            }

            .container-botao-adicionar-equipamento {
                justify-content: center;
            }

            .modal-content {
                padding: 1.5rem;
            }

            .modal-content .form-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="titulo-cabecalho">Gerenciar Equipamentos</h1>
        </header>
        <div class="container-botao-adicionar-equipamento">
            <button class="botao-adicionar-equipamento" id="addEquipmentBtn">Adicionar Equipamento</button>
        </div>
        <div class="container-pesquisa">
            <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou cidade...">
        </div>
        <div id="cityButtonsContainer" class="city-buttons-container">
            <button class="city-button active" data-city="all">Mostrar Todos</button>
            <?php foreach ($cities as $city): ?>
                <button class="city-button" data-city="<?php echo htmlspecialchars($city['nome']); ?>">
                    <?php echo htmlspecialchars($city['nome']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div id="mainLoadingState" class="main-loading-state">
            <div class="main-loading-spinner"></div>
            <span>Carregando dados...</span>
        </div>
        <div id="containerListaEquipamentos" class="equipment-list-container">
            </div>
    </main>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <div id="addEquipmentModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeAddEquipmentModal">&times;</span>
            <h3>Adicionar Novo Equipamento</h3>
            <form id="addEquipmentForm">
                <label for="equipmentType">Tipo de Equipamento:</label>
                <select id="equipmentType" name="tipo_equip" required>
                    <option value="">Selecione o Tipo</option>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LÔMBADA</option>
                </select>

                <label for="equipmentName">Nome:</label>
                <input type="text" id="equipmentName" name="nome_equip" required>

                <label for="equipmentReference">Referência:</label>
                <input type="text" id="equipmentReference" name="referencia_equip">

                <label for="equipmentStatus">Status:</label>
                <select id="equipmentStatus" name="status" required>
                    <option value="ativo" selected>Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>

                <div id="add-qtd-faixa-container" class="hidden">
                    <label for="equipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="equipmentQtdFaixa" name="qtd_faixa">
                </div>

                <label for="equipmentCity">Cidade:</label>
                <select id="equipmentCity" name="id_cidade" required>
                    <option value="">Selecione a Cidade</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id_cidade']); ?>">
                            <?php echo htmlspecialchars($city['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="equipmentLogradouro">Logradouro:</label>
                <input type="text" id="equipmentLogradouro" name="logradouro" required>

                <label for="equipmentBairro">Bairro:</label>
                <input type="text" id="equipmentBairro" name="bairro" required>

                <label for="equipmentProvider">Provedor:</label>
                <select id="equipmentProvider" name="id_provedor" required>
                    <option value="">Carregando provedores...</option>
                </select>

                <label for="equipmentCep">CEP:</label>
                <input type="text" id="equipmentCep" name="cep">

                <label for="equipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="equipmentLatitude" name="latitude">

                <label for="equipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="equipmentLongitude" name="longitude">

                <div class="form-buttons" id="add-form-buttons">
                    <button type="submit" class="save-button" id="saveAddEquipmentButton">
                        Salvar Equipamento
                        <span id="addEquipmentSpinner" class="loading-spinner"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelAddEquipmentButton">Cancelar</button>
                </div>
                <p id="addEquipmentMessage" class="message hidden"></p>
            </form>
        </div>
    </div>

    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditEquipmentModal">&times;</span>
            <h3>Editar Equipamento</h3>
            <form id="editEquipmentForm">
                <input type="hidden" id="editEquipmentId" name="id_equipamento">
                <input type="hidden" id="editEnderecoId" name="id_endereco">

                <label for="editEquipmentType">Tipo de Equipamento:</label>
                <select id="editEquipmentType" name="tipo_equip" required>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LÔMBADA</option>
                </select>

                <label for="editEquipmentName">Nome:</label>
                <input type="text" id="editEquipmentName" name="nome_equip" required>

                <label for="editEquipmentReference">Referência:</label>
                <input type="text" id="editEquipmentReference" name="referencia_equip">

                <label for="editEquipmentStatus">Status:</label>
                <select id="editEquipmentStatus" name="status" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>

                <div id="edit-qtd-faixa-container" class="hidden">
                    <label for="editEquipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="editEquipmentQtdFaixa" name="qtd_faixa">
                </div>

                <label for="editEquipmentCity">Cidade:</label>
                <select id="editEquipmentCity" name="id_cidade" required>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id_cidade']); ?>">
                            <?php echo htmlspecialchars($city['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="editEquipmentLogradouro">Logradouro:</label>
                <input type="text" id="editEquipmentLogradouro" name="logradouro" required>

                <label for="editEquipmentBairro">Bairro:</label>
                <input type="text" id="editEquipmentBairro" name="bairro" required>

                <label for="editEquipmentProvider">Provedor:</label>
                <select id="editEquipmentProvider" name="id_provedor" required>
                    <option value="">Carregando provedores...</option>
                </select>

                <label for="editEquipmentCep">CEP:</label>
                <input type="text" id="editEquipmentCep" name="cep">

                <label for="editEquipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="editEquipmentLatitude" name="latitude">

                <label for="editEquipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="editEquipmentLongitude" name="longitude">

                <div class="form-buttons" id="edit-form-buttons">
                    <button type="submit" class="save-button" id="saveEditEquipmentButton">
                        Salvar Alterações
                        <span id="editEquipmentSpinner" class="loading-spinner"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelEditEquipmentButton">Cancelar</button>
                </div>
                <p id="editEquipmentMessage" class="message hidden"></p>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Declaração das variáveis no escopo correto
            const addEquipmentBtn = document.getElementById('addEquipmentBtn');
            const addEquipmentModal = document.getElementById('addEquipmentModal');
            const closeAddEquipmentModal = document.getElementById('closeAddEquipmentModal');
            const cancelAddEquipmentButton = document.getElementById('cancelAddEquipmentButton');
            const addEquipmentForm = document.getElementById('addEquipmentForm');
            const addEquipmentMessage = document.getElementById('addEquipmentMessage');
            const addQtdFaixaContainer = document.getElementById('add-qtd-faixa-container');
            const addFormButtonsContainer = document.getElementById('add-form-buttons');

            const editEquipmentModal = document.getElementById('editEquipmentModal');
            const closeEditEquipmentModal = document.getElementById('closeEditEquipmentModal');
            const cancelEditEquipmentButton = document.getElementById('cancelEditEquipmentButton');
            const editEquipmentForm = document.getElementById('editEquipmentForm');
            const editEquipmentMessage = document.getElementById('editEquipmentMessage');
            const editQtdFaixaContainer = document.getElementById('edit-qtd-faixa-container');
            const editFormButtonsContainer = document.getElementById('edit-form-buttons');
            const equipmentListContainer = document.getElementById('containerListaEquipamentos');
            const mainLoadingState = document.getElementById('mainLoadingState');
            const campoPesquisa = document.getElementById('campoPesquisa');
            const cityButtonsContainer = document.getElementById('cityButtonsContainer');


            let allEquipmentData = [];
            let currentFilteredData = [];
            let activeCityFilter = 'all';


            window.showMessage = function(element, msg, type) {
                element.textContent = msg;
                element.className = `message ${type}`;
                element.classList.remove('hidden');
            };

            window.hideMessage = function(element) {
                element.classList.add('hidden');
                element.textContent = '';
            };

            function toggleLoadingState(spinnerId, saveButtonId, cancelButtonId, show) {
                const saveButton = document.getElementById(saveButtonId);
                const cancelButton = document.getElementById(cancelButtonId);
                const spinner = document.getElementById(spinnerId);

                if (saveButton) {
                    if (show) {
                        saveButton.disabled = true;
                        saveButton.textContent = 'Salvando...';
                    } else {
                        saveButton.disabled = false;
                        if (saveButtonId === 'saveAddEquipmentButton') {
                            saveButton.textContent = 'Salvar Equipamento';
                        } else if (saveButtonId === 'saveEditEquipmentButton') {
                            saveButton.textContent = 'Salvar Alterações';
                        }
                    }
                }

                if (cancelButton) {
                    cancelButton.disabled = show;
                }

                if (spinner) {
                    if (show) {
                        spinner.classList.add('is-active');
                    } else {
                        spinner.classList.remove('is-active');
                    }
                }
            }

            function toggleQtdFaixaField(selectElement, containerElement) {
                const selectedType = selectElement.value;
                // Campo de faixas visível somente para RADAR FIXO e Educativo
                if (selectedType === 'RADAR FIXO' || selectedType === 'EDUCATIVO' || selectedType === 'LOMBADA') {
                    containerElement.classList.remove('hidden');
                } else {
                    containerElement.classList.add('hidden');
                    containerElement.querySelector('input').value = null;
                }
            }


            async function fetchProvidersForSelect() {
                const selectProvider = document.getElementById('equipmentProvider');
                const editSelectProvider = document.getElementById('editEquipmentProvider');

                const loadingOption = '<option value="">Carregando provedores...</option>';
                selectProvider.innerHTML = loadingOption;
                editSelectProvider.innerHTML = loadingOption;

                try {
                    const response = await fetch('get_provedores_select.php');
                    const data = await response.json();

                    if (data.success) {
                        const defaultOption = '<option value="">Selecione o Provedor</option>';
                        const providerOptions = data.provedores.map(p => `<option value="${p.id_provedor}">${p.nome_prov}</option>`).join('');
                        selectProvider.innerHTML = defaultOption + providerOptions;
                        editSelectProvider.innerHTML = defaultOption + providerOptions;
                    } else {
                        selectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
                        editSelectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
                    }
                } catch (error) {
                    console.error('Erro ao buscar provedores:', error);
                    selectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
                    editSelectProvider.innerHTML = '<option value="">Erro ao carregar provedores</option>';
                }
            }


            async function fetchAndRenderEquipments() {
                // Mostra o spinner de carregamento principal
                mainLoadingState.style.display = 'flex';
                equipmentListContainer.innerHTML = '';

                try {
                    const response = await fetch('get_equipamento-dados.php');
                    const data = await response.json();

                    if (data.success) {
                        allEquipmentData = data.equipamentos;
                        applyFilters();
                    } else {
                        equipmentListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                    }
                } catch (error) {
                    console.error('Erro ao buscar equipamentos:', error);
                    equipmentListContainer.innerHTML = `<p class="message error">Ocorreu um erro ao buscar os equipamentos. Tente novamente.</p>`;
                } finally {
                    // Esconde o spinner de carregamento principal
                    mainLoadingState.style.display = 'none';
                }
            }
            
            // Função para obter a ordem de exibição dos tipos de equipamento
            function getEquipmentTypeOrder(type) {
                switch (type) {
                    case 'CCO':
                        return 1;
                    case 'RADAR FIXO':
                        return 2;
                    case 'DOME':
                        return 3;
                    case 'EDUCATIVO':
                        return 4;
                    default:
                        return 99; // Coloca tipos desconhecidos no final
                }
            }

            function applyFilters() {
                let filteredData = [...allEquipmentData];
                const searchTerm = campoPesquisa.value.toLowerCase();

                if (activeCityFilter !== 'all') {
                    filteredData = filteredData.filter(equip => equip.cidade.toLowerCase() === activeCityFilter.toLowerCase());
                }

                if (searchTerm) {
                     filteredData = filteredData.filter(equip =>
                        (equip.nome_equip && equip.nome_equip.toLowerCase().includes(searchTerm)) ||
                        (equip.referencia_equip && equip.referencia_equip.toLowerCase().includes(searchTerm)) ||
                        (equip.logradouro && equip.logradouro.toLowerCase().includes(searchTerm))
                    );
                }

                // Aplica a nova ordem de exibição
                filteredData.sort((a, b) => getEquipmentTypeOrder(a.tipo_equip) - getEquipmentTypeOrder(b.tipo_equip));
                
                currentFilteredData = filteredData;
                renderEquipmentList(currentFilteredData);
            }

            function renderEquipmentList(equipments) {
                const container = document.getElementById('containerListaEquipamentos');
                container.innerHTML = '';

                if (equipments.length === 0) {
                    container.innerHTML = '<p class="message">Nenhum equipamento encontrado.</p>';
                    return;
                }

                const equipmentsByCity = equipments.reduce((acc, equip) => {
                    const city = equip.cidade;
                    if (!acc[city]) {
                        acc[city] = [];
                    }
                    acc[city].push(equip);
                    return acc;
                }, {});

                for (const city in equipmentsByCity) {
                    const citySection = document.createElement('div');
                    citySection.classList.add('city-section');

                    const cityTitle = document.createElement('h3');
                    cityTitle.textContent = city;
                    citySection.appendChild(cityTitle);

                    const equipmentGrid = document.createElement('div');
                    equipmentGrid.classList.add('equipment-grid');
                    citySection.appendChild(equipmentGrid);

                    const htmlContent = equipmentsByCity[city].map(equip => {
                        const statusClass = `status-${(equip.status || '').toLowerCase()}`;
                        const statusDisplay = (equip.status || 'N/A').charAt(0).toUpperCase() + (equip.status || 'N/A').slice(1);
                        const qtdFaixaDisplay = equip.qtd_faixa ? `<p><strong>Qtd. Faixa:</strong> ${equip.qtd_faixa}</p>` : '';
                        const enderecoDisplay = `<strong>Endereço:</strong> ${equip.logradouro || 'N/A'} - ${equip.bairro || 'N/A'}`;

                        return `
                            <div class="item-equipamento">
                                <h3>${equip.nome_equip || 'N/A'}</h3>
                                <p><strong>Tipo:</strong> ${equip.tipo_equip || 'N/A'}</p>
                                <p><strong>Referência:</strong> ${equip.referencia_equip || 'N/A'}</p>
                                ${qtdFaixaDisplay}
                                <p>${enderecoDisplay}</p>
                                <p><strong>Cidade:</strong> ${equip.cidade || 'N/A'}</p>
                                <p><strong>Provedor:</strong> ${equip.nome_prov || 'N/A'}</p>
                                <p><strong>Status:</strong> <span class="status-cell ${statusClass}">${statusDisplay}</span></p>
                                <button class="botao-editar" data-equipment-id="${equip.id_equipamento}">Editar</button>
                            </div>
                        `;
                    }).join('');

                    equipmentGrid.innerHTML = htmlContent;
                    container.appendChild(citySection);
                }
            }

            function findEquipmentById(id) {
                return allEquipmentData.find(equip => equip.id_equipamento == id);
            }

            function openAddEquipmentModal() {
                addEquipmentForm.reset();
                window.hideMessage(addEquipmentMessage);
                toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
                addQtdFaixaContainer.classList.add('hidden'); // Oculta o campo ao abrir o modal
                addEquipmentModal.classList.add('is-active');
                addFormButtonsContainer.style.display = 'flex'; // Garante que os botões estão visíveis
            }

            function closeAddEquipmentModalFunc() {
                addEquipmentModal.classList.remove('is-active');
            }

            function openEditEquipmentModal(equipmentData) {
                if (!equipmentData) {
                    window.showMessage(editEquipmentMessage, 'Dados do equipamento não encontrados.', 'error');
                    return;
                }

                document.getElementById('editEquipmentId').value = equipmentData.id_equipamento;
                document.getElementById('editEnderecoId').value = equipmentData.id_endereco;
                document.getElementById('editEquipmentType').value = equipmentData.tipo_equip;
                document.getElementById('editEquipmentName').value = equipmentData.nome_equip;
                document.getElementById('editEquipmentReference').value = equipmentData.referencia_equip;
                document.getElementById('editEquipmentStatus').value = equipmentData.status;
                document.getElementById('editEquipmentQtdFaixa').value = equipmentData.qtd_faixa;
                document.getElementById('editEquipmentProvider').value = equipmentData.id_provedor;
                document.getElementById('editEquipmentCity').value = equipmentData.id_cidade;
                document.getElementById('editEquipmentLogradouro').value = equipmentData.logradouro;
                document.getElementById('editEquipmentBairro').value = equipmentData.bairro;
                document.getElementById('editEquipmentCep').value = equipmentData.cep;
                document.getElementById('editEquipmentLatitude').value = equipmentData.latitude;
                document.getElementById('editEquipmentLongitude').value = equipmentData.longitude;

                // Lógica para mostrar/esconder campo qtd_faixa
                toggleQtdFaixaField(document.getElementById('editEquipmentType'), editQtdFaixaContainer);

                window.hideMessage(editEquipmentMessage);
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
                editEquipmentModal.classList.add('is-active');
                editFormButtonsContainer.style.display = 'flex'; // Garante que os botões estão visíveis
            }

            function closeEditEquipmentModalFunc() {
                editEquipmentModal.classList.remove('is-active');
            }

            // Event Listeners
            addEquipmentBtn.addEventListener('click', openAddEquipmentModal);
            closeAddEquipmentModal.addEventListener('click', closeAddEquipmentModalFunc);
            cancelAddEquipmentButton.addEventListener('click', closeAddEquipmentModalFunc);
            closeEditEquipmentModal.addEventListener('click', closeEditEquipmentModalFunc);
            cancelEditEquipmentButton.addEventListener('click', closeEditEquipmentModalFunc);
            
            // Listener para o campo de pesquisa
            campoPesquisa.addEventListener('input', () => {
                applyFilters();
            });

            // Listener para os botões de cidade
            cityButtonsContainer.addEventListener('click', (event) => {
                const target = event.target;
                if (target.classList.contains('city-button')) {
                    // Remove a classe 'active' de todos os botões
                    document.querySelectorAll('.city-button').forEach(btn => btn.classList.remove('active'));
                    // Adiciona a classe 'active' ao botão clicado
                    target.classList.add('active');

                    activeCityFilter = target.dataset.city;
                    applyFilters();
                }
            });

            // Listener para mostrar/esconder campo de qtd_faixa no modal de Adicionar
            document.getElementById('equipmentType').addEventListener('change', (e) => {
                toggleQtdFaixaField(e.target, addQtdFaixaContainer);
            });

            // Listener para mostrar/esconder campo de qtd_faixa no modal de Editar
            document.getElementById('editEquipmentType').addEventListener('change', (e) => {
                toggleQtdFaixaField(e.target, editQtdFaixaContainer);
            });

            equipmentListContainer.addEventListener('click', function(event) {
                if (event.target.classList.contains('botao-editar')) {
                    const equipmentId = event.target.dataset.equipmentId;
                    const equipmentData = findEquipmentById(equipmentId);
                    openEditEquipmentModal(equipmentData);
                }
            });

            window.onclick = function(event) {
                if (event.target === addEquipmentModal) {
                    closeAddEquipmentModalFunc();
                }
                if (event.target === editEquipmentModal) {
                    closeEditEquipmentModalFunc();
                }
            }

            addEquipmentForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                window.showMessage(addEquipmentMessage, 'Adicionando equipamento...', 'success');
                toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', true);

                const formData = new FormData(addEquipmentForm);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (key === 'latitude' || key === 'longitude') {
                        data[key] = value ? parseFloat(value) : null;
                    } else {
                        data[key] = value;
                    }
                }

                try {
                    const response = await fetch('add_equipment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Comportamento de sucesso: esconde botões e mostra mensagem
                        addFormButtonsContainer.style.display = 'none';
                        window.showMessage(addEquipmentMessage, 'Equipamento adicionado com sucesso!', 'success');
                        setTimeout(() => {
                            closeAddEquipmentModalFunc();
                            fetchAndRenderEquipments();
                        }, 1500);
                    } else {
                        window.showMessage(addEquipmentMessage, result.message || 'Erro ao adicionar equipamento.', 'error');
                    }
                } catch (error) {
                    console.error('Erro ao adicionar equipamento:', error);
                    window.showMessage(addEquipmentMessage, 'Ocorreu um erro ao adicionar o equipamento. Tente novamente.', 'error');
                } finally {
                    // Comportamento de falha: re-habilita botões
                    toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
                }
            });

            editEquipmentForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                window.showMessage(editEquipmentMessage, 'Salvando alterações...', 'success');
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', true);

                const formData = new FormData(editEquipmentForm);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (key === 'latitude' || key === 'longitude') {
                        data[key] = value ? parseFloat(value) : null;
                    } else {
                        data[key] = value;
                    }
                }

                try {
                    const response = await fetch('update_equipment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Comportamento de sucesso: esconde botões e mostra mensagem
                        editFormButtonsContainer.style.display = 'none';
                        window.showMessage(editEquipmentMessage, 'Equipamento atualizado com sucesso!', 'success');
                        setTimeout(() => {
                            closeEditEquipmentModalFunc();
                            fetchAndRenderEquipments();
                        }, 1500);
                    } else {
                        window.showMessage(editEquipmentMessage, result.message || 'Erro ao atualizar equipamento.', 'error');
                    }
                } catch (error) {
                    console.error('Erro ao atualizar equipamento:', error);
                    window.showMessage(editEquipmentMessage, 'Ocorreu um erro ao atualizar o equipamento. Tente novamente.', 'error');
                } finally {
                    // Comportamento de falha: re-habilita botões
                    toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
                }
            });

            fetchProvidersForSelect();
            fetchAndRenderEquipments();
        });
    </script>
</body>

</html>