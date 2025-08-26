<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}
require_once 'API/conexao_bd.php';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <title>Gerenciar Lacres INMETRO</title>
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
            width: 95%;
            max-width: 1200px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .cabecalho {
            width: 100%;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .titulo-cabecalho {
            flex-grow: 1;
            text-align: center;
            margin: 0;
            font-size: 2.2em;
            color: black;
        }

        .botao-voltar {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            color: #aaa;
            text-decoration: none;
            font-size: 2em;
        }

        .filtros-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .container-pesquisa-principal {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        #campoPesquisa {
            width: 100%;
            max-width: 600px;
            padding: 12px;
            font-size: 1.1em;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .filtros-pesquisa-data {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }

        .filtros-pesquisa-data select,
        .filtros-pesquisa-data input[type="date"] {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1em;
        }

        .filtros-pesquisa-data button {
            padding: 10px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            color: white;
            font-size: 1em;
            transition: background-color 0.2s ease, opacity 0.2s ease;
        }

        .btn-filtrar {
            background-color: var(--cor-principal);
        }

        .btn-limpar {
            background-color: #6c757d;
        }

        .btn-active {
            filter: brightness(85%);
        }

        .btn-inactive {
            opacity: 0.6;
        }

        .filtro-cidades {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
            width: 100%;
            margin-top: 1rem;
        }

        .filtro-cidades button {
            background-color: #e2e8f0;
            color: #4a5568;
            padding: 8px 16px;
            border: 1px solid #cbd5e0;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .filtro-cidades button.ativo {
            background-color: var(--cor-principal);
            color: white;
            border-color: var(--cor-principal);
        }

        .main-loading-state {
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
            color: #555;
            padding: 40px 0;
        }

        .main-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--cor-principal);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .lista-equipamentos-lacres {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            
        }

        .cidade-grupo h2 {
            text-align: left;
            color: var(--cor-secundaria);
            border-bottom: 2px solid var(--cor-principal);
            padding-bottom: 0.5rem;
        }

        .item-equipamento-lacre {
            background-color: #f9f9f9;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-align: left;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            margin: 1rem 0;
            border-left-color: #112058;
            border-left-width: 6px;
        }

        .item-equipamento-lacre.vencimento-proximo {
            border-left-color: #ef4444;
            border-left-width: 6px;
        }

        .equipamento-identificacao {
            font-size: 1.2em;
            font-weight: bold;
            color: #000;
        }

        .info-bloco p.vencimento-proximo-texto {
            color: #b91c1c;
            font-weight: bold;
        }

        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .notification {
            background-color: #fff;
            color: #333;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-left: 5px solid #f97316;
            transform: translateX(120%);
            transition: transform 0.5s ease-in-out;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification p {
            margin: 0 0 5px 0;
        }

        .notification strong {
            color: var(--cor-secundaria);
        }

        .equipamento-info {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .info-bloco h4 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--cor-secundaria);
        }

        .info-bloco p {
            margin: 0.25rem 0;
            color: #555;
        }

        .lacres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.5rem 1rem;
        }

        .lacre-item {
            background-color: #eef2ff;
            padding: 0.5rem;
            border-radius: 5px;
        }

        .lacre-item strong {
            color: #4338ca;
        }

        .equipamento-actions {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .botao-adicionar-lacres {
            background-color: var(--cor-principal);
            color: white;
            padding: 10px 20px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
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
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
        }

        .fechar-modal {
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
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

        .formulario-modal button[type="submit"] {
            width: 100%;
            padding: 1rem;
            margin-top: 1.5rem;
            border-radius: 0.5rem;
            border: none;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
        }

        .botao-salvar {
            background-color: #4CAF50;
        }

        .mensagem {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            text-align: center;
        }

        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
        }

        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
        }

        .botoes-toggle {
            display: flex;
            gap: 10px;
            margin-top: 0.5rem;
        }

        .botoes-toggle button {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            background-color: #f0f0f0;
            border-radius: 5px;
            cursor: pointer;
        }

        .botoes-toggle button.ativo {
            background-color: #112058;
            color: white;
            border-color: #112058;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="background"></div>
    <div id="notification-container"></div>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar" title="Voltar ao Menu">&larr;</a>
            <h1 class="titulo-cabecalho">Gerenciar Lacres INMETRO</h1>
        </header>

        <div class="filtros-container">
            <div class="container-pesquisa-principal">
                <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou referência...">
            </div>
            <div class="filtros-pesquisa-data">
                <select id="filtroTipoData">
                    <option value="">Filtrar por data...</option>
                    <option value="dt_afericao">Data de Aferição</option>
                    <option value="dt_vencimento">Data de Vencimento</option>
                </select>
                <input type="date" id="dataInicio" title="Data de Início">
                <input type="date" id="dataFim" title="Data de Fim">
                <button id="btnFiltrar" class="btn-filtrar">Filtrar Datas</button>
                <button id="btnLimpar" class="btn-limpar">Limpar Filtros</button>
            </div>
            <div id="containerFiltroCidades" class="filtro-cidades"></div>
        </div>

        <div id="mainLoadingState" class="main-loading-state">
            <div class="main-loading-spinner"></div>
            <span>Carregando dados...</span>
        </div>

        <div id="containerListaLacres" class="lista-equipamentos-lacres"></div>
    </main>

    <div id="modalAdicionarLacres" class="modal">
        <div class="conteudo-modal">
            <div class="modal-header">
                <h2 id="tituloModalAdicionarLacres">Adicionar Lacres</h2>
                <button class="fechar-modal" onclick="fecharModal('modalAdicionarLacres')">&times;</button>
            </div>
            <form id="formularioAdicionarLacres" class="formulario-modal" onsubmit="prepararEnvio(event)">
                <input type="hidden" name="id_equipamento">

                <label>Metrológico:</label><input type="text" name="metrologico">
                <label>Não Metrológico:</label><input type="text" name="nao_metrologico">
                <label>Fonte:</label><input type="text" name="fonte">
                <label>Switch:</label><input type="text" name="switch_lacre">

                <label>Câmera(s) Zoom:</label>
                <div class="botoes-toggle zoom-toggle">
                    <button type="button" class="ativo" onclick="toggleCameras(1, this, 'zoom')">1</button>
                    <button type="button" onclick="toggleCameras(2, this, 'zoom')">2</button>
                </div>
                <div id="zoom_camera_unica"><label>Câmera Zoom (fx. A/B):</label><input type="text" name="camera_zoom_ab"></div>
                <div id="zoom_camera_dupla" class="hidden"><label>Câmera Zoom (fx. A):</label><input type="text" name="camera_zoom_a"><label>Câmera Zoom (fx. B):</label><input type="text" name="camera_zoom_b"></div>

                <label>Câmera(s) PAM:</label>
                <div class="botoes-toggle pam-toggle">
                    <button type="button" class="ativo" onclick="toggleCameras(1, this, 'pam')">1</button>
                    <button type="button" onclick="toggleCameras(2, this, 'pam')">2</button>
                </div>
                <div id="pam_camera_unica"><label>Câmera PAM (fx. A/B):</label><input type="text" name="camera_pam_ab"></div>
                <div id="pam_camera_dupla" class="hidden"><label>Câmera PAM (fx. A):</label><input type="text" name="camera_pam_a"><label>Câmera PAM (fx. B):</label><input type="text" name="camera_pam_b"></div>

                <button type="submit" class="botao-salvar">Avançar</button>
            </form>
        </div>
    </div>
    <div id="modalConfirmacao" class="modal">
        <div class="conteudo-modal" style="max-width: 400px;">
            <div class="modal-header">
                <h2 id="tituloConfirmacao">Confirmar Lacres</h2><button class="fechar-modal" onclick="fecharModal('modalConfirmacao')">&times;</button>
            </div>
            <div id="resumoLacres"></div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button class="botao-salvar" style="background-color: #6c757d;" onclick="fecharModal('modalConfirmacao')">Cancelar</button>
                <button id="btnConfirmarSalvar" class="botao-salvar">Confirmar e Salvar</button>
            </div>
            <div id="mensagemSalvar" class="mensagem" style="display: none;"></div>
        </div>
    </div>

    <script src="js/lacresImetro.js"></script>
</body>

</html>