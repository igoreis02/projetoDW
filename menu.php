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
    <title>Menu</title>
    <style>

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

        /* Estilos para responsividade */
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
        
        /* Contêiner principal do layout flexível */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Estilo para a barra lateral */
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
            justify-content: flex-start; /* Alinha o conteúdo à esquerda quando expandida */
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
        
        /* CORREÇÃO: Permite que o texto quebre a linha e impede que ele ultrapasse o limite */
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
        
        /* Estilos para a tooltip */
        .sidebar-icons-left:not(.expanded) a::after {
            content: attr(data-title);
            position: absolute;
            left: 80px; /* Posição à direita do ícone */
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
        /* Estilos para o botão de alternância do menu */
        .menu-toggle-btn {
            position: fixed; 
            top: 20px;
            left: 80px; /* Posicionamento depois da barra de navegação */
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
            transition: left 0.3s ease; /* Adiciona transição para a propriedade left */
        }
        
        .menu-toggle-btn.menu-shifted {
            left: 260px; /* Nova posição para o botão quando o menu está aberto (250px do menu + 10px de margem) */
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
            display: none; /* Esconde por padrão */
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .sidebar-user-info.visible {
            display: block; /* Mostra ao expandir */
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            
            <a href="usuarios" data-title="Gerenciar Usuários">
                <i class="fas fa-users-cog" ></i>
                <span>Gerenciar Usuários</span>
            </a>
            <a href="cidades" data-title="Gerenciar Cidades">
                <i class="fas fa-city" ></i>
                <span>Gerenciar Cidades</span>
            </a>
            <a href="equipamentos" data-title="Informações dos Equipamentos">
                <i class="fas fa-server" ></i>
                <span>Informações dos Equipamentos</span>
            </a>
            <a href="veiculos" data-title="Veículos">
                <i class="fas fa-car"></i>
                <span>Veículos</span>
            </a>
            <a href="provedores" data-title="Provedores">
                <i class="fas fa-network-wired"></i>
                <span>Provedores</span>
            </a>
            <a href="logout" data-title="Sair">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    
        <div class="content-container">
            <button id="menu-toggle-btn" class="menu-toggle-btn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="card">
                
                <h1 class="card-header">Menu Principal</h1>
                <div class="menu-buttons-container">
                    <div class="menu-row">
                        <a href="#" class="menu-button" id="manutencao">
                            <i class="fas fa-tools"></i>
                            <span>Manutenções/Instalações</span>
                        </a>
                        <a href="#" class="menu-button" id="manutencaoPendentes">
                            <i class="fas fa-clock"></i>
                            <span>
                                Atribuir Técnico Ocorrências Pendentes</span>
                        </a>
                        <a href="#" class="menu-button" id="ocorrenciaProvedores">
                            <i class="fas fa-network-wired"></i>
                            <span>Ocorrência Provedores</span>
                        </a>
                    </div>
                    <div class="menu-row">
                        <a href="#" class="menu-button" id="manutencaoAndamento">
                            <i class="fas fa-cogs"></i>
                            <span>Ocorrências Em Andamento</span>
                        </a>
                        
                        <a href="#" class="menu-button" id="gestaoOcorrencias">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Gestão de Ocorrências</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-icons');
            const menuToggleBtn = document.getElementById('menu-toggle-btn');
            const contentContainer = document.querySelector('.content-container');
            const userInfoContainer = document.getElementById('user-info-container');
            const userNameDisplay = document.getElementById('user-name-display');
            const userEmailDisplay = document.getElementById('user-email-display');
            const userTypeDisplay = document.getElementById('user-type-display');
            const changePasswordBtn = document.getElementById('change-password-btn');

            const userName = '<?php echo $user_name; ?>';
            const userEmail = '<?php echo $user_email; ?>';
            const userType = '<?php echo $user_type; ?>';

            userNameDisplay.textContent = userName;
            userEmailDisplay.textContent = userEmail;
            userTypeDisplay.textContent = userType;

            menuToggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('expanded');
                contentContainer.classList.toggle('sidebar-open');
                menuToggleBtn.classList.toggle('menu-shifted'); // Adiciona/remove a classe para mover o botão
                
                if (sidebar.classList.contains('expanded')) {
                    userInfoContainer.style.display = 'block';
                } else {
                    userInfoContainer.style.display = 'none';
                }
            });
            
            if (changePasswordBtn) {
                changePasswordBtn.addEventListener('click', function() {
                    window.location.href = 'change_password.php';
                });
            }

            const manutencaoButton = document.getElementById('manutencao');
            if (manutencaoButton) {
                manutencaoButton.addEventListener('click', function() {
                    window.location.href = 'manutencoes_instalacoes';
                });
            }
            
            
            const manutencaoAndamentoButton = document.getElementById('manutencaoAndamento');
            if (manutencaoAndamentoButton) {
                manutencaoAndamentoButton.addEventListener('click', function() {
                    window.location.href = 'ocorrenciasEmAndamento';
                });
            }

            const manutencaoPendentesButton = document.getElementById('manutencaoPendentes');
            if (manutencaoPendentesButton) {
                manutencaoPendentesButton.addEventListener('click', function() {
                    window.location.href = 'ocorrenciasPendentes';
                });
            }

            const atribuirTecnicoButton = document.getElementById('atribuirTecnico');
            if (atribuirTecnicoButton) {
                atribuirTecnicoButton.addEventListener('click', function() {
                    window.location.href = 'atribuir_tecnico';
                });
            }

            
            const ocorrenciaProvedoresButton = document.getElementById('ocorrenciaProvedores');
            if (ocorrenciaProvedoresButton) {
                ocorrenciaProvedoresButton.addEventListener('click', function() {
                    window.location.href = 'ocorrenciaProvedores';
                });
            }

            const gestaoOcorrenciasButton = document.getElementById('gestaoOcorrencias');
            if (gestaoOcorrenciasButton) {
                gestaoOcorrenciasButton.addEventListener('click', function() {
                    window.location.href = 'gestaoOcorrencias';
                });
            }

            const logoutButton = document.querySelector('.sidebar-icons-left a[href="logout.php"]');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (confirm('Tem certeza que deseja sair?')) {
                        window.location.href = 'logout.php';
                    }
                });
            }
        });
    </script>
</body>

</html>