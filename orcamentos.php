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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_orcamento'])) {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $valor_limite = floatval($_POST['valor_limite'] ?? 0);
    $mes_atual = date('m'); 
    $ano_atual = date('Y'); 

    if (!$utilizador_id) {
        $erro_sql = "Erro de Segurança: Sessão expirada. Faz login novamente.";
    } elseif ($categoria_id <= 0 || $valor_limite <= 0) {
        $erro_sql = "Por favor, escolhe uma categoria e define um valor válido.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO orcamentosFi (utilizador_id, categoria_id, valor_limite, mes, ano) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$utilizador_id, $categoria_id, $valor_limite, $mes_atual, $ano_atual]);
            
            header("Location: index.php?p=orcamentos&sucesso=1");
            exit;
        } catch (PDOException $e) {
            $erro_sql = "Erro ao guardar orçamento: " . $e->getMessage();
        }
    }
}

$orcamentos = [];
if ($utilizador_id) {
    try {
        $sql = "SELECT 
                    o.orcamento_id,
                    o.valor_limite,
                    c.nome AS nome_categoria,
                    COALESCE(SUM(t.valor), 0) AS total_gasto
                FROM orcamentosFi o
                JOIN categoriasFi c ON o.categoria_id = c.categoria_id
                LEFT JOIN transacoesFi t ON t.categoria_id = o.categoria_id 
                    AND t.utilizador_id = o.utilizador_id 
                    AND t.tipo = 'despesa'
                    AND MONTH(t.data_transacao) = MONTH(CURRENT_DATE())
                    AND YEAR(t.data_transacao) = YEAR(CURRENT_DATE())
                WHERE o.utilizador_id = ?
                GROUP BY o.orcamento_id, o.valor_limite, c.nome
                ORDER BY c.nome ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$utilizador_id]);
        $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $erro_listagem = "Erro ao calcular orçamentos: " . $e->getMessage();
    }
}
?>

<div class="dashboard-header" style="margin-bottom: 20px;">
    <h2>📊 Controlo de Orçamentos</h2>
    <p style="color: var(--text-muted);">Defina limites de gastos mensais e veja quanto ainda tem disponível.</p>
</div>

<?php if (isset($_GET['sucesso'])): ?>
    <div style="background: #e6fffa; color: #00a389; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #b2f5ea;">
        <i class="fa-solid fa-circle-check"></i> Orçamento definido com sucesso!
    </div>
<?php endif; ?>

<?php if (isset($erro_sql)): ?>
    <div style="background: #fff5f5; color: #e53e3e; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fed7d7;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($erro_sql) ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="adicionar_orcamento" value="1">
    
    <select name="categoria_id" required style="flex: 2;">
        <option value="" disabled selected>Escolha a Categoria...</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['categoria_id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
        <?php endforeach; ?>
    </select>
    
    <input type="number" step="0.01" min="1" name="valor_limite" placeholder="Limite Máximo (€)" required style="flex: 1;">
    <button type="submit"><i class="fa-solid fa-bullseye"></i> Definir Limite</button>
</form>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Limite Mensal</th>
                <th>Já Gasto (Este Mês)</th>
                <th>Disponível</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orcamentos)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                        <i class="fa-solid fa-wallet fa-2x" style="display:block; margin-bottom:10px; color:#ccc;"></i>
                        Ainda não definiu nenhum orçamento. Use o formulário acima!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($orcamentos as $o): 
                    $limite = $o['valor_limite'];
                    $gasto = $o['total_gasto'];
                    $disponivel = $limite - $gasto;
                    $percentagem = ($limite > 0) ? ($gasto / $limite) * 100 : 0;
                ?>
                    <tr>
                        <td><strong>📁 <?= htmlspecialchars($o['nome_categoria']) ?></strong></td>
                        <td style="color: var(--dark); font-weight: bold;"><?= number_format($limite, 2, ',', '.') ?> €</td>
                        <td style="color: #e53e3e;"><?= number_format($gasto, 2, ',', '.') ?> €</td>
                        <td style="font-weight: bold; color: <?= $disponivel < 0 ? '#e53e3e' : '#00a389' ?>;">
                            <?= number_format($disponivel, 2, ',', '.') ?> €
                        </td>
                        <td>
                            <?php if ($percentagem >= 100): ?>
                                <span style="background: #fff5f5; color: #e53e3e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Estourado 🚨</span>
                            <?php elseif ($percentagem >= 80): ?>
                                <span style="background: #fffaf0; color: #dd6b20; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Cuidado ⚠️</span>
                            <?php else: ?>
                                <span style="background: #e6fffa; color: #00a389; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Tranquilo ✅</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>