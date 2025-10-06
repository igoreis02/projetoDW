<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id']) || $_SESSION['tipo_usuario'] !== 'tecnico') {
    header('Location: index.html'); // Redireciona se não estiver logado ou não for técnico
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style_tecnico.css">
    <link rel="icon" type="image/png" href="imagens/favicon.png">
    <title>Ocorrência - Técnico</title>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Minhas Manutenções</h2>

        <div class="filtro-manutencoes">
            <button id="btnCorretiva" class="botao-filtro">Corretiva</button>
            <button id="btnInstalacao" class="botao-filtro">Instalação</button>
        </div>

        <div id="listaManutencoes" class="lista-manutencoes">
            <p id="mensagemCarregamento">Carregando suas manutenções...</p>
            <p id="mensagemErro" class="mensagem erro oculto"></p>
        </div>

        <a href="logout.php" class="voltar-btn">Sair</a>
    </div>

    <div class="footer">
        <p>© 2025 APsystem. Todos os direitos reservados.</p>
    </div>

    <div id="concluirReparoModal" class="modal">
        <div class="conteudo-modal">
            <span class="botao-fechar" onclick="fecharModalConcluirReparo()">×</span>
            <h3 id="modalConcluirTitulo">Concluir Reparo</h3>
            <div class="modal-detalhe-item">
                <p class="modal-titulo-equipamento">
                    <span id="nomeEquipamentoModal"></span> - <span id="referenciaEquipamentoModal"></span>
                </p>
            </div>

            <div id="camposReparo" class="oculto">
                <div class="modal-ocorrencia-texto" style="margin-bottom: 15px;">
                    <strong class="rotulo-detalhe">Ocorrência:</strong>
                    <span id="ocorrenciaReparoModal"></span>
                </div>
                <label for="materiaisUtilizadosInput">Materiais Utilizados:</label>
                <div class="input-com-checkbox">
                    <input type="text" id="materiaisUtilizadosInput" placeholder="Ex: switch, 485, ...">
                    <div class="checkbox-container">
                        <input type="checkbox" id="checkboxNenhumMaterial">
                        <label for="checkboxNenhumMaterial" class="checkbox-label">Nenhum</label>
                    </div>
                </div>
                <div class="rompimento-lacre-container">
                    <p class="rotulo-pergunta">Houve rompimento de lacre?</p>
                    <div class="botoes-toggle">
                        <button type="button" id="botaoSimRompimento" class="botao-toggle">Sim</button>
                        <button type="button" id="botaoNaoRompimento" class="botao-toggle ativo">Não</button>
                    </div>
                </div>
                <div id="camposRompimentoLacre" class="oculto">
                    <label for="selectLacreRompido">Qual lacre foi rompido?</label>
                    <select id="selectLacreRompido">
                        <option value="">Selecione um lacre...</option>
                    </select>

                    <label for="inputNumeroLacre">Número do lacre:</label>
                    <input type="text" id="inputNumeroLacre" placeholder="Selecione um lacre acima" readonly>

                    <label for="inputDataRompimento">Data do rompimento:</label>
                    <input type="date" id="inputDataRompimento">
                </div>
                <label for="reparoRealizadoTextarea">Reparo Realizado:</label>
                <textarea id="reparoRealizadoTextarea" rows="5" placeholder="Descreva o que foi feito no reparo..."></textarea>
            </div>

            <div id="camposInstalacao" class="oculto">
                <p class="rotulo-pergunta">Informe a data de conclusão de cada etapa:</p>
                <div class="instalacao-checklist">
                    <div class="item-checklist">
                        <label for="dataBase">Data Instalação<br><b>Base:</b></label>
                        <input type="date" id="dataBase">
                    </div>
                    <div class="item-checklist">
                        <label for="dataLaco">Data Instalação<br><b>Laço:</b></label>
                        <input type="date" id="dataLaco">
                    </div>
                    <div class="item-checklist">
                        <label for="dataInfra">Data Instalação<br><b>Infraestrutura:</b></label>
                        <input type="date" id="dataInfra">
                    </div>
                    <div class="item-checklist">
                        <label for="dataEnergia">Data Instalação<br><b>Energia:</b></label>
                        <input type="date" id="dataEnergia">
                    </div>
                </div>
            </div>

            <div class="container-botao-concluir">

                <button id="btnSalvarProgresso" class="botao-salvar-progresso oculto">
                    Salvar Progresso
                    <span id="salvarProgressoSpinner" class="spinner-carregamento oculto"></span>
                </button>

                <button id="confirmConcluirReparoBtn" class="botao-concluir">
                    Confirmar Instalação
                    <span id="concluirReparoSpinner" class="spinner-carregamento oculto"></span>
                </button>
                <p id="concluirReparoMessage" class="mensagem oculto"></p>
            </div>
        </div>

        <div id="partialConfirmModal" class="modal">
            <div class="conteudo-modal-pequeno">
                <h3>Confirmar Instalação Parcial</h3>
                <p>Você está concluindo a instalação de:</p>
                <ul id="listaItensConcluidos"></ul>
                <p>As outras etapas permanecerão pendentes e esta OS voltará para a base. Deseja continuar?</p>
                <div class="botoes-confirmacao-parcial">
                    <button id="btnConfirmarParcial" class="botao-confirmar">Sim, Confirmar</button>
                    <button id="btnCancelarParcial" class="botao-cancelar">Cancelar</button>
                </div>
            </div>
        </div>

        <div id="devolucaoModal" class="modal">
            <div class="conteudo-modal">
                <span class="botao-fechar" onclick="fecharModalDevolucao()">×</span>
                <h3>Devolução de Manutenção</h3>
                <p class="modal-titulo-equipamento">
                    <span id="nomeEquipamentoDevolucaoModal"></span> - <span id="referenciaEquipamentoDevolucaoModal"></span>
                </p>
                <p class="modal-ocorrencia">Ocorrência: <span id="ocorrenciaReparoDevolucaoModal"></span></p>
                <div class="form-group">
                    <label for="textareaDevolucao">Motivo da Devolução:</label>
                    <textarea id="textareaDevolucao" rows="4" placeholder="Descreva o motivo da devolução"></textarea>
                </div>
                <div id="mensagemDevolucao" class="mensagem oculto"></div>
                <button id="botaoConfirmarDevolucao" class="botao-confirmar">Confirmar Devolução</button>
                <div id="spinnerDevolucao" class="spinner oculto"></div>
            </div>
        </div>

        <div id="fullConfirmModal" class="modal">
            <div class="conteudo-modal-pequeno">
                <h3>Confirmar Conclusão Total</h3>
                <p>Todas as etapas necessárias foram preenchidas. Deseja concluir totalmente esta instalação?</p>
                <div class="botoes-confirmacao-parcial">
                    <button id="btnConfirmarTotal" class="botao-confirmar">Sim, Concluir</button>
                    <button id="btnCancelarTotal" class="botao-cancelar">Cancelar</button>
                </div>
            </div>
        </div>

        <script>
            const userId = <?php echo json_encode($user_id); ?>;
        </script>
        <script src="js/manutencao_tecnico.js" defer></script>
</body>

</html>