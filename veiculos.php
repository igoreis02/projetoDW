<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}
require_once 'conexao_bd.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Veículos</title>
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

        .container-botao-adicionar {
            width: 100%;
            display: flex;
            justify-content: flex-start;
            margin-bottom: 1rem;
        }

        .botao-adicionar {
            background-color: var(--cor-principal);
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-adicionar:hover {
            background-color: var(--cor-secundaria);
        }
        
        .lista-veiculos {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }

        .item-veiculo {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            position: relative;
        }

        .item-veiculo h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .item-veiculo p {
            margin: 0.25rem 0;
            color: #555;
        }

        .item-veiculo .btn-group {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 5px;
        }
        
        .item-veiculo .botao-editar,
        .item-veiculo .botao-excluir {
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .item-veiculo .botao-editar {
            background-color: #ffc107;
            color: #333;
        }

        .item-veiculo .botao-excluir {
            background-color: #dc3545;
        }
        
        /* Estilos do modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .modal.esta-ativo {
            display: flex;
        }

        .conteudo-modal {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 500px;
        }

        .fechar-modal {
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }

        .formulario-modal label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
            color: #333;
        }

        .formulario-modal input {
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .formulario-modal button {
            width: 100%;
            padding: 1rem;
            margin-top: 1.5rem;
            border-radius: 0.5rem;
            border: none;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .formulario-modal .botao-salvar {
            background-color: #4CAF50;
        }

        .mensagem {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .mensagem.sucesso { background-color: #d4edda; color: #155724; }
        .mensagem.erro { background-color: #f8d7da; color: #721c24; }

        .carregando {
            border: 4px solid rgba(0, 0, 0, .1);
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border-left-color: #fff;
            animation: spin 1.2s linear infinite;
            display: none;
            margin: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="titulo-cabecalho">Gerenciar Veículos</h1>
        </header>
        <div class="container-botao-adicionar">
            <button class="botao-adicionar" onclick="abrirModalAdicionarVeiculo()">Adicionar Veículo</button>
        </div>
        <div id="containerListaVeiculos">
        </div>
    </main>

    <div id="modalAdicionarVeiculo" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalAdicionarVeiculo()">&times;</span>
            <h2>Adicionar Novo Veículo</h2>
            <form id="formularioAdicionarVeiculo" class="formulario-modal">
                <label for="nomeVeiculoAdicionar">Nome:</label>
                <input type="text" id="nomeVeiculoAdicionar" name="nome" required>
                
                <label for="placaVeiculoAdicionar">Placa:</label>
                <input type="text" id="placaVeiculoAdicionar" name="placa" required>

                <label for="modeloVeiculoAdicionar">Modelo:</label>
                <input type="text" id="modeloVeiculoAdicionar" name="modelo" required>
                
                <div id="mensagemAdicionarVeiculo" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span>Adicionar</span>
                    <span id="carregandoAdicionarVeiculo" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <div id="modalEdicaoVeiculo" class="modal">
        <div class="conteudo-modal">
            <span class="fechar-modal" onclick="fecharModalEdicaoVeiculo()">&times;</span>
            <h2>Editar Veículo</h2>
            <form id="formularioEdicaoVeiculo" class="formulario-modal">
                <input type="hidden" id="idVeiculoEdicao" name="id_veiculo">
                
                <label for="nomeVeiculoEdicao">Nome:</label>
                <input type="text" id="nomeVeiculoEdicao" name="nome" required>

                <label for="placaVeiculoEdicao">Placa:</label>
                <input type="text" id="placaVeiculoEdicao" name="placa" required>

                <label for="modeloVeiculoEdicao">Modelo:</label>
                <input type="text" id="modeloVeiculoEdicao" name="modelo" required>
                
                <div id="mensagemEdicaoVeiculo" class="mensagem" style="display: none;"></div>
                <button type="submit" class="botao-salvar">
                    <span>Salvar Alterações</span>
                    <span id="carregandoEdicaoVeiculo" class="carregando"></span>
                </button>
            </form>
        </div>
    </div>

    <script src="js/veiculos.js"></script>
</body>
</html>