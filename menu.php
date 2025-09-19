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

require_once 'conexao_bd.php'; 

$user_name = 'Usuário'; 
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
    <link rel="stylesheet" href="css/styleMenu.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Menu</title>
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
                                class="fas fa-tools"></i><span>Adicionar</span><span>Ocorrências e Instalações</span></a>
                        <a href="ocorrenciasPendentes.php" class="menu-button" id="manutencaoPendentes"><i
                                class="fas fa-clock"></i><span>Atribuir Técnico </span><span>Ocorrências Pendentes</span></a>
                        <a href="ocorrenciaProvedores.php" class="menu-button" id="ocorrenciaProvedores"><i
                                class="fas fa-network-wired"></i><span>Ocorrências Provedores</span></a>
                    </div>
                    <div class="menu-row">
                        <a href="ocorrenciasEmAndamento.php" class="menu-button" id="manutencaoAndamento"><i
                                class="fas fa-cogs"></i><span>Ocorrências Em Andamento</span></a>
                        <a href="ocorrenciaProcessamento.php" class="menu-button" id="ocorrenciaProcessamento"><i
                                class="fas fa-clipboard-check"></i><span>Ocorrências Processamento</span></a>
                    </div>
                    <div class="menu-row">
                        <a href="solicitacoesClientes.php" class="menu-button" id="solicitacaoClientesBtn"><i
                                class="fas fa-headset"></i><span>Solicitação Clientes</span></a>
                        <a href="lacresImetro.php" class="menu-button" id="lacresImetroBtn"><i
                                class="fas fa-stamp"></i><span>Lacres INMETRO</span></a>
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