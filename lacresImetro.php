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
            display: flex; align-items: center; justify-content: center;
            padding: 0.5rem; color: #aaa; text-decoration: none; font-size: 2em;
        }
        .container-botoes-topo {
            width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;
        }
        .botao-adicionar-lacres {
            background-color: var(--cor-principal); color: white; padding: 12px 25px;
            font-size: 1.1em; border: none; border-radius: 5px; cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .botao-adicionar-lacres:hover { background-color: var(--cor-secundaria); }
        .container-pesquisa { display: flex; justify-content: center; margin-bottom: 1.5rem; }
        .container-pesquisa input { padding: 10px; border-radius: 5px; border: 1px solid #ccc; width: 100%; max-width: 400px; }
        .lista-equipamentos-lacres { display: flex; flex-direction: column; gap: 1.5rem; }
        .item-equipamento-lacre { background-color: #f9f9f9; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); text-align: left; }
        .equipamento-info { border-bottom: 1px solid #e0e0e0; padding-bottom: 1rem; margin-bottom: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .info-bloco h4 { margin-top: 0; margin-bottom: 0.5rem; color: var(--cor-secundaria); }
        .info-bloco p { margin: 0.25rem 0; color: #555; }
        .lacres-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem 1rem; }
        .lacre-item { background-color: #eef2ff; padding: 0.5rem; border-radius: 5px; }
        .lacre-item strong { color: #4338ca; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.4); justify-content: center; align-items: center; }
        .modal.esta-ativo { display: flex; }
        .conteudo-modal { background-color: #fff; padding: 2rem; border-radius: 1rem; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); width: 90%; max-width: 500px; position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; }
        .fechar-modal { font-size: 2rem; font-weight: bold; cursor: pointer; background: none; border: none; }
        .lista-selecao { display: flex; flex-direction: column; gap: 0.5rem; max-height: 300px; overflow-y: auto; }
        .lista-selecao button { background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 0.75rem; border-radius: 5px; cursor: pointer; text-align: left; }
        .lista-selecao button:hover { background-color: #e5e7eb; }
        .formulario-modal label { display: block; margin-top: 1rem; font-weight: bold; color: #333; }
        .formulario-modal input { width: 100%; padding: 0.75rem; margin-top: 0.5rem; border-radius: 0.5rem; border: 1px solid #ccc; box-sizing: border-box; }
        .formulario-modal button { width: 100%; padding: 1rem; margin-top: 1.5rem; border-radius: 0.5rem; border: none; color: white; font-size: 1.1rem; cursor: pointer; }
        .botao-salvar { background-color: #4CAF50; }
        .mensagem { margin-top: 1rem; padding: 1rem; border-radius: 0.5rem; text-align: center; }
        .mensagem.sucesso { background-color: #d4edda; color: #155724; }
        .mensagem.erro { background-color: #f8d7da; color: #721c24; }
        .botoes-toggle { display: flex; gap: 10px; margin-top: 0.5rem; }
        .botoes-toggle button { flex: 1; padding: 8px; border: 1px solid #ccc; background-color: #f0f0f0; border-radius: 5px; cursor: pointer; }
        .botoes-toggle button.ativo { background-color: #112058; color: white; border-color: #112058; }
        .hidden { display: none; }
    </style>
</head>

<body>
    <div class="background"></div>
    <main class="card">
        <header class="cabecalho">
            <a href="menu.php" class="botao-voltar" title="Voltar ao Menu">&larr;</a>
            <h1 class="titulo-cabecalho">Gerenciar Lacres INMETRO</h1>
        </header>
        <div class="container-botoes-topo">
            <button class="botao-adicionar-lacres" onclick="abrirModalCidades()">Adicionar Lacres</button>
            <div class="container-pesquisa">
                <input type="text" id="campoPesquisa" placeholder="Pesquisar por nome ou referência...">
            </div>
        </div>
        <div id="containerListaLacres" class="lista-equipamentos-lacres">
            Carregando equipamentos...
        </div>
    </main>

    <div id="modalCidades" class="modal">
        <div class="conteudo-modal">
            <div class="modal-header">
                <h2>Selecione a Cidade</h2>
                <button class="fechar-modal" onclick="fecharModal('modalCidades')">&times;</button>
            </div>
            <div id="listaCidades" class="lista-selecao"></div>
        </div>
    </div>

    <div id="modalEquipamentos" class="modal">
        <div class="conteudo-modal">
            <div class="modal-header">
                <h2>Selecione o Equipamento</h2>
                <button class="fechar-modal" onclick="fecharModal('modalEquipamentos')">&times;</button>
            </div>
            <input type="text" id="pesquisaEquipamento" placeholder="Filtrar equipamento..." style="width: 100%; padding: 8px; margin-bottom: 1rem;">
            <div id="listaEquipamentos" class="lista-selecao"></div>
        </div>
    </div>

    <div id="modalAdicionarLacres" class="modal">
        <div class="conteudo-modal">
            <div class="modal-header">
                <h2 id="tituloModalLacres">Adicionar Lacres</h2>
                <button class="fechar-modal" onclick="fecharModal('modalAdicionarLacres')">&times;</button>
            </div>
            <form id="formularioAdicionarLacres" class="formulario-modal">
                <input type="hidden" id="idEquipamentoLacre" name="id_equipamento">
                
                <label for="lacreMetrologico">Metrológico:</label>
                <input type="text" id="lacreMetrologico" name="metrologico">

                <label for="lacreNaoMetrologico">Não Metrológico:</label>
                <input type="text" id="lacreNaoMetrologico" name="nao metrologico">

                <label for="lacreFonte">Fonte:</label>
                <input type="text" id="lacreFonte" name="fonte">
                
                <label for="lacreSwitch">Switch:</label>
                <input type="text" id="lacreSwitch" name="switch">

                <label>Câmera(s) Zoom:</label>
                <div class="botoes-toggle">
                    <button type="button" class="ativo" data-cameras="1" onclick="toggleCameras(1, this)">1</button>
                    <button type="button" data-cameras="2" onclick="toggleCameras(2, this)">2</button>
                </div>
                
                <div id="cameraFxUnica">
                    <label for="lacreCameraFxAB">Câmera Zoom (fx. A/B):</label>
                    <input type="text" id="lacreCameraFxAB" name="camera zoom (fx. A/B)">
                </div>
                <div id="cameraFxDupla" class="hidden">
                    <label for="lacreCameraFxA">Câmera Zoom (fx. A):</label>
                    <input type="text" id="lacreCameraFxA" name="camera zoom (fx. A)">
                    <label for="lacreCameraFxB">Câmera Zoom (fx. B):</label>
                    <input type="text" id="lacreCameraFxB" name="camera zoom (fx. B)">
                </div>

                <label for="lacreCameraPam">Câmera PAM:</label>
                <input type="text" id="lacreCameraPam" name="camera pam">

                <button type="submit" class="botao-salvar">Salvar Lacres</button>
            </form>
        </div>
    </div>
    
    <div id="modalConfirmacao" class="modal">
        <div class="conteudo-modal" style="max-width: 400px;">
             <div class="modal-header">
                <h2>Confirmar Lacres</h2>
                <button class="fechar-modal" onclick="fecharModal('modalConfirmacao')">&times;</button>
            </div>
            <div id="resumoLacres"></div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button class="botao-salvar" style="background-color: #6c757d;" onclick="fecharModal('modalConfirmacao')">Cancelar</button>
                <button class="botao-salvar" onclick="enviarLacres()">Confirmar e Salvar</button>
            </div>
             <div id="mensagemSalvar" class="mensagem" style="display: none;"></div>
        </div>
    </div>

    <script src="js/lacresImetro.js"></script>
</body>
</html>