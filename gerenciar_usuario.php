<?php
session_start(); // Inicia ou resume a sessão
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
  // Redireciona para a página de login
  header("Location: index.html");
  exit;
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Opcional: Redirecionar se o tipo de usuário não tiver permissão para gerenciar usuários
// Por exemplo, apenas administradores e provedores podem acessar esta página
if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] !== 'administrador' && $_SESSION['tipo_usuario'] !== 'provedor')) {
    header('Location: menu.php'); // Redireciona para o menu se não tiver permissão
    exit();
}

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gerenciamento_manutencoes"; // Nome do seu banco de dados

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro de conexão com o banco de dados: " . $conn->connect_error);
}

$users = [];
$errorMessage = '';

try {
    // Prepara a consulta SQL para buscar todos os usuários, incluindo id_usuario para edição
    $stmt = $conn->prepare("SELECT id_usuario, nome, email, telefone, tipo_usuario, status_usuario FROM Usuario ORDER BY nome ASC");
    
    if ($stmt === false) {
        throw new Exception("Erro ao preparar a consulta: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } else {
        $errorMessage = 'Nenhum usuário cadastrado no sistema.';
    }

    $stmt->close();

} catch (Exception $e) {
    $errorMessage = 'Erro ao carregar usuários: ' . $e->getMessage();
    error_log("Erro em gerenciar_usuario.php: " . $e->getMessage());
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
    <title>Gerenciar Usuários</title>
    <style>
        /* Estilos do card e layout geral, adaptados de manutencoes_instalacoes.php */
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
            max-width: 900px; /* Aumentado para acomodar a tabela de usuários e o modal */
            text-align: center;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card:before {
            content: none;
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

        /* Estilos para a tabela de usuários */
        .user-table-container {
            width: 100%;
            overflow-x: auto; /* Adiciona rolagem horizontal para tabelas grandes em telas pequenas */
            margin-top: 20px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 0.9em;
        }

        .user-table th {
            background-color: var(--cor-principal);
            color: white;
            font-weight: bold;
        }

        .user-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .user-table tr:hover {
            background-color: #ddd;
        }

        /* Estilo para o botão "Editar" na tabela */
        .edit-button-table {
            padding: 8px 12px;
            background-color: #4CAF50; /* Verde */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.3s ease;
        }

        .edit-button-table:hover {
            background-color: #45a049;
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

        /* Mensagens de erro/sucesso */
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

        /* Estilos para os Modais */
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
            text-align: left; /* Alinha o texto dentro do modal à esquerda */
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--cor-principal);
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .modal-content input[type="text"],
        .modal-content input[type="email"],
        .modal-content input[type="tel"],
        .modal-content select,
        .modal-content input[type="password"] { /* Adicionado input[type="password"] */
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
            display: flex; /* Para alinhar spinner e texto */
            align-items: center;
            justify-content: center;
        }

        .modal-content .form-buttons .save-button {
            background-color: #28a745; /* Verde */
            color: white;
        }

        .modal-content .form-buttons .save-button:hover {
            background-color: #218838;
        }

        .modal-content .form-buttons .cancel-button {
            background-color: #dc3545; /* Vermelho */
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

        /* Estilos para o novo botão Adicionar Usuário */
        .add-user-button-container {
            width: 100%;
            text-align: left; /* Alinha o botão à esquerda */
            margin-bottom: 20px; /* Espaçamento abaixo do botão */
        }

        .add-user-button {
            padding: 12px 20px;
            font-size: 1em;
            color: white;
            background-color: var(--cor-principal); /* Cor principal do seu tema */
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .add-user-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        /* Estilos para o spinner */
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3); /* Cor clara para o anel */
            border-top-color: #ffffff; /* Cor principal para o topo (visível) */
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px; /* Espaçamento entre o texto do botão e o spinner */
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            .voltar-btn {
                width: 70%;
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
            .user-table th, .user-table td {
                padding: 8px;
                font-size: 0.8em;
            }
            .edit-button-table {
                padding: 6px 10px;
                font-size: 0.7em;
            }
            .add-user-button {
                width: 100%; /* Botão Adicionar Usuário ocupa largura total em telas pequenas */
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Gerenciar Usuários</h2>

        <div class="add-user-button-container">
            <button id="addUsuarioBtn" class="add-user-button">Adicionar Usuário</button>
        </div>

        <div class="user-table-container">
            <?php if (!empty($errorMessage)): ?>
                <p class="message error"><?php echo $errorMessage; ?></p>
            <?php else: ?>
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Tipo de Usuário</th>
                            <th>Status</th>
                            <th>Opções</th> <!-- Nova coluna para opções -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($user['tipo_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($user['status_usuario']); ?></td>
                                <td>
                                    <button class="edit-button-table" 
                                            data-id="<?php echo htmlspecialchars($user['id_usuario']); ?>"
                                            data-nome="<?php echo htmlspecialchars($user['nome']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-telefone="<?php echo htmlspecialchars($user['telefone']); ?>"
                                            data-tipo="<?php echo htmlspecialchars($user['tipo_usuario']); ?>"
                                            data-status="<?php echo htmlspecialchars($user['status_usuario']); ?>">
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <!-- Modal de Edição de Usuário -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditUserModal()">&times;</span>
            <h3>Editar Usuário</h3>
            <form id="editUserForm">
                <input type="hidden" id="editUserId">
                
                <label for="editUserName">Nome:</label>
                <input type="text" id="editUserName" required>

                <label for="editUserEmail">E-mail:</label>
                <input type="email" id="editUserEmail" required>

                <label for="editUserPhone">Telefone:</label>
                <input type="tel" id="editUserPhone">

                <label for="editUserType">Tipo de Usuário:</label>
                <select id="editUserType" required>
                    <option value="administrador">Administrador</option>
                    <option value="tecnico">Técnico</option>
                    <option value="provedor">Provedor</option>
                    <option value="comum">Comum</option>
                </select>

                <label for="editUserStatus">Status do Usuário:</label>
                <select id="editUserStatus" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="pendente">Pendente</option>
                </select>

                <div class="form-buttons">
                    <button type="submit" class="save-button" id="saveEditUserButton">
                        Salvar Alterações
                        <span id="editUserSpinner" class="loading-spinner hidden"></span>
                    </button>
                    <button type="button" class="cancel-button" onclick="closeEditUserModal()">Cancelar</button>
                </div>
                <p id="editUserMessage" class="message hidden"></p>
            </form>
        </div>
    </div>

    <!-- Novo Modal para Adicionar Usuário -->
    <div id="addUsuarioModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddUserModal()">&times;</span>
            <h3>Adicionar Novo Usuário</h3>
            <form id="addUsuarioForm">
                <label for="addUserName">Nome:</label>
                <input type="text" id="addUserName" required>

                <label for="addUserPhone">Telefone:</label>
                <input type="tel" id="addUserPhone">

                <label for="addUserType">Tipo de Usuário:</label>
                <select id="addUserType" required>
                    <option value="administrador">Administrador</option>
                    <option value="tecnico">Técnico</option>
                    <option value="provedor">Provedor</option>
                    <option value="comum">Comum</option>
                </select>

                <div class="form-buttons">
                    <button type="submit" class="save-button" id="saveAddUserButton">
                        Salvar Usuário
                        <span id="addUserSpinner" class="loading-spinner hidden"></span>
                    </button>
                    <button type="button" class="cancel-button" onclick="closeAddUserModal()">Cancelar</button>
                </div>
                <p id="addUserMessage" class="message hidden"></p>
            </form>
        </div>
    </div>

    <script>
        // Funções de utilidade
        // Definindo showMessage e hideMessage no objeto window para garantir acessibilidade global
        window.showMessage = function(element, msg, type) {
            element.textContent = msg;
            element.className = `message ${type}`;
            element.classList.remove('hidden');
        };

        window.hideMessage = function(element) {
            element.classList.add('hidden');
            element.textContent = '';
        };

        // Função para mostrar/esconder spinner e desabilitar/habilitar botão
        function toggleSpinner(button, spinner, show) {
            if (show) {
                spinner.classList.remove('hidden');
                button.disabled = true;
            } else {
                spinner.classList.add('hidden');
                button.disabled = false;
            }
        }

        // Referências aos elementos do modal de edição
        const editUserModal = document.getElementById('editUserModal');
        const editUserId = document.getElementById('editUserId');
        const editUserName = document.getElementById('editUserName');
        const editUserEmail = document.getElementById('editUserEmail');
        const editUserPhone = document.getElementById('editUserPhone');
        const editUserType = document.getElementById('editUserType');
        const editUserStatus = document.getElementById('editUserStatus');
        const editUserMessage = document.getElementById('editUserMessage');
        const editUserForm = document.getElementById('editUserForm');
        const saveEditUserButton = document.getElementById('saveEditUserButton'); // Novo
        const editUserSpinner = document.getElementById('editUserSpinner'); // Novo

        // Referência ao novo botão Adicionar Usuário
        const addUsuarioBtn = document.getElementById('addUsuarioBtn');

        // Referências aos elementos do modal de Adicionar Usuário
        const addUsuarioModal = document.getElementById('addUsuarioModal');
        const addUserNameInput = document.getElementById('addUserName');
        const addUserPhoneInput = document.getElementById('addUserPhone');
        const addUserTypeSelect = document.getElementById('addUserType');
        const addUserMessage = document.getElementById('addUserMessage');
        const addUsuarioForm = document.getElementById('addUsuarioForm');
        const saveAddUserButton = document.getElementById('saveAddUserButton'); // Novo
        const addUserSpinner = document.getElementById('addUserSpinner'); // Novo


        // Função para abrir o modal de edição
        function openEditUserModal(user) {
            editUserId.value = user.id;
            editUserName.value = user.nome;
            editUserEmail.value = user.email;
            editUserPhone.value = user.telefone;
            editUserType.value = user.tipo;
            editUserStatus.value = user.status;
            window.hideMessage(editUserMessage); // Usando window.hideMessage
            editUserModal.classList.add('is-active');
        }

        // Função para fechar o modal de edição
        function closeEditUserModal() {
            editUserModal.classList.remove('is-active');
        }

        // Função para abrir o modal de Adicionar Usuário
        function openAddUserModal() {
            // Limpa os campos do formulário ao abrir o modal
            addUserNameInput.value = '';
            addUserPhoneInput.value = '';
            addUserTypeSelect.value = 'comum'; // Define um valor padrão, se desejar
            window.hideMessage(addUserMessage); // Esconde mensagens anteriores
            addUsuarioModal.classList.add('is-active');
        }

        // Função para fechar o modal de Adicionar Usuário
        function closeAddUserModal() {
            addUsuarioModal.classList.remove('is-active');
        }

        // Função para formatar o número de telefone
        function formatPhoneNumber(value) {
            if (!value) return "";
            value = value.replace(/\D/g, ""); // Remove tudo que não é dígito
            let formattedValue = "";

            if (value.length > 0) {
                formattedValue += "(" + value.substring(0, 2);
            }
            if (value.length > 2) {
                formattedValue += ") " + value.substring(2, 3);
            }
            if (value.length > 3) {
                formattedValue += " " + value.substring(3, 7);
            }
            if (value.length > 7) {
                formattedValue += "-" + value.substring(7, 11);
            }
            return formattedValue;
        }

        // Adiciona event listener para formatar o telefone enquanto o usuário digita
        addUserPhoneInput.addEventListener('input', (event) => {
            event.target.value = formatPhoneNumber(event.target.value);
        });

        // Adiciona event listeners aos botões "Editar" na tabela
        document.querySelectorAll('.edit-button-table').forEach(button => {
            button.addEventListener('click', function() {
                const user = {
                    id: this.dataset.id,
                    nome: this.dataset.nome,
                    email: this.dataset.email,
                    telefone: this.dataset.telefone,
                    tipo: this.dataset.tipo,
                    status: this.dataset.status
                };
                openEditUserModal(user);
            });
        });

        // Lógica para submeter o formulário de edição (agora salva no DB)
        editUserForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const userId = editUserId.value;
            const userName = editUserName.value;
            const userEmail = editUserEmail.value;
            const userPhone = editUserPhone.value;
            const userType = editUserType.value;
            const userStatus = editUserStatus.value;

            window.showMessage(editUserMessage, 'Salvando alterações...', 'success'); 
            toggleSpinner(saveEditUserButton, editUserSpinner, true); // Mostra spinner e desabilita botão
            
            try {
                const response = await fetch('update_user.php', { // Chamando o novo script PHP
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: userId,
                        nome: userName,
                        email: userEmail,
                        telefone: userPhone,
                        tipo_usuario: userType,
                        status_usuario: userStatus
                    })
                });
                const data = await response.json();

                if (data.success) {
                    window.showMessage(editUserMessage, 'Usuário atualizado com sucesso!', 'success');
                    setTimeout(() => {
                        closeEditUserModal();
                        location.reload(); // Recarrega a página para mostrar os dados atualizados
                    }, 1500);
                } else {
                    window.showMessage(editUserMessage, data.message || 'Erro ao atualizar usuário.', 'error');
                }
            } catch (error) {
                console.error('Erro ao salvar usuário:', error);
                window.showMessage(editUserMessage, 'Ocorreu um erro ao salvar o usuário. Tente novamente.', 'error');
            } finally {
                toggleSpinner(saveEditUserButton, editUserSpinner, false); // Esconde spinner e habilita botão
            }
        });

        // Event listener para o botão "Adicionar Usuário"
        if (addUsuarioBtn) {
            addUsuarioBtn.addEventListener('click', function() {
                openAddUserModal(); // Abre o novo modal de adição de usuário
            });
        }

        // Lógica para submeter o formulário de Adicionar Usuário
        addUsuarioForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            const userName = addUserNameInput.value.trim();
            const userEmail = userName.split(' ')[0].toLowerCase() + '@apsystem.com.br'; // Gera o e-mail
            const userPhone = addUserPhoneInput.value.trim(); // Pega o valor formatado
            const userType = addUserTypeSelect.value;
            const defaultPassword = '12345'; // Senha padrão
            const defaultStatus = 'ativo'; // Status padrão

            window.showMessage(addUserMessage, 'Adicionando usuário...', 'success');
            toggleSpinner(saveAddUserButton, addUserSpinner, true); // Mostra spinner e desabilita botão

            try {
                const response = await fetch('add_user.php', { // Novo script PHP para adicionar usuário
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nome: userName,
                        email: userEmail, // Envia o e-mail gerado
                        telefone: userPhone,
                        tipo_usuario: userType,
                        senha: defaultPassword, // Envia a senha padrão
                        status_usuario: defaultStatus // Envia o status padrão
                    })
                });
                const data = await response.json();

                if (data.success) {
                    window.showMessage(addUserMessage, 'Usuário adicionado com sucesso!', 'success');
                    setTimeout(() => {
                        closeAddUserModal();
                        location.reload(); // Recarrega a página para mostrar o novo usuário
                    }, 1500);
                } else {
                    window.showMessage(addUserMessage, data.message || 'Erro ao adicionar usuário.', 'error');
                }
            } catch (error) {
                console.error('Erro ao adicionar usuário:', error);
                window.showMessage(addUserMessage, 'Ocorreu um erro ao adicionar o usuário. Tente novamente.', 'error');
            } finally {
                toggleSpinner(saveAddUserButton, addUserSpinner, false); // Esconde spinner e habilita botão
            }
        });

        // Fecha os modais se o usuário clicar fora deles
        window.onclick = function(event) {
            if (event.target == editUserModal) {
                closeEditUserModal();
            }
            if (event.target == addUsuarioModal) {
                closeAddUserModal();
            }
        }
    </script>
</body>

</html>
