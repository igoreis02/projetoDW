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
    <title>Minhas Manutenções - Técnico</title>
   
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
        <span class="botao-fechar" onclick="closeConcluirReparoModal()">×</span>
        <h3>Concluir Reparo</h3>
        <div class="modal-detalhe-item">
            <p class="modal-titulo-equipamento">
                <span id="nomeEquipamentoModal"></span> - <span id="referenciaEquipamentoModal"></span>
            </p>
            <p class="modal-ocorrencia-texto">
                <strong class="rotulo-detalhe">Ocorrência:</strong>
                <span id="ocorrenciaReparoModal"></span>
            </p>
        </div>

        <label for="materiaisUtilizadosInput">Materiais Utilizados:</label>
        <div class="input-com-checkbox">
            <input type="text" id="materiaisUtilizadosInput" placeholder="Ex: switch, 485, ...">
            <div class="checkbox-container">
                <input type="checkbox" id="checkboxNenhumMaterial">
                <label for="checkboxNenhumMaterial" class="checkbox-label">Nenhum material</label>
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
            <label for="inputNumeroLacre">Número do lacre:</label>
            <input type="text" id="inputNumeroLacre" placeholder="Ex: 123456">
        
            <label for="inputInfoRompimento">Qual lacre foi rompido? </label>
            <input type="text" id="inputInfoRompimento" placeholder="Ex: Metrológico, Não-metrológico">
        
            <label for="inputDataRompimento">Data do rompimento:</label>
            <input type="date" id="inputDataRompimento">
        </div>

        <label for="reparoRealizadoTextarea">Reparo Realizado:</label>
        <textarea id="reparoRealizadoTextarea" rows="5" placeholder="Descreva o que foi feito no reparo..."></textarea>

        <div class="container-botao-concluir">
        <button id="confirmConcluirReparoBtn" class="botao-concluir">
            Confirmar Reparo
            <span id="concluirReparoSpinner" class="spinner-carregamento oculto"></span>
        </button>
        <p id="concluirReparoMessage" class="mensagem oculto"></p>
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

    <script>
        const userId = <?php echo json_encode($user_id); ?>;
    </script>
    <script src="js/manutencao_tecnico.js"></script>

</body>
</html>