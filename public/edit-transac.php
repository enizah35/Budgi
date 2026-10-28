<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if (!isset($_GET['id']) && !isset($_POST['id'])) {
    header("Location: gestion.php");
    exit;
}

$transaction_id = $_GET['id'] ?? $_POST['id'];
$user_id = $_SESSION['user_id'];
$error_msg = "";

try {
    $conn = new PDO($dsn, $username, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch existing transaction
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $transaction_id, 'user_id' => $user_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        header("Location: gestion.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $categorie = $_POST['categorie'];
        $type = ($_POST['type'] ?? '') === 'income' ? 'income' : 'expense';

        if (trim($description) === '' || (float) $amount <= 0 || !isset(categories_for($type)[$categorie])) {
            $error_msg = "Indique une description, un montant positif et une catégorie.";
            $transaction = array_merge($transaction, ['description' => $description, 'amount' => $amount, 'categorie' => $categorie, 'type' => $type]);
        } else {
            // Start transaction
            $conn->beginTransaction();

            // Update transaction
            $updateStmt = $conn->prepare("UPDATE transactions SET type = :type, description = :description, amount = :amount, categorie = :categorie WHERE id = :id");
            $updateStmt->execute([
                'type' => $type,
                'description' => $description,
                'amount' => $amount,
                'categorie' => $categorie,
                'id' => $transaction_id
            ]);

            // Ajuste le solde : annule l'ancien effet et applique le nouveau
            $budgetAdjustment = balance_effect($type, $amount) - balance_effect($transaction['type'], $transaction['amount']);
            if ($budgetAdjustment != 0) {
                $adjustStmt = $conn->prepare("UPDATE users SET current_budget = current_budget + :adjustment WHERE id = :user_id");
                $adjustStmt->execute(['adjustment' => $budgetAdjustment, 'user_id' => $user_id]);
            }

            $conn->commit();
            flash("Transaction modifiée.");
            header("Location: gestion.php");
            exit;
        }
    }

} catch (PDOException $e) {
    $error_msg = "Impossible d'enregistrer la modification.";
}

render_header('Modifier la transaction', '', true);
?>
<div class="auth">
    <div class="card auth-card">
        <div class="auth-head">
            <h2>Modifier la transaction</h2>
            <p>Ton solde sera ajusté automatiquement.</p>
        </div>

        <?php render_alert($error_msg); ?>

        <form action="edit-transac.php" method="post">
            <input type="hidden" name="id" value="<?= (int) $transaction_id ?>">
            <?php render_type_picker($transaction['type'], $transaction['categorie']); ?>
            <div class="field">
                <label for="amount">Montant</label>
                <div class="input-euro">
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="input" value="<?= e($transaction['amount']) ?>" inputmode="decimal" required autofocus>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="input" value="<?= e($transaction['description']) ?>" maxlength="100" required>
            </div>
            <div class="form-actions">
                <a href="gestion.php" class="btn btn-ghost">Annuler</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php render_footer(); ?>
