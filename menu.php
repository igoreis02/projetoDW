<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Se o tipo de usuário for 'entregador', redirecione-o para a página de entrega,
// pois ele não deveria ter acesso direto ao menu.php.
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'entregador') {
    header('Location: lista_pedidos_em_entrega.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Menu</title>
    <style>
        .card:before {
            content: none; /* Remove o pseudo-elemento ::before do card */
        }
        /* Novo contêiner principal para agrupar todas as linhas de botões */
        .menu-buttons-container {
            display: flex;
            flex-direction: column; /* Empilha as linhas de botões verticalmente */
            gap: 15px; /* Espaçamento entre as linhas de botões */
            padding: 20px; /* Adiciona um padding interno para o container */
        }

        .menu-row {
            display: flex;
            justify-content: space-around; /* Distribui os botões uniformemente na linha */
            gap: 20px; /* Espaçamento entre os botões na mesma linha */
        }

        .menu-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1; /* Faz com que cada botão ocupe o mesmo espaço */
            background-color: #A6A6A6;
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 120px; /* Garante uma altura mínima para todos os botões */
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h1 class="card-header">Menu Principal</h1>
        <div class="menu-buttons-container">
            <div class="menu-row">
                <a href="#" class="menu-button" id="manutencao">
                    <i class="fas fa-tools"></i>
                    <span>Manutenções/Instalações</span>
                </a>
                
                <a href="#" class="menu-button" id="atribuirManutencao">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Atribuir Manutenção</span>
                </a>
            </div>
            <div class="menu-row">
                <a href="#" class="menu-button" id="manutencaoAndamento">
                    <i class="fas fa-cogs"></i>
                    <span>Manutenções Em Andamento</span>
                </a>
                <a href="#" class="menu-button" id="manutencaoAndamento">
                    <i class="fas fa-cogs"></i>
                    <span>Manutenções Em Pendentes</span>
                </a>
            </div>
            <div class="menu-row">
                <a href="#" class="menu-button" id="infoEquipamentos">
                    <i class="fas fa-server"></i>
                    <span>Informações dos Equipamentos</span>
                </a>
                <a href="#" class="menu-button" id="gerenciarCidades">
                    <i class="fas fa-city"></i>
                    <span>Gerenciar Cidades</span>
                </a>
                <a href="#" class="menu-button" id="gerenciarUsuarios">
                    <i class="fas fa-users-cog"></i>
                    <span>Gerenciar Usuários</span>
                </a>
            </div>
        </div>
    </div>
    <script>
        // Lógica para o botão "Manutenções/Instalações"
        const manutencaoButton = document.getElementById('manutencao');
        if (manutencaoButton) {
            manutencaoButton.addEventListener('click', function() {
                // Redireciona para a página de manutenções e instalações
                window.location.href = 'manutencoes_instalacoes.php';
            });
        }

        // Lógica para o botão "Gerenciar Usuários"
        const gerenciarUsuariosButton = document.getElementById('gerenciarUsuarios');
        if (gerenciarUsuariosButton) {
            gerenciarUsuariosButton.addEventListener('click', function() {
                // Redireciona para a página de gerenciamento de usuários
                window.location.href = 'gerenciar_usuario.php';
            });
        }

        const manutencaoAndamentoButton = document.getElementById('manutencaoAndamento');
        if (manutencaoAndamentoButton) {
            manutencaoAndamentoButton.addEventListener('click', function() {
                // Redireciona para a página de manutenções e instalações em andamento
                window.location.href = 'manutencao_EmAndamento.php';
            });
        }

        const atribuirManutencaoButton = document.getElementById('atribuirManutencao');
        if (atribuirManutencaoButton) {
            atribuirManutencaoButton.addEventListener('click', function() {
                // Redireciona para a página de atribuição de manutenções
                window.location.href = 'atribuir_tecnico.php';
            });
        }

        const infoEquipamentosButton = document.getElementById('infoEquipamentos');
        if (infoEquipamentosButton) {
            infoEquipamentosButton.addEventListener('click', function() {
                // Redireciona para a página de informações de equipamentos
                window.location.href = 'info_equipamentos.php';
            });
        }

        // NOVO: Lógica para o botão "Gerenciar Cidades"
        const gerenciarCidadesButton = document.getElementById('gerenciarCidades');
        if (gerenciarCidadesButton) {
            gerenciarCidadesButton.addEventListener('click', function() {
                // Redireciona para a nova página de gerenciamento de cidades
                window.location.href = 'info_cidade.php';
            });
        }
    </script>
</body>

</html>
