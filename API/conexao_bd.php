<?php
// Arquivo de conexão com o banco de dados

// Configurações do banco de dados (AJUSTE CONFORME SEU AMBIENTE)
$servername = "localhost";
$username = "root";     // Substitua pelo seu usuário do banco de dados
$password = "";         // Substitua pela sua senha do banco de dados
$dbname = "gerenciamento_manutencoes"; // Substitua pelo nome do seu banco de dados

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    // Se a conexão falhar, o script é encerrado com uma mensagem de erro
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// A conexão está agora disponível na variável $conn para ser utilizada em outros arquivos.
