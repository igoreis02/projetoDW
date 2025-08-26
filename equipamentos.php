<?php
session_start();
// Ativa a exibição de erros para depuração (REMOVER EM PRODUÇÃO)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

require_once 'API/conexao_bd.php';

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
    <link rel="icon" type="image/png" href="imagens/favicon.png">
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
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            margin: 0;
            color: #333333;
            flex-grow: 1;
            text-align: center;
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
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
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
            <div class="modal-header">
                <h3>Adicionar Novo Equipamento</h3>
                <button class="close-button" id="closeAddEquipmentModal">&times;</button>
            </div>
            <form id="addEquipmentForm">
                <label for="equipmentType">Tipo de Equipamento:</label>
                <select id="equipmentType" name="tipo_equip" required>
                    <option value="">Selecione o Tipo</option>
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LOMBADA</option>
                    <option value="LAP">LAP</option>
                </select>

                <label for="equipmentName">Nome:</label>
                <input type="text" id="equipmentName" name="nome_equip" >

                <label for="equipmentReference">Referência:</label>
                <input type="text" id="equipmentReference" name="referencia_equip">

                <label for="equipmentStatus">Status:</label>
                <select id="equipmentStatus" name="status" required>
                    <option value="ativo" selected>Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>

                <div id="add-specific-fields-container" class="hidden">
                    <label for="equipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="equipmentQtdFaixa" name="qtd_faixa">
                    
                    <label for="equipmentKm">KM da via:</label>
                    <input type="text" id="equipmentKm" name="km">

                    <label for="equipmentSentido">Sentido:</label>
                    <input type="text" id="equipmentSentido" name="sentido">
                </div>

                <label for="equipmentCity">Cidade:</label>
                <select id="equipmentCity" name="id_cidade" >
                    <option value="">Selecione a Cidade</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id_cidade']); ?>">
                            <?php echo htmlspecialchars($city['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="equipmentLogradouro">Logradouro:</label>
                <input type="text" id="equipmentLogradouro" name="logradouro" >

                <label for="equipmentBairro">Bairro:</label>
                <input type="text" id="equipmentBairro" name="bairro">

                <label for="equipmentProvider">Provedor:</label>
                <select id="equipmentProvider" name="id_provedor">
                    <option value="">Carregando provedores...</option>
                </select>

                <label for="equipmentCep">CEP:</label>
                <input type="text" id="equipmentCep" name="cep">

                <label for="numInstrumento">Nº Instrumento:</label>
                <input type="text" id="numInstrumento" name="num_instrumento">

                <label for="dtAfericao">Data Aferição:</label>
                <input type="date" id="dtAfericao" name="dt_afericao">

                <label for="equipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="equipmentLatitude" name="latitude">

                <label for="equipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="equipmentLongitude" name="longitude">
                    
                <p id="addEquipmentMessage" class="message hidden"></p>
                <div class="form-buttons" id="add-form-buttons">
                    <button type="submit" class="save-button" id="saveAddEquipmentButton">
                        Salvar Equipamento
                        <span id="addEquipmentSpinner" class="loading-spinner"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelAddEquipmentButton">Cancelar</button>
                </div>
                
            </form>
        </div>
    </div>

    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Equipamento</h3>
                <button class="close-button" id="closeEditEquipmentModal">&times;</button>
            </div>
            <form id="editEquipmentForm">
                <input type="hidden" id="editEquipmentId" name="id_equipamento">
                <input type="hidden" id="editEnderecoId" name="id_endereco">

                <label for="editEquipmentType">Tipo de Equipamento:</label>
                <select id="editEquipmentType" name="tipo_equip" >
                    <option value="CCO">CCO</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                    <option value="DOME">DOME</option>
                    <option value="EDUCATIVO">EDUCATIVO</option>
                    <option value="LOMBADA">LOMBADA</option>
                    <option value="LAP">LAP</option>
                </select>

                <label for="editEquipmentName">Nome:</label>
                <input type="text" id="editEquipmentName" name="nome_equip" >

                <label for="editEquipmentReference">Referência:</label>
                <input type="text" id="editEquipmentReference" name="referencia_equip">

                <label for="editEquipmentStatus">Status:</label>
                <select id="editEquipmentStatus" name="status" >
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>
                
                <div id="edit-specific-fields-container" class="hidden">
                    <label for="editEquipmentQtdFaixa">Quantidade de Faixas:</label>
                    <input type="number" id="editEquipmentQtdFaixa" name="qtd_faixa">

                    <label for="editEquipmentKm">KM da via:</label>
                    <input type="text" id="editEquipmentKm" name="km">

                    <label for="editEquipmentSentido">Sentido:</label>
                    <input type="text" id="editEquipmentSentido" name="sentido">
                </div>

                <label for="editEquipmentCity">Cidade:</label>
                <select id="editEquipmentCity" name="id_cidade" >
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city['id_cidade']); ?>">
                            <?php echo htmlspecialchars($city['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="editEquipmentLogradouro">Logradouro:</label>
                <input type="text" id="editEquipmentLogradouro" name="logradouro" >

                <label for="editEquipmentBairro">Bairro:</label>
                <input type="text" id="editEquipmentBairro" name="bairro" >

                <label for="editEquipmentProvider">Provedor:</label>
                <select id="editEquipmentProvider" name="id_provedor" >
                    <option value="">Carregando provedores...</option>
                </select>

                <label for="editEquipmentCep">CEP:</label>
                <input type="text" id="editEquipmentCep" name="cep">

                <label for="editNumInstrumento">Nº Instrumento:</label>
                <input type="text" id="editNumInstrumento" name="num_instrumento" >

                <label for="editDtAfericao">Data Aferição:</label>
                <input type="date" id="editDtAfericao" name="dt_afericao" >

                <label for="editEquipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="editEquipmentLatitude" name="latitude">

                <label for="editEquipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="editEquipmentLongitude" name="longitude">

                <p id="editEquipmentMessage" class="message hidden"></p>
                <div class="form-buttons" id="edit-form-buttons">
                    <button type="submit" class="save-button" id="saveEditEquipmentButton">
                        Salvar Alterações
                        <span id="editEquipmentSpinner" class="loading-spinner"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelEditEquipmentButton">Cancelar</button>
                </div>
                
            </form>
        </div>
    </div>


    <script src="js/equipamentos.js"></script>
</body>

</html>