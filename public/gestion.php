<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$error_msg = "";
$user = [];
$transactions = [];
$categoryData = [];
$spent_month = 0;
$income_month = 0;

try {
    $conn = new PDO($dsn, $username, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $req = $conn->prepare("SELECT surname, budget_limit, current_budget, budget_setup_complete FROM users WHERE id = :user_id");
    $req->execute(['user_id' => $_SESSION['user_id']]);
    $user = $req->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$user) {
        header("Location: logout.php");
        exit;
    }
    if ($user['budget_setup_complete'] == 0) {
        header("Location: set-budget.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY date DESC, id DESC");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dépenses du mois en cours, totales et par catégorie
    $month = date('Y-m');
    foreach ($transactions as $t) {
        if (substr($t['date'], 0, 7) !== $month) {
            continue;
        }
        if ($t['type'] === 'income') {
            $income_month += (float) $t['amount'];
            continue;
        }
        $spent_month += (float) $t['amount'];
        $categoryData[$t['categorie']] = ($categoryData[$t['categorie']] ?? 0) + (float) $t['amount'];
    }
    arsort($categoryData);
} catch (PDOException $e) {
    $error_msg = "Impossible de charger tes données, réessaie dans un instant.";
}

$budget_limit = (float) ($user['budget_limit'] ?? 0);
$current_budget = (float) ($user['current_budget'] ?? 0);
$remaining = $budget_limit - $spent_month;
$percent = $budget_limit > 0 ? min(100, $spent_month / $budget_limit * 100) : 0;
$barClass = $percent >= 100 ? 'over' : ($percent >= 80 ? 'warn' : '');

$months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$monthLabel = $months[(int) date('n') - 1] . ' ' . date('Y');

$flash = flash();

render_header(
    'Tableau de bord',
    'gestion',
    true,
    '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>'
);
?>
<div class="dashboard container">
    <div class="dash-head">
        <div>
            <h1>Salut <?= e($user['surname'] ?? '') ?> 👋</h1>
            <p>Voici où en est ton budget de <?= $monthLabel ?>.</p>
        </div>
        <a href="#add" class="btn btn-primary"><span class="icon">add</span>Nouvelle transaction</a>
    </div>

    <?php if ($flash) render_alert($flash[0], $flash[1]); ?>
    <?php render_alert($error_msg); ?>
    <?php if ($percent >= 100): ?>
        <div class="alert alert-error"><span class="icon">warning</span>Tu as dépassé ta limite mensuelle de <?= euro($budget_limit) ?>. Lève le pied !</div>
    <?php elseif ($percent >= 80): ?>
        <div class="alert alert-warning"><span class="icon">info</span>Attention, tu as déjà utilisé <?= round($percent) ?>% de ton budget du mois.</div>
    <?php endif; ?>

    <div class="stats">
        <div class="card stat">
            <div class="stat-label"><span class="icon">account_balance</span>Solde actuel</div>
            <div class="stat-value<?= $current_budget < 0 ? ' negative' : '' ?>"><?= euro($current_budget) ?></div>
            <div class="stat-sub">Sur ton compte</div>
        </div>
        <div class="card stat">
            <div class="stat-label"><span class="icon">trending_up</span>Revenus du mois</div>
            <div class="stat-value positive">+<?= euro($income_month) ?></div>
            <div class="stat-sub">Job, bourse, famille…</div>
        </div>
        <div class="card stat">
            <div class="stat-label"><span class="icon">shopping_bag</span>Dépensé ce mois</div>
            <div class="stat-value"><?= euro($spent_month) ?></div>
            <div class="progress"><div class="progress-bar <?= $barClass ?>" style="width: <?= round($percent, 1) ?>%"></div></div>
            <div class="stat-sub"><?= round($percent) ?>% de ta limite de <?= euro($budget_limit) ?></div>
        </div>
        <div class="card stat">
            <div class="stat-label"><span class="icon">savings</span>Reste à dépenser</div>
            <div class="stat-value<?= $remaining < 0 ? ' negative' : '' ?>"><?= euro($remaining) ?></div>
            <div class="stat-sub">Jusqu'à la fin du mois</div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-side">
            <div class="card" id="add">
                <div class="card-header"><h3>Nouvelle transaction</h3></div>
                <form action="new-transac.php" method="post">
                    <?php render_type_picker(); ?>
                    <div class="field">
                        <label for="amount">Montant</label>
                        <div class="input-euro">
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="input" placeholder="0,00" inputmode="decimal" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" class="input" placeholder="Ex : courses, salaire…" maxlength="100" required>
                    </div>
                    <button type="submit" class="btn btn-mint btn-block" data-submit-label><span class="icon">add</span><span class="label">Ajouter</span></button>
                </form>
            </div>

            <div class="card">
                <div class="card-header"><h3>Répartition du mois</h3></div>
                <?php if ($categoryData): ?>
                    <div class="chart-wrap"><canvas id="spendingChart" height="240"></canvas></div>
                    <ul class="chart-legend">
                        <?php foreach ($categoryData as $cat => $amount): ?>
                            <li>
                                <span class="dot" style="background: <?= CATEGORIES[$cat][1] ?? '#64748b' ?>"></span>
                                <?= e($cat) ?>
                                <span class="amount"><?= euro($amount) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty"><span class="icon">donut_large</span><p>Aucune dépense ce mois-ci.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Historique</h3>
                <span class="count-badge"><?= count($transactions) ?> transaction<?= count($transactions) > 1 ? 's' : '' ?></span>
            </div>
            <?php if ($transactions): ?>
                <ul class="tx-list">
                    <?php foreach ($transactions as $t):
                        $income = $t['type'] === 'income';
                        $cat = categories_for($t['type'])[$t['categorie']] ?? ['more_horiz', '#64748b']; ?>
                        <li class="tx" style="--cat: <?= $cat[1] ?>">
                            <span class="tx-icon"><span class="icon"><?= $cat[0] ?></span></span>
                            <div class="tx-main">
                                <div class="tx-title"><?= e($t['description']) ?></div>
                                <div class="tx-meta"><?= e($t['categorie']) ?> · <?= date('d/m/Y', strtotime($t['date'])) ?></div>
                            </div>
                            <span class="tx-amount<?= $income ? ' positive' : '' ?>"><?= $income ? '+' : '−' ?><?= euro($t['amount']) ?></span>
                            <div class="tx-actions">
                                <a href="edit-transac.php?id=<?= (int) $t['id'] ?>" class="icon-btn" title="Modifier" aria-label="Modifier"><span class="icon">edit</span></a>
                                <a href="delete-transac.php?id=<?= (int) $t['id'] ?>" class="icon-btn danger" title="Supprimer" aria-label="Supprimer" data-confirm="Supprimer « <?= e($t['description']) ?> » ? Ton solde sera ajusté."><span class="icon">delete</span></a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="empty">
                    <span class="icon">receipt_long</span>
                    <p>Aucune transaction pour le moment.<br>Ajoute ta première dépense ou ton premier revenu !</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$chartScript = '';
if ($categoryData) {
    $colors = array_map(fn($c) => CATEGORIES[$c][1] ?? '#64748b', array_keys($categoryData));
    $chartScript = '<script>
    window.addEventListener("DOMContentLoaded", () => {
        new Chart(document.getElementById("spendingChart"), {
            type: "doughnut",
            data: {
                labels: ' . json_encode(array_keys($categoryData)) . ',
                datasets: [{
                    data: ' . json_encode(array_values($categoryData)) . ',
                    backgroundColor: ' . json_encode($colors) . ',
                    borderWidth: 3,
                    borderColor: "#fff",
                    hoverOffset: 6
                }]
            },
            options: {
                cutout: "68%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: (c) => " " + c.parsed.toLocaleString("fr-FR", { style: "currency", currency: "EUR" }) }
                    }
                }
            }
        });
    });
    </script>';
}
render_footer($chartScript);
