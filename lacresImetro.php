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
    <link rel="stylesheet" href="css/inmetro.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Lacres INMETRO</title>
</head>

<body>
    <div class="background"></div>
    <div id="sidebar-alertas" class="sidebar-alertas">
        <button id="toggle-sidebar-btn" class="toggle-sidebar-btn" title="Ocultar/Mostrar Alertas">&raquo;</button>
        <div class="sidebar-header">
            <h3>Alertas</h3>
        </div>
        <div id="lista-alertas" class="lista-alertas">
        </div>
    </div>
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

        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>

        <button id="btnVoltarAoTopo" title="Voltar ao topo">
            <i class="fas fa-arrow-up"></i>
        </button>
    </main>

    <div id="modalAdicionarLacres" class="modal">
        <div class="conteudo-modal">
            <div class="modal-header">
                <h2 id="tituloModalAdicionarLacres">Adicionar Lacres</h2>
                <button class="fechar-modal" onclick="fecharModal('modalAdicionarLacres')">&times;</button>
            </div>


            <form id="formularioAdicionarLacres" class="formulario-modal" onsubmit="prepararEnvio(event)">
                <input type="hidden" name="id_equipamento">

                <div class="form-lacre-group">
                    <label>Metrológico:</label>
                    <input type="text" name="metrologico">

                    <div class="rompido-toggle-container">
                        <label class="rompido-label">Lacre rompido?</label>
                        <div class="botoes-toggle">
                            <button type="button" onclick="toggleRompido(this, false)">Não</button>
                            <button type="button" onclick="toggleRompido(this, true)">Sim</button>
                        </div>
                    </div>
                    <textarea name="obs_metrologico" class="obs-lacre hidden" placeholder="Observação..."></textarea>

                    <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                    <input type="date" name="dt_fixacao_metrologico"
                        style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">



                    <div class="data-rompimento-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                        <input type="date" name="dt_rompimento_metrologico"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; ">
                    </div>
                    <div class="data-psie-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                        <input type="date" name="dt_reporta_psie_metrologico"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>

                <div class="form-lacre-group">
                    <label>Não Metrológico:</label>
                    <input type="text" name="nao_metrologico">

                    <div class="rompido-toggle-container">
                        <label class="rompido-label">Lacre rompido?</label>
                        <div class="botoes-toggle">
                            <button type="button" onclick="toggleRompido(this, false)">Não</button>
                            <button type="button" onclick="toggleRompido(this, true)">Sim</button>
                        </div>
                    </div>
                    <textarea name="obs_nao_metrologico" class="obs-lacre hidden"
                        placeholder="Observação..."></textarea>

                    <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                    <input type="date" name="dt_fixacao_nao_metrologico"
                        style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                    <div class="data-rompimento-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                        <input type="date" name="dt_rompimento_nao_metrologico"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div class="data-psie-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                        <input type="date" name="dt_reporta_psie_nao_metrologico"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>

                <div class="form-lacre-group">
                    <label>Fonte:</label>
                    <input type="text" name="fonte">

                    <div class="rompido-toggle-container">
                        <label class="rompido-label">Lacre rompido?</label>
                        <div class="botoes-toggle">
                            <button type="button" onclick="toggleRompido(this, false)">Não</button>
                            <button type="button" onclick="toggleRompido(this, true)">Sim</button>
                        </div>
                    </div>
                    <textarea name="obs_fonte" class="obs-lacre hidden" placeholder="Observação..."></textarea>


                    <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                    <input type="date" name="dt_fixacao_fonte"
                        style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                    <div class="data-rompimento-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                        <input type="date" name="dt_rompimento_fonte"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; ">
                    </div>
                    <div class="data-psie-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                        <input type="date" name="dt_reporta_psie_fonte"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>

                <div class="form-lacre-group">
                    <label>Switch:</label>
                    <input type="text" name="switch_lacre">

                    <div class="rompido-toggle-container">
                        <label class="rompido-label">Lacre rompido?</label>
                        <div class="botoes-toggle">
                            <button type="button" onclick="toggleRompido(this, false)">Não</button>
                            <button type="button" onclick="toggleRompido(this, true)">Sim</button>
                        </div>
                    </div>
                    <textarea name="obs_switch_lacre" class="obs-lacre hidden" placeholder="Observação..."></textarea>

                    <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                    <input type="date" name="dt_fixacao_switch_lacre"
                        style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                    <div class="data-rompimento-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                        <input type="date" name="dt_rompimento_switch_lacre"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div class="data-psie-container hidden">
                        <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                        <input type="date" name="dt_reporta_psie_switch_lacre"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                </div>

                <div class="form-lacre-group">
                    <label>Câmera(s) Zoom:</label>
                    <div class="botoes-toggle zoom-toggle">
                        <button type="button" class="ativo" onclick="toggleCameras(1, this, 'zoom')">1 cam</button>
                        <button type="button" onclick="toggleCameras(2, this, 'zoom')">2 cam</button>
                    </div>

                    <div id="zoom_camera_unica">
                        <label>Câmera Zoom (fx. A/B):</label><input type="text" name="camera_zoom_ab">

                        <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                            <div class="botoes-toggle"><button type="button"
                                    onclick="toggleRompido(this, false)">Não</button><button type="button"
                                    onclick="toggleRompido(this, true)">Sim</button></div>
                        </div><textarea name="obs_camera_zoom_ab" class="obs-lacre hidden"
                            placeholder="Observação..."></textarea>

                        <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                        <input type="date" name="dt_fixacao_camera_zoom_ab"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                        <div class="data-rompimento-container hidden">
                            <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                            <input type="date" name="dt_rompimento_camera_zoom_ab"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                        <div class="data-psie-container hidden">
                            <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                            <input type="date" name="dt_reporta_psie_camera_zoom_ab"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                    </div>

                    <div id="zoom_camera_dupla" class="hidden">

                        <div class="camera-sub-group">
                            <label>Câmera Zoom (fx. A):</label><input type="text" name="camera_zoom_a">

                            <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                                <div class="botoes-toggle"><button type="button"
                                        onclick="toggleRompido(this, false)">Não</button><button type="button"
                                        onclick="toggleRompido(this, true)">Sim</button></div>
                            </div><textarea name="obs_camera_zoom_a" class="obs-lacre hidden"
                                placeholder="Observação..."></textarea>

                            <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                            <input type="date" name="dt_fixacao_camera_zoom_a"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                            <div class="data-rompimento-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                                <input type="date" name="dt_rompimento_camera_zoom_a"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                            <div class="data-psie-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                                <input type="date" name="dt_reporta_psie_camera_zoom_a"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                        </div>

                        <div class="camera-sub-group">
                            <label>Câmera Zoom (fx. B):</label><input type="text" name="camera_zoom_b">

                            <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                                <div class="botoes-toggle"><button type="button"
                                        onclick="toggleRompido(this, false)">Não</button><button type="button"
                                        onclick="toggleRompido(this, true)">Sim</button></div>
                            </div><textarea name="obs_camera_zoom_b" class="obs-lacre hidden"
                                placeholder="Observação..."></textarea>

                            <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                            <input type="date" name="dt_fixacao_camera_zoom_b"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                            <div class="data-rompimento-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                                <input type="date" name="dt_rompimento_camera_zoom_b"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; ">
                            </div>
                            <div class="data-psie-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                                <input type="date" name="dt_reporta_psie_camera_zoom_b"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc; ">
                            </div>
                        </div>

                    </div>
                </div>

                <div class="form-lacre-group">
                    <label>Câmera(s) PAM:</label>
                    <div class="botoes-toggle pam-toggle">
                        <button type="button" class="ativo" onclick="toggleCameras(1, this, 'pam')">1 cam</button>
                        <button type="button" onclick="toggleCameras(2, this, 'pam')">2 cam</button>
                    </div>

                    <div id="pam_camera_unica">
                        <label>Câmera PAM (fx. A/B):</label><input type="text" name="camera_pam_ab">

                        <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                            <div class="botoes-toggle"><button type="button"
                                    onclick="toggleRompido(this, false)">Não</button><button type="button"
                                    onclick="toggleRompido(this, true)">Sim</button></div>
                        </div><textarea name="obs_camera_pam_ab" class="obs-lacre hidden"
                            placeholder="Observação..."></textarea>

                        <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                        <input type="date" name="dt_fixacao_camera_pam_ab"
                            style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                        <div class="data-rompimento-container hidden">
                            <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                            <input type="date" name="dt_rompimento_camera_pam_ab"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                        <div class="data-psie-container hidden">
                            <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                            <input type="date" name="dt_reporta_psie_camera_pam_ab"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                    </div>

                    <div id="pam_camera_dupla" class="hidden">

                        <div class="camera-sub-group">
                            <label>Câmera PAM (fx. A):</label><input type="text" name="camera_pam_a">

                            <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                                <div class="botoes-toggle"><button type="button"
                                        onclick="toggleRompido(this, false)">Não</button><button type="button"
                                        onclick="toggleRompido(this, true)">Sim</button></div>
                            </div><textarea name="obs_camera_pam_a" class="obs-lacre hidden"
                                placeholder="Observação..."></textarea>

                            <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                            <input type="date" name="dt_fixacao_camera_pam_a"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                            <div class="data-rompimento-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                                <input type="date" name="dt_rompimento_camera_pam_a"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                            <div class="data-psie-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                                <input type="date" name="dt_reporta_psie_camera_pam_a"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                        </div>

                        <div class="camera-sub-group">
                            <label>Câmera PAM (fx. B):</label><input type="text" name="camera_pam_b">

                            <div class="rompido-toggle-container"><label class="rompido-label">Lacre rompido?</label>
                                <div class="botoes-toggle"><button type="button"
                                        onclick="toggleRompido(this, false)">Não</button><button type="button"
                                        onclick="toggleRompido(this, true)">Sim</button></div>
                            </div><textarea name="obs_camera_pam_b" class="obs-lacre hidden"
                                placeholder="Observação..."></textarea>

                            <label style="margin-top: 8px; font-weight: normal;">Data de Fixação:</label>
                            <input type="date" name="dt_fixacao_camera_pam_b"
                                style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">

                            <div class="data-rompimento-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Data do Rompimento:</label>
                                <input type="date" name="dt_rompimento_camera_pam_b"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc">
                            </div>
                            <div class="data-psie-container hidden">
                                <label style="margin-top: 8px; font-weight: normal;">Reporta PSIE:</label>
                                <input type="date" name="dt_reporta_psie_camera_pam_b"
                                    style="padding: 0.5rem; border-radius: 5px; border: 1px solid #ccc;">
                            </div>
                        </div>

                    </div>
                </div>
                <div id="mensagemAdicionarVazio" class="mensagem erro" style="display: none;"></div>
                <button type="submit" class="botao-salvar">Avançar</button>
            </form>
        </div>
    </div>
    <div id="modalConfirmacao" class="modal">
        <div class="conteudo-modal" style="max-width: 400px;">
            <div class="modal-header">
                <h2 id="tituloConfirmacao">Confirmar Lacres</h2><button class="fechar-modal"
                    onclick="fecharModal('modalConfirmacao')">&times;</button>
            </div>
            <div id="resumoLacres"></div>
            <div id="confirmacaoBotoesContainer" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button class="botao-cancelar" onclick="fecharModal('modalConfirmacao')">Cancelar</button>
                <button id="btnConfirmarSalvar" class="botao-salvar">
                    <span>Confirmar e Salvar</span>
                    <div class="spinner hidden"></div>
                </button>
            </div>
            <div id="mensagemSalvar" class="mensagem" style="display: none;"></div>
        </div>
    </div>
    <div id="modalLacreRompido" class="modal">
        <div class="conteudo-modal" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="tituloModalLacreRompido">Lacre Rompido</h2>
                <button class="fechar-modal" onclick="fecharModal('modalLacreRompido')">&times;</button>
            </div>
            <form id="formLacreRompido" onsubmit="prepararConfirmacaoRompimento(event)">
                <input type="hidden" name="id_equipamento">
                <p><strong>Qual lacre foi rompido?</strong> (Selecione um ou mais)</p>

                <div id="listaLacresAfixados"
                    style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                </div>

                <div id="detalhesLacresSelecionados" class="hidden" style="margin-top: 1.5rem;">
                </div>

                <div id="mensagemErroRompimento" class="mensagem erro" style="display: none;"></div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="botao-cancelar"
                        onclick="fecharModal('modalLacreRompido')">Cancelar</button>
                    <button type="submit" class="botao-salvar">Avançar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalConfirmarRompimento" class="modal">
        <div class="conteudo-modal" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Confirmar Rompimento</h2>
                <button class="fechar-modal" onclick="fecharModal('modalConfirmarRompimento')">&times;</button>
            </div>
            <p>Por favor, confirme os detalhes do rompimento:</p>
            <div id="resumoLacresRompimento">
            </div>
            <div id="botoesConfirmarRompimento" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="botao-cancelar"
                    onclick="fecharModal('modalConfirmarRompimento')">Cancelar</button>
                <button id="btnSalvarRompimento" type="button" class="botao-salvar" onclick="executarRompimento()">
                    <span>Confirmar</span>
                    <div class="spinner hidden"></div>
                </button>
            </div>
            <div id="mensagemSalvarRompimento" class="mensagem" style="display: none;"></div>
        </div>
    </div>

    <div id="modalDistribuirRompido" class="modal">
        <div class="conteudo-modal" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="tituloModalDistribuirRompido">Distribuir para Lacres Rompidos</h2>
                <button class="fechar-modal" onclick="fecharModal('modalDistribuirRompido')">&times;</button>
            </div>
            <form id="formDistribuirRompido" onsubmit="prepararConfirmacaoDistribuicao(event)">
                <input type="hidden" name="id_equipamento">
                <p><strong>Selecione os lacres rompidos que serão substituídos:</strong></p>
                <div id="listaLacresRompidoDistribuir"
                    style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                </div>
                <div id="detalhesLacresDistribuir" class="hidden" style="margin-top: 1.5rem;">
                </div>
                <div id="mensagemErroDistribuir" class="mensagem erro" style="display: none;"></div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="botao-cancelar"
                        onclick="fecharModal('modalDistribuirRompido')">Cancelar</button>
                    <button type="submit" class="botao-salvar">Avançar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditarLacreIndividual" class="modal">
        <div class="conteudo-modal" style="max-width: 500px;">
            <div class="modal-header">
                <h2 id="tituloModalEdicaoIndividual">
                    Editar Lacre <span id="localLacreIndividualTitulo"></span>
                </h2>
                <button class="fechar-modal" onclick="fecharModal('modalEditarLacreIndividual')">&times;</button>
            </div>
            <form id="formEditarLacreIndividual" class="formulario-modal" onsubmit="salvarEdicaoIndividual(event)">
                <input type="hidden" name="id_controle_lacres">
                <div class="form-lacre-group" style="padding: 1rem 1rem 1.5rem 1rem;">
                    <div id="campoNumLacreIndividual">
                        <label for="numLacreIndividualInput">Número do Lacre:</label>
                        <input type="text" id="numLacreIndividualInput" name="numero_lacre" required>
                    </div>

                    <div id="campoObsIndividual" style="margin-top: 1rem;">
                        <label for="obsLacreIndividualInput">Observação:</label>
                        <textarea id="obsLacreIndividualInput" name="obs_lacre" class="obs-lacre"
                            style="display: block;"></textarea>
                    </div>

                    <div id="campoDataFixacaoIndividual" class="hidden" style="margin-top: 1rem;">
                        <label for="dataFixacaoIndividualInput">Data de Fixação:</label>
                        <input type="date" id="dataFixacaoIndividualInput" name="dt_fixacao">
                    </div>

                    <div id="campoDataRompimentoIndividual" class="hidden" style="margin-top: 1rem;">
                        <label for="dataRompimentoIndividualInput">Data do Rompimento:</label>
                        <input type="date" id="dataRompimentoIndividualInput" name="dt_rompimento">
                    </div>

                    <div id="campoDataPsieIndividual" class="hidden" style="margin-top: 1rem;">
                        <label for="dataPsieIndividualInput">Reporta PSIE:</label>
                        <input type="date" id="dataPsieIndividualInput" name="dt_reporta_psie">
                    </div>
                </div>

                <div id="mensagemSalvarIndividual" class="mensagem" style="display: none;"></div>
                <div id="botoesEdicaoIndividual" class="modal-actions">
                    <button type="button" class="botao-cancelar botao-cancelar-editar"
                        onclick="fecharModal('modalEditarLacreIndividual')">Cancelar</button>
                    <button type="submit" class="botao-salvar">
                        <span>Salvar Alterações</span>
                        <div class="spinner hidden"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalConfirmarEdicao" class="modal">
        <div class="conteudo-modal" style="max-width: 450px;">
            <div class="modal-header">
                <h2>Confirmar Edição</h2>
                <button class="fechar-modal" onclick="fecharModal('modalConfirmarEdicao')">&times;</button>
            </div>
            <div class="modal-body-text">
                <p>Todos os lacres para este equipamento já estão preenchidos ou com pendências.</p>
                <p><strong>Deseja continuar e editar os lacres fixados?</strong></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="botao-cancelar" onclick="fecharModal('modalConfirmarEdicao')">
                    Cancelar
                </button>
                <button id="btnExecutarEdicao" type="button" class="botao-salvar">
                    <span>Confirmar e Editar</span>
                </button>
            </div>
        </div>
    </div>

    <script src="js/lacresImetro.js"></script>
</body>

</html>