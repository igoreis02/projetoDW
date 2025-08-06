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
            width: 100%;
        }

        /* Estilos para cada linha de botões */
        .menu-row {
            display: flex;
            justify-content: space-between;
            gap: 15px; /* Espaçamento entre botões na mesma linha */
        }

        /* Para as linhas com três botões, ajustar o espaçamento */
        .menu-row.three-buttons .menu-button {
            flex: 1; /* Faz os botões ocuparem espaço igualmente */
            min-width: 0; /* Permite que eles diminuam se necessário */
        }

        /* Estilo básico para os botões do menu */
        .menu-button {
            flex: 1; /* Garante que os botões se expandam para preencher o espaço */
            padding: 20px;
            font-size: 1.2em;
            color: white;
            background-color: var(--cor-principal);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-align: center;
            text-decoration: none; /* Em caso de ser um <a> estilizado como botão */
            display: flex; /* Para centralizar o texto verticalmente */
            align-items: center;
            justify-content: center;
            box-sizing: border-box; /* Inclui padding na largura total */
        }

        .menu-button:hover {
            background-color: var(--cor-secundaria);
            transform: translateY(-3px);
        }

        /* Estilo para o botão "Sair" na parte inferior */
        .voltar-btn {
            display: block;
            width: 50%;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            background-color: var(--botao-voltar); /* Cor de destaque para sair */
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

        /* Estilos para os Modais (Redefinição de Senha e Novo Usuário) */
        .modal {
            display: none; /* Escondido por padrão */
            position: fixed; /* Fica por cima de tudo */
            z-index: 1000; /* Z-index alto para ficar acima de outros elementos */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto; /* Habilita scroll se o conteúdo for muito grande */
            background-color: rgba(0, 0, 0, 0.7); /* Fundo semi-transparente */
            /* Removemos display: flex; daqui, ele será aplicado apenas quando a classe is-active estiver presente */
        }

        .modal.is-active { /* Nova classe para quando o modal está ativo */
            display: flex; /* Exibe o modal e o centraliza */
            justify-content: center;
            align-items: center;
        }

        .modal-content.card {
            background-color: #fefefe;
            margin: auto;
            border: 1px solid #888;
            width: 90%; /* Ajuste a largura conforme necessário */
            max-width: 500px; /* Largura máxima */
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative; /* Para o botão fechar */
        }
        
        /* Para o card no modal não ter o pseudo-elemento ::before */
        .modal-content.card::before {
            content: none;
        }

        .modal-content.card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: var(--cor-principal);
        }

        .modal-content.card label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .modal-content.card input[type="text"],
        .modal-content.card input[type="email"],
        .modal-content.card input[type="password"],
        .modal-content.card select,
        .modal-content.card textarea { /* Adicionado textarea para endereço */
            width: calc(100% - 20px); /* 100% menos o padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .modal-content.card button {
            width: 100%;
            padding: 12px;
            background-color: var(--cor-principal);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .modal-content.card button:hover {
            background-color: var(--cor-secundaria);
        }

        .modal-content.card .message {
            margin-top: 15px;
            text-align: center;
            color: red; /* Cor padrão para erro */
            font-weight: bold;
        }
        /* Para mensagens de sucesso no modal */
        .modal-content.card .message.success {
            color: green;
        }


        .modal-content.card .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }

        .modal-content.card .close:hover,
        .modal-content.card .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Ajustes para telas menores */
        @media (max-width: 768px) {
            .menu-row {
                flex-direction: column; /* Empilha botões verticalmente em telas menores */
            }
            .menu-button {
                width: 100%; /* Botões ocupam a largura total em telas menores */
            }
            .modal-content.card {
                width: 95%; /* Ocupa mais largura em telas menores */
            }
            .logoMenu {
                width: 60%; /* Ajusta a largura do logo em telas menores */
                top: -40px;
            }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
       
        <h2>Menu Principal</h2>

        <div class="menu-buttons-container" id="menuButtons">
            <div class="menu-row">
                <button class="menu-button" id="manutencao">Manutenções/Instalações</button>
                <button class="menu-button" id="atribuirManutencao">Atribuir técnico/manutenção</button>
                <button class="menu-button" id="manutencaoAndamento">Manutenção/Instalação em andamento</button>
            </div>
            <div class="menu-row three-buttons">
                <button class="menu-button" id="gerenciamentoManutencao">Gerenciamento de Manutenções</button>
                <button class="menu-button" id="gerenciamentoInstalacoes">Gerenciamento de Instalações</button>
            </div>
            <div class="menu-row">
                <button class="menu-button" id="gerenciarUsuarios">Gerenciar Usuários</button>
                <button class="menu-button" id="relatorios">Relatórios</button>
                <button class="menu-button" id="configuracoes">Configurações</button>
        </div>
        </div>
        <a href="logout.php" class="voltar-btn">Sair</a>
    </div>
    <div class="footer">
        <p>&copy; 2025 APsystem. Todos os direitos reservados.</p>
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

    

</script>
</body>

</html> 
