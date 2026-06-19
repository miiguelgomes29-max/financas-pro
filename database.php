<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "11TGPSIBD_11"; // O teu nome real da base de dados configurado!

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div style='background:#f8d7da; color:#721c24; padding:15px; margin:20px; border-radius:5px; font-family:sans-serif;'>
            <b>Erro de Ligação à Base de Dados:</b> " . $e->getMessage() . "
         </div>");
}

// Garante que a função getDB() funciona em todos os ficheiros antigos
if (!function_exists('getDB')) {
    function getDB() {
        global $pdo;
        return $pdo;
    }
}

if (!function_exists('redirecionar')) {
    function redirecionar($url) {
        header("Location: " . $url);
        exit;
    }
}
?>