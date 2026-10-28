<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $budget_limit = $_POST['budget_limit'] ?? '';
    $current_budget = $_POST['current_budget'] ?? '';

    if ($budget_limit === '' || $budget_limit <= 0) {
        $error_msg = "Veuillez indiquer votre limite de dépenses.";
    } elseif ($current_budget === '') {
        $error_msg = "Veuillez indiquer votre solde actuel.";
    } else {
        try {
            $conn = new PDO($dsn, $username, $dbpassword);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $req = $conn->prepare("UPDATE users SET budget_limit = :budget_limit, current_budget = :current_budget, budget_setup_complete = 1 WHERE id = :user_id");
            $req->execute([
                'budget_limit' => $budget_limit,
                'current_budget' => $current_budget,
                'user_id' => $_SESSION['user_id']
            ]);
            header("Location: gestion.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Erreur lors de l'enregistrement, réessaie dans un instant.";
        }
    }
}

render_header('Définir mon budget', '', true);
?>
<div class="auth">
    <div class="card auth-card">
        <div class="auth-head">
            <img src="images/logo-icon.png" alt="" width="50" height="56">
            <h2>Définissons ton budget</h2>
            <p>Deux chiffres et c'est parti. Tu pourras les modifier à tout moment.</p>
        </div>

        <?php render_alert($error_msg); ?>

        <form action="set-budget.php" method="post">
            <div class="field">
                <label for="current_budget">Combien as-tu sur ton compte ?</label>
                <div class="input-euro">
                    <input type="number" step="0.01" name="current_budget" id="current_budget" class="input" placeholder="1200" inputmode="decimal" required autofocus>
                </div>
                <div class="field-hint">Ton solde actuel, il baissera à chaque dépense.</div>
            </div>
            <div class="field">
                <label for="budget_limit">Combien veux-tu dépenser au maximum par mois ?</label>
                <div class="input-euro">
                    <input type="number" step="0.01" min="1" name="budget_limit" id="budget_limit" class="input" placeholder="800" inputmode="decimal" required>
                </div>
                <div class="field-hint">On te préviendra quand tu t'en approches.</div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">C'est parti !<span class="icon">arrow_forward</span></button>
        </form>
    </div>
</div>
<?php render_footer(); ?>
