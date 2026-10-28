<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

$error_msg = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            $conn = new PDO($dsn, $username, $dbpassword);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $req = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $req->execute(['email' => $email]);
            $rep = $req->fetch(PDO::FETCH_ASSOC);

            if ($rep && password_verify($password, $rep['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $rep['id'];
                header("Location: " . ($rep['budget_setup_complete'] == 0 ? "set-budget.php" : "gestion.php"));
                exit;
            }
            $error_msg = "Email ou mot de passe incorrect.";
        } catch (PDOException $e) {
            $error_msg = "Impossible de se connecter au serveur, réessaie dans un instant.";
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs.";
    }
}

render_header('Se connecter', 'signin');
?>
<div class="auth">
    <div class="card auth-card">
        <div class="auth-head">
            <img src="images/logo-vertical.png" alt="Budgi" class="logo-vertical" width="80" height="112">
            <h2>Content de te revoir !</h2>
            <p>Connecte-toi pour retrouver ton budget.</p>
        </div>

        <?php render_alert($error_msg); ?>

        <form action="signin.php" method="post">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="input" placeholder="toi@exemple.fr" value="<?= e($email) ?>" autocomplete="email" required autofocus>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" class="input" placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <div class="auth-foot">
            <p>Pas encore de compte ? <a href="register.php">Inscris-toi gratuitement</a></p>
        </div>
    </div>
</div>
<?php render_footer(); ?>
