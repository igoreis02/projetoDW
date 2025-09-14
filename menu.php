<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'entregador') {
    header('Location: lista_pedidos_em_entrega.html');
    exit();
}

require_once 'conexao_bd.php'; // Incluindo a conexão com o banco de dados para buscar o nome do usuário

$user_name = 'Usuário'; // Valor padrão
$user_type = 'Desconhecido';
if (isset($user_id)) {
    $stmt = $conn->prepare("SELECT nome, tipo_usuario FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_name = $user_data['nome'];
        $user_type = $user_data['tipo_usuario'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Menu</title>
    <style>
        /* --- SEU CSS ORIGINAL (INALTERADO) --- */
        .card:before {
            content: none;
        }

        .menu-buttons-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px;
        }

        .menu-row {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }

        .menu-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            background-color: #112058;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 120px;
        }

        .menu-button i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .menu-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 900px;
            margin: 40px auto;
            position: relative;
        }

        .card-header {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .menu-row {
                flex-direction: column;
            }

            .menu-button {
                width: 100%;
            }

            .card {
                margin: 20px auto;
                padding: 1rem;
            }

            .card-header {
                font-size: 1.5rem;
            }
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-icons-left {
            position: fixed;
            top: 0;
            left: 0;
            width: 70px;
            height: 100vh;
            background-color: #112058;
            padding: 10px 0;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            transition: width 0.3s ease;
            z-index: 1000;
        }

        .sidebar-icons-left.expanded {
            width: 250px;
        }

        .sidebar-icons-left a {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            color: white;
            text-decoration: none;
            padding: 10px;
            margin-bottom: 10px;
            width: 100%;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
        }

        .sidebar-icons-left.expanded a {
            justify-content: flex-start;
        }

        .sidebar-icons-left a:first-of-type {
            margin-top: auto;
        }

        .sidebar-icons-left a:hover {
            background-color: rgba(9, 16, 43, 0.2);
        }

        .sidebar-icons-left a i {
            font-size: 1.5rem;
            min-width: 30px;
            text-align: center;
        }

        .sidebar-icons-left a span {
            display: none;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 150px;
        }

        .sidebar-icons-left.expanded a span {
            display: block;
        }

        .sidebar-icons-left:not(.expanded) a::after {
            content: attr(data-title);
            position: absolute;
            left: 80px;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .sidebar-icons-left:not(.expanded) a:hover::after {
            opacity: 1;
            visibility: visible;
        }

        .menu-toggle-btn {
            position: fixed;
            top: 20px;
            left: 80px;
            width: 30px;
            height: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            z-index: 1001;
            background: none;
            border: none;
            padding: 0;
            transition: left 0.3s ease;
        }

        .menu-toggle-btn.menu-shifted {
            left: 260px;
        }

        .menu-toggle-btn span {
            display: block;
            width: 100%;
            height: 3px;
            background-color: #333;
            transition: all 0.3s ease;
        }

        .content-container {
            flex-grow: 1;
            padding: 20px;
            margin-left: 70px;
            transition: margin-left 0.3s ease;
        }

        .content-container.sidebar-open {
            margin-left: 250px;
        }

        .sidebar-user-info {
            padding: 20px 10px;
            color: white;
            text-align: center;
            display: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .sidebar-user-info.visible {
            display: block;
        }

        .sidebar-user-info p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .btn-change-password {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 0.8rem;
        }

        .btn-change-password:hover {
            background-color: #0056b3;
        }

        /* --- NOVO CSS ADICIONADO PARA A NOTIFICAÇÃO --- */
        .notification-toast {
            position: fixed;
            bottom: 20px;
            right: -400px;
            /* Começa fora da tela para animar a entrada */
            width: 380px;
            background-color: #2c3e50;
            /* Cor escura elegante */
            color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: 'Inter', sans-serif;
            z-index: 2000;
            transition: right 0.5s ease-in-out;
            /* Animação suave de entrada e saída */
            cursor: pointer;
            /* Torna a notificação clicável */
        }

        .notification-toast.show {
            right: 20px;
            /* Posição final na tela quando visível */
        }

        .notification-toast i {
            font-size: 1.8rem;
            color: #f1c40f;
            /* Cor de ícone para aviso */
        }

        .notification-toast .message {
            flex-grow: 1;
            font-size: 1rem;
            font-weight: 500;
        }

        .notification-toast .close-notification {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .notification-toast .close-notification:hover {
            opacity: 1;
        }
    </style>
    
</head>

<body>

    <div class="main-wrapper">
        <div id="sidebar-icons" class="sidebar-icons-left">
            <div id="user-info-container" class="sidebar-user-info">
                <p><strong> <span id="user-name-display"></span></strong></p>
                <p><strong>Email:</strong> <span id="user-email-display"></span></p>
                <p><strong>Tipo:</strong> <span id="user-type-display"></span></p>
                <button id="change-password-btn" class="btn-change-password">Alterar Senha</button>
            </div>
            <a href="relatorios.php" data-title="Relatórios"><i class="fas fa-file-alt"></i><span>Relatórios</span></a>
            <a href="usuarios" data-title="Gerenciar Usuários"><i class="fas fa-users-cog"></i><span>Gerenciar
                    Usuários</span></a>
            <a href="cidades" data-title="Gerenciar Cidades"><i class="fas fa-city"></i><span>Gerenciar
                    Cidades</span></a>
            <a href="equipamentos" data-title="Informações dos Equipamentos"><i
                    class="fas fa-server"></i><span>Informações dos Equipamentos</span></a>
            <a href="veiculos" data-title="Veículos"><i class="fas fa-car"></i><span>Veículos</span></a>
            <a href="provedores" data-title="Provedores"><i class="fas fa-network-wired"></i><span>Provedores</span></a>
            <a href="logout.php" data-title="Sair"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>

        <div class="content-container">
            <button id="menu-toggle-btn" class="menu-toggle-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="card">
                <div class="menu-buttons-container">
                    <div class="menu-row">
                        <a href="manutencoes_instalacoes.php" class="menu-button" id="manutencao"><i
                                class="fas fa-tools"></i><span>Ocorrências e Instalações</span></a>
                        <a href="ocorrenciasPendentes.php" class="menu-button" id="manutencaoPendentes"><i
                                class="fas fa-clock"></i><span>Atribuir Técnico Ocorrências Pendentes</span></a>
                        <a href="ocorrenciaProvedores.php" class="menu-button" id="ocorrenciaProvedores"><i
                                class="fas fa-network-wired"></i><span>Ocorrência Provedores</span></a>
                    </div>
                    <div class="menu-row">
                        <a href="ocorrenciasEmAndamento.php" class="menu-button" id="manutencaoAndamento"><i
                                class="fas fa-cogs"></i><span>Ocorrências Em Andamento</span></a>
                        <a href="ocorrenciaProcessamento.php" class="menu-button" id="ocorrenciaProcessamento"><i
                                class="fas fa-clipboard-check"></i><span>Ocorrência Processamento</span></a>
                    </div>
                    <div class="menu-row">
                        <a href="solicitacoesClientes.php" class="menu-button" id="solicitacaoClientesBtn"><i
                                class="fas fa-headset"></i><span>Solicitação Clientes</span></a>
                        <a href="lacresImetro.php" class="menu-button" id="lacresImetroBtn"><i
                                class="fas fa-stamp"></i><span>Lacres IMETRO</span></a>
                        <a href="#" class="menu-button" id="afericoesBtn"><i
                                class="fas fa-gauge-high"></i><span>Aferições</span></a>
                    </div>
                    <div class="menu-row">
                        <a href="gestaoOcorrencias.php" class="menu-button" id="gestaoOcorrencias"><i
                                class="fas fa-check-circle"></i><span>Gestão de Ocorrências</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="validation-toast" class="notification-toast">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="message">
            Ocorrência aguardando validação do processamento.
        </div>
        <button class="close-notification">&times;</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            const sidebar = document.getElementById('sidebar-icons');
            const menuToggleBtn = document.getElementById('menu-toggle-btn');
            const contentContainer = document.querySelector('.content-container');
            const userInfoContainer = document.getElementById('user-info-container');
            const userNameDisplay = document.getElementById('user-name-display');
            const userEmailDisplay = document.getElementById('user-email-display');
            const userTypeDisplay = document.getElementById('user-type-display');
            const changePasswordBtn = document.getElementById('change-password-btn');

            const userName = <?php echo json_encode($user_name); ?>;
            const userEmail = <?php echo json_encode($user_email); ?>;
            const userType = <?php echo json_encode($user_type); ?>;

            userNameDisplay.textContent = userName;
            userEmailDisplay.textContent = userEmail;
            userTypeDisplay.textContent = userType;

            menuToggleBtn.addEventListener('click', function () {
                sidebar.classList.toggle('expanded');
                contentContainer.classList.toggle('sidebar-open');
                menuToggleBtn.classList.toggle('menu-shifted');
                if (sidebar.classList.contains('expanded')) {
                    userInfoContainer.style.display = 'block';
                } else {
                    userInfoContainer.style.display = 'none';
                }
            });

            if (changePasswordBtn) {
                changePasswordBtn.addEventListener('click', function () {
                    window.location.href = 'change_password.php';
                });
            }

            
            document.getElementById('manutencao').addEventListener('click', () => window.location.href = 'manutencoes_instalacoes.php');
            document.getElementById('manutencaoPendentes').addEventListener('click', () => window.location.href = 'ocorrenciasPendentes.php');
            document.getElementById('ocorrenciaProvedores').addEventListener('click', () => window.location.href = 'ocorrenciaProvedores.php');
            document.getElementById('manutencaoAndamento').addEventListener('click', () => window.location.href = 'ocorrenciasEmAndamento.php');
            document.getElementById('ocorrenciaProcessamento').addEventListener('click', () => window.location.href = 'ocorrenciaProcessamento.php');
            document.getElementById('solicitacaoClientesBtn').addEventListener('click', () => window.location.href = 'solicitacoesClientes.php');
            document.getElementById('lacresImetroBtn').addEventListener('click', () => window.location.href = 'lacresImetro.php');
            document.getElementById('afericoesBtn').addEventListener('click', () => alert('Funcionalidade "Aferições" ainda não implementada.'));
            document.getElementById('gestaoOcorrencias').addEventListener('click', () => window.location.href = 'gestaoOcorrencias.php');

            const logoutButton = document.querySelector('.sidebar-icons-left a[href="logout.php"]');
            if (logoutButton) {
                logoutButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (confirm('Tem certeza que deseja sair?')) {
                        window.location.href = 'logout.php';
                    }
                });
            }

            
            const toast = document.getElementById('validation-toast');
            const closeToastBtn = toast.querySelector('.close-notification');

            // Função para verificar se há validações pendentes
            async function checkPendingValidations() {
                try {
                    const response = await fetch('API/check_validacoes_pendentes.php');
                    const result = await response.json();

                    if (result.success && result.data.has_pending_validation) {
                        toast.classList.add('show'); // Mostra a notificação se houver
                    } else {
                        toast.classList.remove('show'); // Garante que ela esteja escondida se não houver
                    }
                } catch (error) {
                    console.error('Erro ao verificar validações pendentes:', error);
                }
            }

            // A notificação também funciona como um botão para ir à página de processamento
            toast.addEventListener('click', function (event) {
                // A ação só ocorre se o clique não for no botão de fechar
                if (event.target !== closeToastBtn) {
                    window.location.href = 'ocorrenciaProcessamento.php';
                }
            });

            // Adiciona o evento para fechar a notificação ao clicar no 'X'
            closeToastBtn.addEventListener('click', function () {
                toast.classList.remove('show');
            });

            // Executa a verificação assim que a página carrega
            checkPendingValidations();

            // Configura o intervalo para verificar a cada 5 minutos (300000 milissegundos)
            setInterval(checkPendingValidations, 300000);
        });
    </script>
</body>

</html>