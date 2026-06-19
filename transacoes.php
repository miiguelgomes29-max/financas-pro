<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

$categorias = [];
$utilizador_id = $_SESSION['utilizador_id'] ?? null; 

try {
    $stmt_cat = $pdo->query("SELECT * FROM categoriasFi ORDER BY nome ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_sql = "Erro ao carregar categorias: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_transacao'])) {
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $tipo = $_POST['tipo'] ?? ''; 
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $data_transacao = $_POST['data_transacao'] ?: date('Y-m-d');

    if (!$utilizador_id) {
        $erro_sql = "Erro: Sessão expirou. Faça login novamente.";
    } elseif (empty($descricao) || $valor <= 0 || empty($tipo) || $categoria_id <= 0) {
        $erro_sql = "Por favor, preencha todos os campos corretamente.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO transacoesFi (utilizador_id, categoria_id, descricao, valor, tipo, data_transacao) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$utilizador_id, $categoria_id, $descricao, $valor, $tipo, $data_transacao]);
            
            header("Location: index.php?p=transacoes&sucesso=1");
            exit;
        } catch (PDOException $e) {
            $erro_sql = "Erro ao guardar transação: " . $e->getMessage();
        }
    }
}

$transacoes = [];
if ($utilizador_id) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, c.nome AS nome_categoria 
                               FROM transacoesFi t 
                               LEFT JOIN categoriasFi c ON t.categoria_id = c.categoria_id 
                               WHERE t.utilizador_id = ? 
                               ORDER BY t.data_transacao DESC");
        $stmt->execute([$utilizador_id]);
        $transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_listagem = "Erro ao carregar transações: " . $e->getMessage();
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 20px;">
    <h2>💸 Gestão de Transações</h2>
    <p style="color: var(--text-muted);">Adicione e controle o seu fluxo de caixa de forma rápida.</p>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div style="background: #e6fffa; color: #00a389; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #b2f5ea;">
        <i class="fa-solid fa-circle-check"></i> Transação registada com sucesso!
    </div>
<?php endif; ?>

<?php if (isset($erro_sql)): ?>
    <div style="background: #fff5f5; color: #e53e3e; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fed7d7;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erro_sql) ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="adicionar_transacao" value="1">
    <input type="text" name="descricao" placeholder="Descrição (Ex: Salário...)" required>
    <input type="number" step="0.01" min="0.01" name="valor" placeholder="Valor (€)" required>
    
    <select name="tipo" required>
        <option value="" disabled selected>Tipo</option>
        <option value="receita">📈 Receita (+)</option>
        <option value="despesa">📉 Despesa (-)</option>
    </select>

    <select name="categoria_id" required>
        <option value="" disabled selected>Categoria</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['categoria_id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?>
    </select>
    
    <input type="date" name="data_transacao" value="<?= date('Y-m-d') ?>" required>
    <button type="submit"><i class="fa-solid fa-plus"></i> Gravar</button>
</form>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Tipo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transacoes)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        <i class="fa-solid fa-receipt fa-2x" style="display:block; margin-bottom:10px; color:#ccc;"></i>
                        Nenhuma transação encontrada. Use o formulário acima para começar!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($transacoes as $t): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($t['data_transacao'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($t['descricao']) ?></strong>
                            <small style="display:block; color: #a0aec0; font-size: 11px; margin-top: 2px;">
                                📁 <?= htmlspecialchars($t['nome_categoria'] ?? 'Sem Categoria') ?>
                            </small>
                        </td>
                        <td>
                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: <?= $t['tipo'] === 'receita' ? '#e6fffa; color: #00a389;' : '#fff5f5; color: #e53e3e;' ?>">
                                <?= $t['tipo'] === 'receita' ? '📈 Receita' : '📉 Despesa' ?>
                            </span>
                        </td>
                        <td style="font-weight: bold; color: <?= $t['tipo'] === 'receita' ? '#00a389;' : '#e53e3e;' ?>">
                            <?= $t['tipo'] === 'receita' ? '+' : '-' ?> <?= number_format($t['valor'], 2, ',', '.') ?> €
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>