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
$allEquipments = []; // Array para armazenar todos os equipamentos

try {
    // Consulta SQL para obter os equipamentos com informações de cidade e endereço, incluindo o status.
    // Selecionando id_equipamento, id_endereco e id_cidade para uso no modal de edição
    $sql_equipamentos = "SELECT
                e.id_equipamento,
                e.id_cidade,
                e.id_endereco,
                e.nome_equip,
                e.referencia_equip,
                e.tipo_equip,
                c.nome AS nome_cidade,
                en.logradouro,
                en.numero,
                en.bairro,
                en.cep
            FROM equipamentos e
            LEFT JOIN cidades c ON e.id_cidade = c.id_cidade
            LEFT JOIN endereco en ON e.id_endereco = en.id_endereco";

    $result_equipamentos = $conn->query($sql_equipamentos);

    if ($result_equipamentos === false) {
        throw new Exception("Erro na consulta SQL: " . $conn->error);
    }

    if ($result_equipamentos->num_rows > 0) {
        while ($row = $result_equipamentos->fetch_assoc()) {
            $allEquipments[] = $row;
            $nome_cidade = $row['nome_cidade'] ?? 'Não especificada';
            if (!isset($equipamentos_por_cidade[$nome_cidade])) {
                $equipamentos_por_cidade[$nome_cidade] = [];
            }
            $equipamentos_por_cidade[$nome_cidade][] = $row;
        }
    }

    // Consulta SQL para obter todas as cidades
    $sql_cities = "SELECT id_cidade, nome FROM cidades ORDER BY nome";
    $result_cities = $conn->query($sql_cities);

    if ($result_cities === false) {
        throw new Exception("Erro na consulta SQL de cidades: " . $conn->error);
    }
    
    if ($result_cities->num_rows > 0) {
        while ($row = $result_cities->fetch_assoc()) {
            $cities[] = $row;
        }
    }

} catch (Exception $e) {
    $errorMessage = "Erro: " . $e->getMessage();
} finally {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informações de Equipamentos</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 90%;
            margin: 0 auto;
            position: relative;
        }
        
        .card-header {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #007bff;
            font-size: 1.2rem;
            text-decoration: none;
        }

        .btn-back:hover {
            text-decoration: underline;
        }

        /* Contêiner para os cards de equipamentos */
        #containerListaEquipamentos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 1rem;
        }

        /* Estilo para cada card de equipamento */
        .equipment-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card-info {
            margin-bottom: 10px;
        }
        
        .card-info-label {
            font-weight: 600;
            color: #495057;
        }

        .card-info-value {
            color: #6c757d;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .btn-edit {
            background-color: #ffc107;
            color: #333;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        /* Estilos do modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        
        .modal-form .form-group {
            margin-bottom: 1rem;
        }

        .modal-form label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 600;
        }

        .modal-form input, .modal-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: none;
            margin-left: 10px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .message-box {
            text-align: center;
            margin-top: 1rem;
        }

        .message-box p {
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="card">
        <a href="menu.php" class="btn-back"><i class="fas fa-arrow-left"></i> Voltar</a>
        <h1 class="card-header">Informações de Equipamentos</h1>
        <div id="containerListaEquipamentos">
            <!-- A lista de equipamentos será renderizada aqui pelo JavaScript -->
        </div>
    </div>

    <!-- Modal de Edição de Equipamento -->
    <div id="editEquipmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditEquipmentModal()">&times;</span>
            <h2 class="modal-title">Editar Equipamento</h2>
            <form id="editEquipmentForm" class="modal-form">
                <input type="hidden" id="idEquipamentoEdicao" name="id_equipamento">
                <input type="hidden" id="idEnderecoEdicao" name="id_endereco">
                <div class="form-group">
                    <label for="nomeEquipamentoEdicao">Nome:</label>
                    <input type="text" id="nomeEquipamentoEdicao" name="nome_equip" required>
                </div>
                <div class="form-group">
                    <label for="referenciaEquipamentoEdicao">Referência:</label>
                    <input type="text" id="referenciaEquipamentoEdicao" name="referencia_equip" required>
                </div>
                <div class="form-group">
                    <label for="tipoEquipamentoEdicao">Tipo:</label>
                    <input type="text" id="tipoEquipamentoEdicao" name="tipo_equip" required>
                </div>
                <div class="form-group">
                    <label for="cidadeEdicao">Cidade:</label>
                    <select id="cidadeEdicao" name="id_cidade" required>
                        <!-- As opções de cidade serão preenchidas via PHP -->
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city['id_cidade']); ?>"><?php echo htmlspecialchars($city['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="logradouroEdicao">Logradouro:</label>
                    <input type="text" id="logradouroEdicao" name="logradouro">
                </div>
                <div class="form-group">
                    <label for="numeroEdicao">Número:</label>
                    <input type="text" id="numeroEdicao" name="numero">
                </div>
                <div class="form-group">
                    <label for="bairroEdicao">Bairro:</label>
                    <input type="text" id="bairroEdicao" name="bairro">
                </div>
                <div class="form-group">
                    <label for="cepEdicao">CEP:</label>
                    <input type="text" id="cepEdicao" name="cep">
                </div>
                <div id="editEquipmentMessage" class="message-box"></div>
                <div class="btn-group-modal mt-3">
                    <button type="submit" id="saveEditEquipmentButton" class="btn btn-primary">
                        <span id="saveEditEquipmentButtonText">Salvar Alterações</span>
                        <div id="editEquipmentSpinner" class="spinner"></div>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditEquipmentModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-title">Confirmar Exclusão</h2>
            <p>Tem certeza que deseja excluir o equipamento <strong id="confirmEquipmentName"></strong>?</p>
            <div class="btn-group-modal mt-3">
                <button id="confirmDeleteButton" class="btn btn-danger">Excluir</button>
                <button class="btn btn-secondary" onclick="closeConfirmDeleteModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // Array com os dados dos equipamentos passados do PHP para o JavaScript
        const equipamentos = <?php echo json_encode($allEquipments); ?>;

        // Funções para os modais
        const editEquipmentModal = document.getElementById('editEquipmentModal');
        const confirmDeleteModal = document.getElementById('confirmDeleteModal');

        function openEditEquipmentModal(equipment) {
            document.getElementById('idEquipamentoEdicao').value = equipment.id_equipamento;
            document.getElementById('idEnderecoEdicao').value = equipment.id_endereco;
            document.getElementById('nomeEquipamentoEdicao').value = equipment.nome_equip;
            document.getElementById('referenciaEquipamentoEdicao').value = equipment.referencia_equip;
            document.getElementById('tipoEquipamentoEdicao').value = equipment.tipo_equip;
            document.getElementById('cidadeEdicao').value = equipment.id_cidade;
            document.getElementById('logradouroEdicao').value = equipment.logradouro;
            document.getElementById('numeroEdicao').value = equipment.numero;
            document.getElementById('bairroEdicao').value = equipment.bairro;
            document.getElementById('cepEdicao').value = equipment.cep;
            document.getElementById('editEquipmentMessage').innerHTML = '';
            editEquipmentModal.style.display = 'block';
        }

        function closeEditEquipmentModal() {
            editEquipmentModal.style.display = 'none';
        }
        
        function openConfirmDeleteModal(equipmentId, equipmentName) {
            document.getElementById('confirmEquipmentName').textContent = equipmentName;
            const confirmButton = document.getElementById('confirmDeleteButton');
            confirmButton.onclick = () => deleteEquipment(equipmentId);
            confirmDeleteModal.style.display = 'block';
        }

        function closeConfirmDeleteModal() {
            confirmDeleteModal.style.display = 'none';
        }

        // Fecha os modais se o usuário clicar fora deles
        window.onclick = function(event) {
            if (event.target == editEquipmentModal) {
                closeEditEquipmentModal();
            }
            if (event.target == confirmDeleteModal) {
                closeConfirmDeleteModal();
            }
        };

        // Função para mostrar mensagens
        function showMessage(element, message, type) {
            element.innerHTML = `<p class="${type}-message">${message}</p>`;
        };

        // Função para alternar o spinner e o estado do botão
        function toggleLoadingState(spinnerId, buttonId, show) {
            const button = document.getElementById(buttonId);
            const spinner = document.getElementById(spinnerId);
            const buttonText = button.querySelector('span') || button.firstChild;
            
            if (show) {
                button.disabled = true;
                button.style.opacity = '0.7';
                spinner.style.display = 'inline-block';
                if (buttonText) buttonText.style.display = 'none';
            } else {
                button.disabled = false;
                button.style.opacity = '1';
                spinner.style.display = 'none';
                if (buttonText) buttonText.style.display = 'inline-block';
            }
        }

        // Lógica para renderizar os cards de equipamento
        function renderEquipments() {
            const container = document.getElementById('containerListaEquipamentos');
            container.innerHTML = ''; // Limpa o conteúdo anterior

            if (equipamentos.length > 0) {
                equipamentos.forEach(equipment => {
                    const card = document.createElement('div');
                    card.className = 'equipment-card';
                    card.innerHTML = `
                        <div class="card-info">
                            <span class="card-info-label">Nome:</span>
                            <span class="card-info-value">${equipment.nome_equip}</span>
                        </div>
                        <div class="card-info">
                            <span class="card-info-label">Referência:</span>
                            <span class="card-info-value">${equipment.referencia_equip}</span>
                        </div>
                        <div class="card-info">
                            <span class="card-info-label">Tipo:</span>
                            <span class="card-info-value">${equipment.tipo_equip}</span>
                        </div>
                        <div class="card-info">
                            <span class="card-info-label">Cidade:</span>
                            <span class="card-info-value">${equipment.nome_cidade}</span>
                        </div>
                        <div class="card-info">
                            <span class="card-info-label">Endereço:</span>
                            <span class="card-info-value">${equipment.logradouro}, ${equipment.numero} - ${equipment.bairro}, ${equipment.cep}</span>
                        </div>
                        <div class="btn-group">
                            <button class="btn-edit" onclick='openEditEquipmentModal(${JSON.stringify(equipment)})'>
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn-delete" onclick="openConfirmDeleteModal(${equipment.id_equipamento}, '${equipment.nome_equip}')">
                                <i class="fas fa-trash-alt"></i> Excluir
                            </button>
                        </div>
                    `;
                    container.appendChild(card);
                });
            } else {
                container.innerHTML = '<p class="text-center mt-4">Nenhum equipamento cadastrado.</p>';
            }
        }

        // Lógica para editar equipamento
        const editEquipmentForm = document.getElementById('editEquipmentForm');
        editEquipmentForm.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            
            toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', true);

            const formData = new FormData(editEquipmentForm);
            const equipmentData = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('update_equipment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(equipmentData)
                });
                const result = await response.json();

                if (result.success) {
                    showMessage(document.getElementById('editEquipmentMessage'), 'Equipamento atualizado com sucesso!', 'success');
                    setTimeout(() => {
                        closeEditEquipmentModal();
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(document.getElementById('editEquipmentMessage'), result.message || 'Erro ao atualizar equipamento.', 'error');
                }
            } catch (error) {
                console.error('Erro ao atualizar equipamento:', error);
                showMessage(document.getElementById('editEquipmentMessage'), 'Ocorreu um erro ao atualizar o equipamento. Tente novamente.', 'error');
            } finally {
                toggleLoadingState('editEquipmentSpinner', 'saveEditEquipmentButton', false);
            }
        });

        // Lógica para excluir equipamento
        async function deleteEquipment(equipmentId) {
            closeConfirmDeleteModal();
            try {
                const response = await fetch('delete_equipment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_equipamento: equipmentId })
                });
                const data = await response.json();

                if (data.success) {
                    // Substituindo o alert por uma mensagem no próprio modal, se necessário
                    // ou apenas recarregando a página para mostrar a alteração
                    location.reload(); 
                } else {
                    alert(data.message || 'Erro ao excluir equipamento.');
                }
            } catch (error) {
                console.error('Erro ao excluir equipamento:', error);
                alert('Ocorreu um erro ao excluir o equipamento. Tente novamente.');
            }
        }
        
        // Renderiza os equipamentos quando a página é carregada
        document.addEventListener('DOMContentLoaded', renderEquipments);
    </script>
</body>
</html>
