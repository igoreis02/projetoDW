<?php
session_start(); // Inicia ou resume a sessão
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para index.html se user_id não estiver na sessão
    exit();
}
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Opcional: Redirecionar se o tipo de usuário não tiver permissão para esta página
// Por exemplo, apenas administradores e provedores podem atribuir manutenções
// if (isset($_SESSION['tipo_usuario']) && ($_SESSION['tipo_usuario'] !== 'administrador' && $_SESSION['tipo_usuario'] !== 'provedor')) {
//     header('Location: menu.php');
//     exit();
// }

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylle.css">
    <title>Atribuir Técnico</title>
<style>

</style>
</head>

<body>
    <div class="background"></div>
    <div class="card">
        <h2>Atribuir Técnico</h2>
        <div class="botao-pagina-container">
            <button id="btnManutencoes" class="botao-pagina" onclick="abrirModalCidades()">Manutenções</button>
        </div>
        <a href="menu.php" class="voltar-btn">Voltar ao Menu</a>
    </div>

    <div id="modalCidades" class="modal">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <button id="botaoVoltar" class="botao-voltar-icone" style="display: none;">&larr;</button>
            <span class="fechar" onclick="fecharModalCidades()">&times;</span>
            <h2 id="modalTitulo"></h2>
            
        </div>
        <ul id="listaCidades" class="grupo-lista">
            </ul>
        <button id="botaoAtribuirTecnico">Atribuir Técnico</button>
        <div id="mensagem-erro" class="mensagem-erro" style="display: none;"></div>
        <div id="mensagem-sucesso" class="mensagem-sucesso" style="display: none;"></div>


    </div>
</div>

    <script src="js/atribuir_tecnico.js"></script>
</body>

</html>
