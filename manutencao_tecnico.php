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

        <div id="listaManutencoes" class="lista-manutencoes">
            <p id="mensagemCarregamento">Carregando suas manutenções...</p>
            <p id="mensagemErro" class="mensagem erro oculto"></p>
        </div>

        <a href="logout.php" class="voltar-btn">Sair</a>
    </div>
    <div class="footer">
        <p>© 2025 APsystem. Todos os direitos reservados.</p>
    </div>

<div id="modalConcluirReparo" class="modal">
    <div class="conteudo-modal">
        <span class="botao-fechar" onclick="fecharModalConcluirReparo()">×</span>
        <h3>Conclusão do Reparo</h3>
    <div class="modal-detalhe-item">
        <p class="modal-titulo-equipamento">
            <span id="nomeEquipamentoModal"></span> - <span id="referenciaEquipamentoModal"></span>
        </p>
        
        <p class="modal-ocorrencia-texto">
            <strong class="rotulo-detalhe">Ocorrência:</strong> 
            <span id="ocorrenciaReparoModal"></span>
        </p>
    </div>
        <label for="inputMateriaisUtilizados">Materiais utilizados:</label>
        <input type="text" id="inputMateriaisUtilizados" placeholder="Ex: Peça X, Óleo Y...">
        
        <div class="sem-materiais-container">
            <input type="checkbox" id="checkboxNenhumMaterial">
            <label for="checkboxNenhumMaterial" class="checkbox-label">Nenhum material utilizado</label>
        </div>

        <label for="textareaReparoRealizado">Descrição do reparo:</label>
        <textarea id="textareaReparoRealizado" rows="5" placeholder="Descreva o trabalho realizado..." required></textarea>

        <button id="botaoConfirmarConcluirReparo">
            Confirmar Reparo
            <span id="spinnerConcluirReparo" class="spinner-carregamento oculto"></span>
        </button>
        <p id="mensagemConcluirReparo" class="mensagem oculto"></p>
    </div>
</div>
    <script>
        const userId = <?php echo json_encode($user_id); ?>;
    </script>
    <script src="js/manutencao_tecnico.js"></script>
</body>

</html>
</html>
