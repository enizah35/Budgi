<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

try {
    $conn = new PDO($dsn, $username, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user info
    $stmt = $conn->prepare("SELECT name, surname, email, budget_limit, current_budget FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $surname = $_POST['surname'];
        $email = $_POST['email'];
        $budget_limit = $_POST['budget_limit'];
        $current_budget = $_POST['current_budget'];
        $new_password = $_POST['new_password'];

        if (empty($name) || empty($surname) || empty($email)) {
            $error_msg = "Veuillez remplir les champs obligatoires.";
        } elseif (!empty($new_password) && strlen($new_password) < 8) {
            $error_msg = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } else {
            // Update basic info and budget
            $update_stmt = $conn->prepare("UPDATE users SET name = :name, surname = :surname, email = :email, budget_limit = :budget_limit, current_budget = :current_budget WHERE id = :id");
            $update_stmt->execute([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'budget_limit' => $budget_limit,
                'current_budget' => $current_budget,
                'id' => $user_id
            ]);

            // Update password if provided
            if (!empty($new_password)) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $pass_stmt->execute(['password' => $password_hash, 'id' => $user_id]);
            }

            $success_msg = "Profil mis à jour avec succès !";
            
            // Refresh local user data
            $user['name'] = $name;
            $user['surname'] = $surname;
            $user['email'] = $email;
            $user['budget_limit'] = $budget_limit;
            $user['current_budget'] = $current_budget;
        }
    }

} catch (PDOException $e) {
    $error_msg = "Impossible d'enregistrer le profil (email déjà utilisé ?).";
}

render_header('Mon profil', 'profile', true);
?>
<div class="auth">
    <div class="card auth-card wide">
        <div class="auth-head">
            <h2>Mon profil</h2>
            <p>Tes infos et les réglages de ton budget.</p>
        </div>

        <?php render_alert($success_msg, 'success'); ?>
        <?php render_alert($error_msg); ?>

        <form action="profile.php" method="post">
            <div class="field-row">
                <div class="field">
                    <label for="surname">Prénom</label>
                    <input type="text" name="surname" id="surname" class="input" value="<?= e($user['surname']) ?>" autocomplete="given-name" required>
                </div>
                <div class="field">
                    <label for="name">Nom</label>
                    <input type="text" name="name" id="name" class="input" value="<?= e($user['name']) ?>" autocomplete="family-name" required>
                </div>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="input" value="<?= e($user['email']) ?>" autocomplete="email" required>
            </div>

            <div class="form-section-title">Budget</div>
            <div class="field-row">
                <div class="field">
                    <label for="budget_limit">Limite mensuelle</label>
                    <div class="input-euro">
                        <input type="number" step="0.01" min="1" name="budget_limit" id="budget_limit" class="input" value="<?= e($user['budget_limit']) ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label for="current_budget">Solde actuel</label>
                    <div class="input-euro">
                        <input type="number" step="0.01" name="current_budget" id="current_budget" class="input" value="<?= e($user['current_budget']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section-title">Sécurité</div>
            <div class="field">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" name="new_password" id="new_password" class="input" placeholder="Laisser vide pour garder l'actuel" minlength="8" autocomplete="new-password">
            </div>

            <div class="form-actions">
                <a href="gestion.php" class="btn btn-ghost">Retour</a>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<?php render_footer(); ?>
