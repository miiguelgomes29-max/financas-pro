<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 📂 CAMINHO CORRIGIDO: Recua uma pasta para encontrar a 'config'
require_once __DIR__ . "/../config/database.php";

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // O tipo 2 (User normal) é definido automaticamente
            $stmt = $pdo->prepare("INSERT INTO utilizadoresFi (nome, email, senha, tipo) VALUES (?, ?, ?, 2)");
            $stmt->execute([$nome, $email, $senha_hash]);
            
            $sucesso = "Conta criada com sucesso! Já pode fazer o seu login.";
        } catch (PDOException $e) {
            $erro = "Erro ao registar. O email introduzido já poderá estar em uso.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Criar Conta - Finanças Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #0f172a; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; outline: none; }
        input:focus { border-color: #4f46e5; }
        button { width: 100%; padding: 14px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 10px; transition: 0.3s; }
        button:hover { background: #059669; }
        .erro { background: #fff5f5; color: #e53e3e; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #fed7d7; font-size: 14px; }
        .sucesso { background: #e6fffa; color: #00a389; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #b2f5ea; font-size: 14px; }
        .link-rodape { display: block; margin-top: 20px; color: #64748b; text-decoration: none; font-size: 14px; }
        .link-rodape:hover { color: #10b981; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Criar Conta Grátis 🚀</h2>
        
        <?php if (!empty($erro)): ?> <div class="erro"><?= $erro ?></div> <?php endif; ?>
        <?php if (!empty($sucesso)): ?> <div class="sucesso"><?= $sucesso ?></div> <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="nome" placeholder="O seu Nome completo" required>
            <input type="email" name="email" placeholder="O seu melhor Email" required>
            <input type="password" name="senha" placeholder="Crie uma Senha forte" required>
            <button type="submit">Registar Agora</button>
        </form>
        <a href="login.php" class="link-rodape">Já tem uma conta criada? Entre aqui.</a>
    </div>
</body>
</html>