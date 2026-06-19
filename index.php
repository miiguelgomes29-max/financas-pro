<?php
// 1. INICIAR SESSÃO GLOBAL
// Garante que a sessão está ativa logo a partir do ficheiro principal
// Assim, o login, o admin e o dashboard partilham a mesma memória.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SISTEMA DE ROTAS INTELIGENTE
// Se o utilizador escrever apenas o endereço do site (sem '?p='), 
// o sistema assume a palavra 'landing' como página inicial.
$pagina_solicitada = $_GET['p'] ?? 'landing';

// 3. SEGURANÇA MÁXIMA (Prevenção contra Directory Traversal)
// Limpa a variável para garantir que ninguém tenta aceder a ficheiros do sistema
// Exemplo: impede coisas como "?p=../../etc/passwd"
$pagina_limpa = basename($pagina_solicitada);

// 4. DEFINIR O CAMINHO DO FICHEIRO
// Procura a página solicitada dentro da tua pasta 'paginas'
$caminho_ficheiro = __DIR__ . "/paginas/" . $pagina_limpa . ".php";

// 5. CARREGAR A PÁGINA
// Se o ficheiro existir, ele injeta-o no ecrã.
// Se não existir (ex: o utilizador inventou um link ou apagaste a página),
// ele redireciona para a landing page automaticamente para não dar "Erro 404".
if (file_exists($caminho_ficheiro)) {
    require_once $caminho_ficheiro;
} else {
    require_once __DIR__ . "/paginas/landing.php";
}
?>
