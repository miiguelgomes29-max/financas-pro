<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 📂 Conexão com a Base de Dados (recua uma pasta e entra em config)
require_once __DIR__ . "/../config/database.php"; 

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos!";
    } else {
        try {
            // 1. Procurar o utilizador na tabela pelo e-mail
            $stmt = $pdo->prepare("SELECT * FROM utilizadoresFi WHERE email = ?");
            $stmt->execute([$email]);
            $utilizador = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verificar se o utilizador existe e comparar a senha DIRETAMENTE (Texto Limpo)
            if ($utilizador && $senha === $utilizador['senha']) {
                
                // 3. Guardar os dados essenciais na Sessão global
                $_SESSION['utilizador_id'] = $utilizador['id'] ?? $utilizador['utilizador_id'];
                $_SESSION['nome']          = $utilizador['nome'] ?? 'Utilizador';
                $_SESSION['tipo']          = isset($utilizador['tipo']) ? (int)$utilizador['tipo'] : 0; 

                // 4. 🚀 Redirecionamento Inteligente com base no tipo de conta através do index.php
                if ($_SESSION['tipo'] === 1) {
                    header("Location: index.php?p=admin"); 
                } else {
                    header("Location: index.php?p=dashboard");
                }
                exit;
            } else {
                $erro = "E-mail ou Palavra-passe incorretos!";
            }
        } catch (PDOException $e) {
            $erro = "Erro no sistema: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sessão - Finanças Pro</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-login">
    <div class="login-container">
        <div class="login-header">
            <i class="fa-solid fa-wallet fa-3x" style="color: #4f46e5;"></i>
            <h2>Iniciar Sessão</h2>
            <p>Aceda à sua conta para gerir as suas finanças.</p>
        </div>

        <?php if(!empty($erro)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 14px; text-align: left;">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Erro:</strong> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <i class="fa-solid fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="E-mail" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="senha" placeholder="Palavra-passe" required>
            </div>
            <button type="submit" class="btn-primary" style="background: #4f46e5; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">
                Entrar <i class="fa-solid fa-sign-in-alt"></i>
            </button>
        </form>
        
        <p class="login-footer" style="margin-top: 20px; text-align: center;">
            Ainda não tem conta? <a href="index.php?p=registar" style="color: #4f46e5; font-weight: bold;">Criar Conta</a>
        </p>
    </div>
</body>
</html>