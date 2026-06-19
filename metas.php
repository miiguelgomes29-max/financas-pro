<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/database.php";

$utilizador_id = $_SESSION['utilizador_id'] ?? null;
$categorias = [];

try {
    $stmt_cat = $pdo->query("SELECT * FROM categoriasFi ORDER BY nome ASC");
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro_sql = "Erro ao carregar categorias.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_meta'])) {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $valor_alvo = floatval($_POST['valor_alvo'] ?? 0);
    $data_limite = $_POST['data_limite'] ?: null;

    if (!$utilizador_id) {
        $erro_sql = "Erro de Segurança: Sessão expirada. Faça login novamente.";
    } elseif ($categoria_id <= 0 || empty($descricao) || $valor_alvo <= 0) {
        $erro_sql = "Por favor, preencha todos os campos corretamente.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO metasFi (utilizador_id, categoria_id, descricao, valor_alvo, data_limite) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$utilizador_id, $categoria_id, $descricao, $valor_alvo, $data_limite]);
            
            header("Location: index.php?p=metas&sucesso=1");
            exit;
        } catch (PDOException $e) {
            $erro_sql = "Erro ao guardar meta: " . $e->getMessage();
        }
    }
}

$metas = [];
if ($utilizador_id) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, c.nome AS nome_categoria 
                               FROM metasFi m 
                               LEFT JOIN categoriasFi c ON m.categoria_id = c.categoria_id 
                               WHERE m.utilizador_id = ? 
                               ORDER BY m.data_limite ASC");
        $stmt->execute([$utilizador_id]);
        $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_listagem = "Erro ao carregar metas: " . $e->getMessage();
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 20px;">
    <h2>🎯 Metas Financeiras</h2>
    <p style="color: var(--text-muted);">Planeie os seus objetivos de poupança por categoria e defina prazos.</p>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div style="background: #e6fffa; color: #00a389; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #b2f5ea;">
        <i class="fa-solid fa-circle-check"></i> Meta estabelecida com sucesso! Organize o seu orçamento para a atingir.
    </div>
<?php endif; ?>

<?php if (isset($erro_sql)): ?>
    <div style="background: #fff5f5; color: #e53e3e; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fed7d7;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erro_sql) ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="adicionar_meta" value="1">
    
    <input type="text" name="descricao" placeholder="Objetivo (Ex: Comprar Carro, Fundo de Emergência...)" required style="flex: 2;">
    
    <select name="categoria_id" required style="flex: 1;">
        <option value="" disabled selected>Categoria</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['categoria_id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?>
    </select>
    
    <input type="number" step="0.01" min="0.01" name="valor_alvo" placeholder="Valor Alvo (€)" required style="flex: 1;">
    
    <input type="date" name="data_limite" required style="flex: 1;">
    <button type="submit"><i class="fa-solid fa-bullseye"></i> Adicionar</button>
</form>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
    <table>
        <thead>
            <tr>
                <th>Objetivo</th>
                <th>Categoria</th>
                <th>Valor Alvo</th>
                <th>Data Limite</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($metas)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        <i class="fa-solid fa-trophy fa-2x" style="display:block; margin-bottom:10px; color:#ccc;"></i>
                        Ainda não criou metas financeiras. Defina o seu primeiro objetivo acima!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($metas as $m): ?>
                    <tr>
                        <td><strong>🎯 <?= htmlspecialchars($m['descricao']) ?></strong></td>
                        <td><span style="color: #4a5568; background: #edf2f7; padding: 4px 8px; border-radius: 4px; font-size: 13px;">📁 <?= htmlspecialchars($m['nome_categoria'] ?? 'Geral') ?></span></td>
                        <td style="font-weight: bold; color: #2b6cb0;"><?= number_format($m['valor_alvo'], 2, ',', '.') ?> €</td>
                        <td>
                            <span style="color: var(--text-muted);">
                                <i class="fa-regular fa-calendar-days"></i> <?= date('d/m/Y', strtotime($m['data_limite'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>