<?php
session_start();
// Ativa a exibição de erros para depuração (REMOVER EM PRODUÇÃO)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Inclui o arquivo de conexão com o banco de dados
require_once 'conexao_bd.php';

$cities = [];
$errorMessage = '';

try {
    // Consulta SQL para obter todas as cidades
    $sql_cidades = "SELECT id_cidade, nome FROM cidades ORDER BY nome ASC";
    $result_cidades = $conn->query($sql_cidades);

    if ($result_cidades === false) {
        throw new Exception("Erro ao executar a consulta de cidades: " . $conn->error);
    }

    if ($result_cidades->num_rows > 0) {
        while ($row = $result_cidades->fetch_assoc()) {
            $cities[] = $row;
        }
    } else {
        $errorMessage = 'Nenhuma cidade cadastrada no sistema.';
    }

} catch (Exception $e) {
    $errorMessage = 'Erro ao carregar dados: ' . $e->getMessage();
    error_log("Erro em info_cidade.php: " . $e->getMessage());
} finally {
    // A conexão com o banco de dados é fechada no final
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
    <title>Gerenciar Cidades</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 800px;
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

        .add-city-container {
            text-align: right;
            margin-bottom: 20px;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-add:hover {
            background-color: #218838;
        }

        .cities-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: #f8f9fa;
        }

        .cities-table th,
        .cities-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .cities-table th {
            background-color: #e9ecef;
            font-weight: 600;
            color: #495057;
        }

        .cities-table tr:hover {
            background-color: #e2f0ff;
        }

        .btn-group {
            display: flex;
            gap: 5px;
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
            max-width: 400px;
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

        .modal-form input {
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
        <h1 class="card-header">Gerenciar Cidades</h1>
        <div class="card-body">
            <div class="add-city-container">
                <button class="btn-add" onclick="openAddCityModal()">
                    <i class="fas fa-plus"></i> Adicionar Cidade
                </button>
            </div>
            <?php if (!empty($errorMessage)): ?>
                <p class="error-message"><?php echo $errorMessage; ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="cities-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Cidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($city['id_cidade']); ?></td>
                                    <td><?php echo htmlspecialchars($city['nome']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn-edit" onclick="openEditCityModal(<?php echo htmlspecialchars(json_encode($city)); ?>)">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                            <button class="btn-delete" onclick="deleteCity(<?php echo htmlspecialchars($city['id_cidade']); ?>)">
                                                <i class="fas fa-trash-alt"></i> Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Adicionar Cidade -->
    <div id="addCityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddCityModal()">&times;</span>
            <h2 class="modal-title">Adicionar Nova Cidade</h2>
            <form id="addCityForm" class="modal-form">
                <div class="form-group">
                    <label for="add-city-name">Nome da Cidade:</label>
                    <input type="text" id="add-city-name" name="nome" required>
                </div>
                <div id="addCityMessage" class="message-box"></div>
                <div class="btn-group-modal mt-3">
                    <button type="submit" id="saveAddCityButton" class="btn btn-primary">
                        <span id="saveAddCityButtonText">Adicionar</span>
                        <div id="addCitySpinner" class="spinner"></div>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeAddCityModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Edição de Cidade -->
    <div id="editCityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditCityModal()">&times;</span>
            <h2 class="modal-title">Editar Cidade</h2>
            <form id="editCityForm" class="modal-form">
                <input type="hidden" id="edit-city-id" name="id_cidade">
                <div class="form-group">
                    <label for="edit-city-name">Nome da Cidade:</label>
                    <input type="text" id="edit-city-name" name="nome" required>
                </div>
                <div id="editCityMessage" class="message-box"></div>
                <div class="btn-group-modal mt-3">
                    <button type="submit" id="saveEditCityButton" class="btn btn-primary">
                        <span id="saveEditCityButtonText">Salvar Alterações</span>
                        <div id="editCitySpinner" class="spinner"></div>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditCityModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Funções para os modais
        function openAddCityModal() {
            document.getElementById('addCityModal').style.display = 'block';
            document.getElementById('addCityMessage').innerHTML = '';
            document.getElementById('addCityForm').reset();
        }

        function closeAddCityModal() {
            document.getElementById('addCityModal').style.display = 'none';
        }

        function openEditCityModal(city) {
            document.getElementById('edit-city-id').value = city.id_cidade;
            document.getElementById('edit-city-name').value = city.nome;
            document.getElementById('editCityMessage').innerHTML = '';
            document.getElementById('editCityModal').style.display = 'block';
        }

        function closeEditCityModal() {
            document.getElementById('editCityModal').style.display = 'none';
        }

        // Fechar modais ao clicar fora deles
        window.onclick = function(event) {
            const addModal = document.getElementById('addCityModal');
            const editModal = document.getElementById('editCityModal');
            if (event.target == addModal) {
                closeAddCityModal();
            }
            if (event.target == editModal) {
                closeEditCityModal();
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
        
        // Lógica para adicionar cidade
        const addCityForm = document.getElementById('addCityForm');
        addCityForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            toggleLoadingState('addCitySpinner', 'saveAddCityButton', true);
            
            const formData = new FormData(addCityForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('add_city.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showMessage(document.getElementById('addCityMessage'), 'Cidade adicionada com sucesso!', 'success');
                    setTimeout(() => {
                        closeAddCityModal();
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(document.getElementById('addCityMessage'), result.message || 'Erro ao adicionar cidade.', 'error');
                }
            } catch (error) {
                console.error('Erro ao adicionar cidade:', error);
                showMessage(document.getElementById('addCityMessage'), 'Ocorreu um erro ao adicionar a cidade. Tente novamente.', 'error');
            } finally {
                toggleLoadingState('addCitySpinner', 'saveAddCityButton', false);
            }
        });
        
        // Lógica para editar cidade
        const editCityForm = document.getElementById('editCityForm');
        editCityForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            toggleLoadingState('editCitySpinner', 'saveEditCityButton', true);
            
            const formData = new FormData(editCityForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('update_city.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showMessage(document.getElementById('editCityMessage'), 'Cidade atualizada com sucesso!', 'success');
                    setTimeout(() => {
                        closeEditCityModal();
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(document.getElementById('editCityMessage'), result.message || 'Erro ao atualizar cidade.', 'error');
                }
            } catch (error) {
                console.error('Erro ao atualizar cidade:', error);
                showMessage(document.getElementById('editCityMessage'), 'Ocorreu um erro ao atualizar a cidade. Tente novamente.', 'error');
            } finally {
                toggleLoadingState('editCitySpinner', 'saveEditCityButton', false);
            }
        });

        // Lógica para excluir cidade
        async function deleteCity(id) {
            // Substitui o alert padrão por um modal personalizado se necessário
            if (confirm('Tem certeza que deseja excluir esta cidade?')) {
                try {
                    const response = await fetch('delete_city.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_cidade: id })
                    });
                    const result = await response.json();

                    if (result.success) {
                        alert('Cidade excluída com sucesso!');
                        location.reload();
                    } else {
                        alert(result.message || 'Erro ao excluir cidade.');
                    }
                } catch (error) {
                    console.error('Erro ao excluir cidade:', error);
                    alert('Ocorreu um erro ao excluir a cidade. Tente novamente.');
                }
            }
        }
    </script>
</body>

</html>
