<?php
// Garante que a sessão está ativa para poder ser destruída
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpa todas as variáveis da sessão
$_SESSION = array();

// Destrói a sessão no servidor
session_destroy();

// Redireciona diretamente para o ecrã de login (anda para trás e entra em paginas/)
header("Location: ../paginas/login.php");
exit;
?>