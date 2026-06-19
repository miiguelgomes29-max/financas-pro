<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças Pro</title>
    <link rel="stylesheet" href="/11TGPSI2526/11/gestao_financeira/css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fa-solid fa-wallet"></i> Finanças Pro
        </div>
        <div class="nav-links">
            <a href="index.php?p=dashboard"><i class="fa-solid fa-chart-pie"></i> Início</a>
            <a href="index.php?p=transacoes"><i class="fa-solid fa-money-bill-transfer"></i> Transações</a>
            <a href="index.php?p=orcamentos"><i class="fa-solid fa-calculator"></i> Orçamentos</a>
            <a href="index.php?p=metas"><i class="fa-solid fa-bullseye"></i> Metas</a>
        </div>
        <div class="nav-user">
            <span><i class="fa-solid fa-circle-user"></i> <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Utilizador'); ?></span>
            <a href="/11TGPSI2526/11/gestao_financeira/acoes/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
        </div>
    </nav>
    <main class="container">