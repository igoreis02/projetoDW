<?php
session_start();
// Ativa a exibição de erros para depuração (REMOVER EM PRODUÇÃO)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Configurações do banco de dados (AJUSTE CONFORME SEU AMBIENTE)
$servername = "localhost";
$username = "root"; // Substitua pelo seu usuário do banco de dados
$password = "";     // Substitua pela sua senha do banco de dados
$dbname = "gerenciamento_manutencoes"; // Substitua pelo nome do seu banco de dados

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

$equipamentos_por_cidade = [];
$errorMessage = '';
$cities = []; // Array para armazenar as cidades

try {
    // Consulta SQL para obter os equipamentos com informações de cidade e endereço, incluindo o status.
    // Selecionando id_equipamento, id_endereco e id_cidade para uso no modal de edição
    $sql_equipamentos = "SELECT
                e.id_equipamento,
                e.id_cidade,
                e.id_endereco,
                c.nome AS cidade,
                e.tipo_equip,
                e.nome_equip,
                e.referencia_equip,
                en.logradouro,
                en.numero,
                en.bairro,
                en.cep,
                en.latitude,
                en.longitude,
                e.status
            FROM equipamentos AS e
            JOIN cidades AS c ON e.id_cidade = c.id_cidade
            LEFT JOIN endereco AS en ON e.id_endereco = en.id_endereco
            ORDER BY
                CASE
                    WHEN e.tipo_equip = 'RADAR' THEN 0
                    ELSE 1
                END,
                c.nome,
                e.tipo_equip,
                e.nome_equip";

    $result_equipamentos = $conn->query($sql_equipamentos);

    if ($result_equipamentos === false) {
        throw new Exception("Erro ao executar a consulta de equipamentos: " . $conn->error);
    }

    if ($result_equipamentos->num_rows > 0) {
        while ($row = $result_equipamentos->fetch_assoc()) {
            $cidade = $row['cidade'];
            if (!isset($equipamentos_por_cidade[$cidade])) {
                $equipamentos_por_cidade[$cidade] = [];
            }
            $equipamentos_por_cidade[$cidade][] = $row;
        }
    } else {
        $errorMessage = 'Nenhum equipamento cadastrado no sistema.';
    }

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
    $errorMessage = 'Erro ao carregar dados: ' . $e->getMessage();
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
    <title>Informações de Equipamentos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos do card e layout geral, adaptados de gerenciar_usuario.php */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
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
            width: 90%;
            max-width: 1200px;
            text-align: center;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 50px;
        }

        .card:before {
            content: none;
        }

        h2 {
            font-size: 2em;
            color: var(--cor-principal);
            margin-bottom: 30px;
            margin-top: 0;
        }

        /* Estilos para a tabela de equipamentos */
        .equipment-table-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .equipment-table th, .equipment-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 0.9em;
        }

        .equipment-table th {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            border-bottom: 2px solid var(--cor-principal);
        }

        .equipment-table td {
            color: #000000;
            font-weight: normal;
        }

        .equipment-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .equipment-table tr:hover {
            background-color: #ddd;
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

        .footer {
            margin-top: auto;
            color: #888;
            font-size: 0.9em;
            width: 100%;
            text-align: center;
            padding-top: 20px;
        }

        .add-equipment-button-container {
            width: 100%;
            text-align: left;
            margin-bottom: 20px;
        }

        .add-equipment-button {
            padding: 12px 20px;
            font-size: 1em;
            color: white;
            background-color: var(--cor-principal);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .add-equipment-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        /* Estilo do botão de edição */
        .edit-equipment-btn {
            background-color: #007bff; /* Azul */
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .edit-equipment-btn:hover {
            background-color: #0056b3;
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
            margin-top: 10%;
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
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .card {
                padding: 1.5rem;
                width: 95%;
            }
            h2 {
                font-size: 1.8em;
            }
            .voltar-btn {
                width: 70%;
            }
            .add-equipment-button {
                width: 100%;
                text-align: center;
            }
            .modal-content {
                padding: 1.5rem;
            }
            .modal-content .form-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .card {
                width: 100%;
                padding: 10px;
            }
            .footer {
                margin-top: 20px;
            }
            .equipment-table th, .equipment-table td {
                padding: 8px;
                font-size: 0.8em;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Informações de Equipamentos</h2>

        <div class="add-equipment-button-container">
            <button id="addEquipmentBtn" class="add-equipment-button">Adicionar Equipamento</button>
        </div>

        <div class="equipment-table-container">
            <?php if (!empty($errorMessage)): ?>
                <p class="message error"><?php echo $errorMessage; ?></p>
            <?php elseif (empty($equipamentos_por_cidade)): ?>
                <p class="message">Nenhum equipamento cadastrado no sistema.</p>
            <?php else: ?>
                <?php foreach ($equipamentos_por_cidade as $cidade => $equipamentos): ?>
                    <h3><?php echo htmlspecialchars($cidade); ?></h3>
                    <table class="equipment-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Nome</th>
                                <th>Referência</th>
                                <th>Logradouro</th>
                                <th>Bairro</th>
                                <th>Status</th>
                                <th>Opções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipamentos as $equipamento): ?>
                                <?php
                                    $status = $equipamento['status'] ?? 'N/A';
                                    $status_display = ucfirst($status);
                                    $status_class = '';
                                    switch (strtolower($status)) {
                                        case 'ativo':
                                            $status_class = 'status-ativo';
                                            break;
                                        case 'inativo':
                                            $status_class = 'status-inativo';
                                            break;
                                        case 'remanejado':
                                            $status_class = 'status-remanejado';
                                            break;
                                        default:
                                            $status_class = '';
                                            break;
                                    }
                                    // Prepara os dados do equipamento para o JSON, tratando valores NULL
                                    $equipmentJson = json_encode([
                                        'id_equipamento' => $equipamento['id_equipamento'] ?? '',
                                        'id_endereco' => $equipamento['id_endereco'] ?? '',
                                        'tipo_equip' => $equipamento['tipo_equip'] ?? '',
                                        'nome_equip' => $equipamento['nome_equip'] ?? '',
                                        'referencia_equip' => $equipamento['referencia_equip'] ?? '',
                                        'status' => $equipamento['status'] ?? '',
                                        'id_cidade' => $equipamento['id_cidade'] ?? '',
                                        'logradouro' => $equipamento['logradouro'] ?? '',
                                        'numero' => $equipamento['numero'] ?? '',
                                        'bairro' => $equipamento['bairro'] ?? '',
                                        'cep' => $equipamento['cep'] ?? '',
                                        'latitude' => $equipamento['latitude'] ?? '',
                                        'longitude' => $equipamento['longitude'] ?? ''
                                    ]);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($equipamento['tipo_equip'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($equipamento['nome_equip'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($equipamento['referencia_equip'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($equipamento['logradouro'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($equipamento['bairro'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($status !== 'N/A'): ?>
                                            <span class="status-cell <?php echo htmlspecialchars($status_class); ?>">
                                                <?php echo htmlspecialchars($status_display); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($status_display); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="edit-equipment-btn" data-equipment-json='<?php echo htmlspecialchars($equipmentJson, ENT_QUOTES, 'UTF-8'); ?>'>Editar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <!-- Modal para Adicionar Equipamento -->
    <div id="addEquipmentModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeAddEquipmentModal">&times;</span>
            <h3>Adicionar Novo Equipamento</h3>
            <form id="addEquipmentForm">
                <label for="equipmentType">Tipo de Equipamento:</label>
                <select id="equipmentType" name="tipo_equip" required>
                    <option value="">Selecione o Tipo</option>
                    <option value="DOME">DOME</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
                </select>

                <label for="equipmentName">Nome:</label>
                <input type="text" id="equipmentName" name="nome_equip" required>

                <label for="equipmentReference">Referência:</label>
                <input type="text" id="equipmentReference" name="referencia_equip">

                <label for="equipmentStatus">Status:</label>
                <select id="equipmentStatus" name="status" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="remanejado">Remanejado</option>
                </select>

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

                <label for="equipmentNumero">Número (Opcional):</label>
                <input type="text" id="equipmentNumero" name="numero">

                <label for="equipmentBairro">Bairro:</label>
                <input type="text" id="equipmentBairro" name="bairro" required>

                <label for="equipmentCep">CEP:</label>
                <input type="text" id="equipmentCep" name="cep">

                <label for="equipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="equipmentLatitude" name="latitude">

                <label for="equipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="equipmentLongitude" name="longitude">

                <div class="form-buttons" id="add-form-buttons">
                    <button type="submit" class="save-button" id="saveAddEquipmentButton">
                        Salvar Equipamento
                        <span id="addEquipmentSpinner" class="loading-spinner hidden"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelAddEquipmentButton">Cancelar</button>
                </div>
                <p id="addEquipmentMessage" class="message hidden"></p>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Equipamento -->
    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeEditEquipmentModal">&times;</span>
            <h3>Editar Equipamento</h3>
            <form id="editEquipmentForm">
                <input type="hidden" id="editEquipmentId" name="id_equipamento">
                <input type="hidden" id="editEnderecoId" name="id_endereco">

                <label for="editEquipmentType">Tipo de Equipamento:</label>
                <select id="editEquipmentType" name="tipo_equip" required>
                    <option value="DOME">DOME</option>
                    <option value="RADAR FIXO">RADAR FIXO</option>
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

                <label for="editEquipmentNumero">Número (Opcional):</label>
                <input type="text" id="editEquipmentNumero" name="numero">

                <label for="editEquipmentBairro">Bairro:</label>
                <input type="text" id="editEquipmentBairro" name="bairro" required>

                <label for="editEquipmentCep">CEP:</label>
                <input type="text" id="editEquipmentCep" name="cep">

                <label for="editEquipmentLatitude">Latitude:</label>
                <input type="number" step="any" id="editEquipmentLatitude" name="latitude">

                <label for="editEquipmentLongitude">Longitude:</label>
                <input type="number" step="any" id="editEquipmentLongitude" name="longitude">

                <div class="form-buttons" id="edit-form-buttons">
                    <button type="submit" class="save-button" id="saveEditEquipmentButton">
                        Salvar Alterações
                        <span id="editEquipmentSpinner" class="loading-spinner hidden"></span>
                    </button>
                    <button type="button" class="cancel-button" id="cancelEditEquipmentButton">Cancelar</button>
                </div>
                <p id="editEquipmentMessage" class="message hidden"></p>
            </form>
        </div>
    </div>


    <script>
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

            if (show) {
                spinner.classList.remove('hidden');
                saveButton.disabled = true;
                cancelButton.disabled = true;
            } else {
                spinner.classList.add('hidden');
                saveButton.disabled = false;
                cancelButton.disabled = false;
            }
        }

        // Referências para o modal de Adicionar Equipamento
        const addEquipmentBtn = document.getElementById('addEquipmentBtn');
        const addEquipmentModal = document.getElementById('addEquipmentModal');
        const closeAddEquipmentModal = document.getElementById('closeAddEquipmentModal');
        const cancelAddEquipmentButton = document.getElementById('cancelAddEquipmentButton');
        const addEquipmentForm = document.getElementById('addEquipmentForm');
        const addEquipmentMessage = document.getElementById('addEquipmentMessage');
        const addFormButtonsContainer = document.getElementById('add-form-buttons');

        // Referências para o modal de Editar Equipamento
        const editEquipmentModal = document.getElementById('editEquipmentModal');
        const closeEditEquipmentModal = document.getElementById('closeEditEquipmentModal');
        const cancelEditEquipmentButton = document.getElementById('cancelEditEquipmentButton');
        const editEquipmentForm = document.getElementById('editEquipmentForm');
        const editEquipmentMessage = document.getElementById('editEquipmentMessage');
        const equipmentTable = document.querySelector('.equipment-table-container');

        const modalButtonsContainer = document.getElementById('edit-form-buttons');

        // Função para abrir o modal de Adicionar Equipamento
        function openAddEquipmentModal() {
            addEquipmentForm.reset();
            window.hideMessage(addEquipmentMessage);
            addFormButtonsContainer.style.display = 'flex';
            toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
            addEquipmentModal.classList.add('is-active');
        }

        // Função para fechar o modal de Adicionar Equipamento
        function closeAddEquipmentModalFunc() {
            addEquipmentModal.classList.remove('is-active');
        }

        // Função para abrir o modal de Editar Equipamento
        function openEditEquipmentModal(equipmentData) {
            // Preenche o formulário do modal com os dados do equipamento
            document.getElementById('editEquipmentId').value = equipmentData.id_equipamento;
            document.getElementById('editEnderecoId').value = equipmentData.id_endereco;
            document.getElementById('editEquipmentType').value = equipmentData.tipo_equip;
            document.getElementById('editEquipmentName').value = equipmentData.nome_equip;
            document.getElementById('editEquipmentReference').value = equipmentData.referencia_equip;
            document.getElementById('editEquipmentStatus').value = equipmentData.status;
            document.getElementById('editEquipmentCity').value = equipmentData.id_cidade;
            document.getElementById('editEquipmentLogradouro').value = equipmentData.logradouro;
            document.getElementById('editEquipmentNumero').value = equipmentData.numero;
            document.getElementById('editEquipmentBairro').value = equipmentData.bairro;
            document.getElementById('editEquipmentCep').value = equipmentData.cep;
            document.getElementById('editEquipmentLatitude').value = equipmentData.latitude;
            document.getElementById('editEquipmentLongitude').value = equipmentData.longitude;

            window.hideMessage(editEquipmentMessage);
            toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
            editEquipmentModal.classList.add('is-active');
        }

        // Função para fechar o modal de Editar Equipamento
        function closeEditEquipmentModalFunc() {
            editEquipmentModal.classList.remove('is-active');
        }

        // Event listeners para os botões e modais
        addEquipmentBtn.addEventListener('click', openAddEquipmentModal);

        closeAddEquipmentModal.onclick = closeAddEquipmentModalFunc;
        cancelAddEquipmentButton.onclick = closeAddEquipmentModalFunc;

        closeEditEquipmentModal.onclick = closeEditEquipmentModalFunc;
        cancelEditEquipmentButton.onclick = closeEditEquipmentModalFunc;

        window.onclick = function(event) {
            if (event.target == addEquipmentModal) {
                closeAddEquipmentModalFunc();
            }
            if (event.target == editEquipmentModal) {
                closeEditEquipmentModalFunc();
            }
        }

        // Event listener para os botões "Editar" da tabela usando delegação de eventos
        equipmentTable.addEventListener('click', function(event) {
            if (event.target.classList.contains('edit-equipment-btn')) {
                const equipmentData = JSON.parse(event.target.dataset.equipmentJson);
                openEditEquipmentModal(equipmentData);
            }
        });

        // Lógica para submeter o formulário de Adicionar Equipamento
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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    addFormButtonsContainer.style.display = 'none';
                    window.showMessage(addEquipmentMessage, 'Equipamento adicionado com sucesso!', 'success');
                    setTimeout(() => {
                        closeAddEquipmentModalFunc();
                        location.reload();
                    }, 1500);
                } else {
                    window.showMessage(addEquipmentMessage, result.message || 'Erro ao adicionar equipamento.', 'error');
                    toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
                }
            } catch (error) {
                console.error('Erro ao adicionar equipamento:', error);
                window.showMessage(addEquipmentMessage, 'Ocorreu um erro ao adicionar o equipamento. Tente novamente.', 'error');
                toggleLoadingState('addEquipmentSpinner', 'saveAddEquipmentButton', 'cancelAddEquipmentButton', false);
            }
        });

        // Lógica para submeter o formulário de Editar Equipamento
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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    window.showMessage(editEquipmentMessage, 'Equipamento atualizado com sucesso!', 'success');
                    // Aguarda um pouco antes de fechar o modal e recarregar a página
                    modalButtonsContainer.style.display = 'none';
                    setTimeout(() => {
                        closeEditEquipmentModalFunc();
                        location.reload();
                    }, 1500);
                } else {
                    window.showMessage(editEquipmentMessage, result.message || 'Erro ao atualizar equipamento.', 'error');
                    toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
                }
            } catch (error) {
                console.error('Erro ao atualizar equipamento:', error);
                window.showMessage(editEquipmentMessage, 'Ocorreu um erro ao atualizar o equipamento. Tente novamente.', 'error');
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', 'cancelEditEquipmentButton', false);
            }
        });
    </script>
</body>
</html>
