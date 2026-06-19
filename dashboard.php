<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexão com a Base de Dados
require_once __DIR__ . "/../config/database.php";

$utilizador_id = $_SESSION['utilizador_id'] ?? null;
$nome_utilizador = $_SESSION['nome'] ?? 'Utilizador';

// Se não houver sessão ativa, manda para o login
if (!$utilizador_id) {
    header("Location: index.php?p=login");
    exit;
}

$mensagem_sucesso = "";
$mensagem_erro = "";

// Processamento do formulário de novos movimentos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_movimento'])) {
    $descricao = trim($_POST['descricao'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $tipo = $_POST['tipo'] ?? 'Despesa';
    $data_movimento = $_POST['data_movimento'] ?? date('Y-m-d');

    if (!empty($descricao) && !empty($categoria) && $valor > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO movimentosFi (utilizador_id, descricao, categoria, valor, tipo, data_movimento) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$utilizador_id, $descricao, $categoria, $valor, $tipo, $data_movimento]);
            $mensagem_sucesso = "Movimento registado com sucesso!";
        } catch (Exception $e) {
            $mensagem_erro = "Erro ao guardar movimento: " . $e->getMessage();
        }
    } else {
        $mensagem_erro = "Preencha todos os campos corretamente.";
    }
}

// Carregamento de dados financeiros do utilizador logado
$saldo_disponivel = 0.00; $total_entradas = 0.00; $total_gastos = 0.00; $transacoes_pessoais = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM movimentosFi WHERE utilizador_id = ? ORDER BY data_movimento DESC, id DESC");
    $stmt->execute([$utilizador_id]);
    $transacoes_pessoais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($transacoes_pessoais as $tx) {
        $v = (float)$tx['valor'];
        if (strtolower($tx['tipo']) === 'entrada' || strtolower($tx['tipo']) === 'receita') { 
            $total_entradas += $v; 
        } else { 
            $total_gastos += $v; 
        }
    }
    $saldo_disponivel = $total_entradas - $total_gastos;
} catch (Exception $e) {}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { background-color: #020617 !important; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; color: #ffffff; }
    .dashboard-wrapper { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
    
    .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .dash-header h2 { font-size: 28px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
    .header-actions { display: flex; gap: 12px; }

    .btn-action { background: #2563eb; color: white !important; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-action:hover { background: #1d4ed8; transform: translateY(-1px); }
    .btn-logout { background: #1e293b; color: #f43f5e !important; border: 1px solid #334155; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    
    .tabs-nav { display: flex; gap: 30px; border-bottom: 2px solid #1e3a8a; margin-bottom: 35px; }
    .tab-btn { background: none; border: none; color: #93c5fd; font-size: 15px; font-weight: 600; padding: 14px 4px; cursor: pointer; position: relative; display: flex; align-items: center; gap: 8px; }
    .tab-btn.active { color: #3b82f6; font-weight: 700; } 
    .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background: #3b82f6; border-radius: 4px; }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 35px; }
    .stat-card { background: #0f172a; padding: 24px; border-radius: 16px; border: 2px solid #1e3a8a; }
    .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .stat-title { color: #cbd5e1; font-size: 14px; font-weight: 700; text-transform: uppercase; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; justify-content: center; align-items: center; background: rgba(37, 99, 235, 0.2); color: #3b82f6; }
    .stat-value { font-size: 34px; font-weight: 800; margin: 0; }

    .main-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px; }
    @media (max-width: 992px) { .main-grid { grid-template-columns: 1fr; } }
    
    .content-box { background: #0f172a; padding: 24px; border-radius: 16px; border: 2px solid #1e3a8a; margin-bottom: 25px; }
    .box-title { font-size: 19px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #1e3a8a; padding-bottom: 10px; }

    .form-control { width: 100%; padding: 11px; background: #1e293b; border: 1px solid #1e3a8a; color: white; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; color: #cbd5e1; font-weight: 600; font-size: 13px; margin-bottom: 6px; }

    .custom-table { width: 100%; border-collapse: collapse; text-align: left; }
    .custom-table th { padding: 14px; color: #94a3b8; font-weight: 700; font-size: 14px; border-bottom: 2px solid #1e3a8a; text-transform: uppercase; }
    .custom-table td { padding: 14px; border-bottom: 1px solid #1e3a8a; font-size: 14px; color: #ffffff; }
    .custom-table tr:hover { background: rgba(30, 58, 138, 0.15); }

    .tx-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid #1e3a8a; margin-bottom: 16px; }
    .tx-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .tx-amount { font-weight: 700; } .tx-pos { color: #38bdf8; } .tx-neg { color: #f43f5e; }

    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 6, 23, 0.85); display: none; justify-content: center; align-items: center; z-index: 9999; }
    .modal-box { background: #0f172a; border: 2px solid #2563eb; padding: 30px; border-radius: 16px; width: 100%; max-width: 450px; }
</style>

<div class="dashboard-wrapper">
    
    <?php if(!empty($mensagem_sucesso)): ?><script>alert("<?= $mensagem_sucesso ?>"); window.location.href="index.php?p=dashboard";</script><?php endif; ?>
    <?php if(!empty($mensagem_erro)): ?><script>alert("<?= $mensagem_erro ?>");</script><?php endif; ?>

    <header class="dash-header">
        <div>
            <h2>Olá, <?= htmlspecialchars($nome_utilizador) ?> 👋</h2>
            <p style="color:#93c5fd; margin:5px 0 0 0;">Controlo financeiro pessoal centralizado.</p>
        </div>
        <div class="header-actions">
            <button class="btn-action" onclick="document.getElementById('movimentoModal').style.display='flex'"><i class="fas fa-plus"></i> Novo Movimento</button>
            <a href="index.php?p=logout" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </header>

    <nav class="tabs-nav">
        <button class="tab-btn active" onclick="alterarAba(event, 'visao-geral')"><i class="fas fa-chart-pie"></i> Visão Geral</button>
        <button class="tab-btn" onclick="alterarAba(event, 'historico-pessoal')"><i class="fas fa-wallet"></i> Histórico Detalhado</button>
    </nav>

    <div id="visao-geral" class="tab-content active">
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Saldo Atual</span><div class="stat-icon"><i class="fas fa-wallet"></i></div></div>
                <h3 class="stat-value">€ <?= number_format($saldo_disponivel, 2, ',', '.') ?></h3>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Receitas</span><div class="stat-icon" style="color:#38bdf8;"><i class="fas fa-arrow-up"></i></div></div>
                <h3 class="stat-value">€ <?= number_format($total_entradas, 2, ',', '.') ?></h3>
            </div>
            <div class="stat-card">
                <div class="stat-header"><span class="stat-title">Despesas</span><div class="stat-icon" style="color:#f43f5e;"><i class="fas fa-arrow-down"></i></div></div>
                <h3 class="stat-value">€ <?= number_format($total_gastos, 2, ',', '.') ?></h3>
            </div>
        </section>

        <div class="main-grid">
            <div class="content-box">
                <div class="box-title">Gráfico Estatístico</div>
                <div style="height: 300px;"><canvas id="financialChart"></canvas></div>
            </div>
            <div class="content-box">
                <div class="box-title">Atividades Recentes</div>
                <div class="tx-list">
                    <?php if(empty($transacoes_pessoais)): ?>
                        <p style="text-align:center; color:#94a3b8; font-size:14px;">Nenhum movimento registado.</p>
                    <?php else: ?>
                        <?php foreach(array_slice($transacoes_pessoais, 0, 4) as $tx): $isE = (strtolower($tx['tipo'])==='entrada' || strtolower($tx['tipo'])==='receita'); ?>
                            <div class="tx-item">
                                <div>
                                    <h4 style="margin:0; font-size:14px;"><?= htmlspecialchars($tx['descricao']) ?></h4>
                                    <small style="color:#94a3b8;"><?= date('d/m/Y', strtotime($tx['data_movimento'])) ?> • <?= htmlspecialchars($tx['categoria']) ?></small>
                                </div>
                                <span class="tx-amount <?= $isE ? 'tx-pos' : 'tx-neg' ?>"><?= $isE ? '+' : '-' ?> €<?= number_format($tx['valor'], 2, ',', '.') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="historico-pessoal" class="tab-content">
        <div class="content-box">
            <div class="box-title">Extrato Geral de Movimentos</div>
            <table class="custom-table">
                <thead><tr><th>Descrição</th><th>Categoria</th><th>Data</th><th>Tipo</th><th>Valor</th></tr></thead>
                <tbody>
                    <?php foreach($transacoes_pessoais as $tx): $isE = (strtolower($tx['tipo'])==='entrada' || strtolower($tx['tipo'])==='receita'); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($tx['descricao']) ?></strong></td>
                            <td><?= htmlspecialchars($tx['categoria']) ?></td>
                            <td><?= date('d/m/Y', strtotime($tx['data_movimento'])) ?></td>
                            <td style="color:<?= $isE?'#38bdf8':'#f43f5e'?>; font-weight:700;"><?= $isE?'Entrada':'Despesa'?></td>
                            <td class="<?= $isE?'tx-pos':'tx-neg'?>" style="font-weight:700;">€<?= number_format($tx['valor'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="movimentoModal" class="modal-backdrop">
    <div class="modal-box">
        <h3>Adicionar Novo Movimento</h3>
        <form method="POST" action="">
            <div class="form-group"><label>Descrição</label><input type="text" name="descricao" class="form-control" required></div>
            <div class="form-group"><label>Categoria</label><input type="text" name="categoria" class="form-control" required></div>
            <div class="form-group"><label>Valor (€)</label><input type="number" step="0.01" name="valor" class="form-control" required></div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control"><option value="Despesa">Despesa</option><option value="Entrada">Entrada</option></select>
            </div>
            <div class="form-group"><label>Data</label><input type="date" name="data_movimento" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn-logout" style="padding:10px 20px;" onclick="document.getElementById('movimentoModal').style.display='none'">Cancelar</button>
                <button type="submit" name="adicionar_movimento" class="btn-action">Gravar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function alterarAba(event, nomeAba) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(nomeAba).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    const ctx = document.getElementById('financialChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
            datasets: [{
                label: 'Receitas (€)',
                data: [<?= $total_entradas * 0.2 ?>, <?= $total_entradas * 0.5 ?>, <?= $total_entradas * 0.7 ?>, <?= $total_entradas ?>],
                borderColor: '#38bdf8', backgroundColor: 'rgba(56, 189, 248, 0.05)', borderWidth: 3, tension: 0.35, fill: true
            }, {
                label: 'Despesas (€)',
                data: [<?= $total_gastos * 0.3 ?>, <?= $total_gastos * 0.6 ?>, <?= $total_gastos * 0.8 ?>, <?= $total_gastos ?>],
                borderColor: '#f43f5e', backgroundColor: 'rgba(244, 63, 94, 0.05)', borderWidth: 3, tension: 0.35, fill: true
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#ffffff', font: { family: 'Plus Jakarta Sans', weight: '600' } } } },
            scales: {
                y: { grid: { color: '#1e3a8a', borderDash: [5, 5] }, ticks: { color: '#ffffff' } },
                x: { grid: { display: false }, ticks: { color: '#ffffff' } }
            }
        }
    });
</script>