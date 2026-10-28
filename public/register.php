<?php
session_start();
require_once __DIR__ . '/../includes/layout.php';

$error_msg = "";
$name = $surname = $email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['password_confirm'] ?? '';

    if (empty($name)) {
        $error_msg = "Veuillez entrer votre nom";
    } elseif (empty($surname)) {
        $error_msg = "Veuillez entrer votre prénom";
    } elseif (empty($email)) {
        $error_msg = "Veuillez entrer votre email";
    } elseif (empty($password)) {
        $error_msg = "Veuillez entrer votre mot de passe";
    } elseif (strlen($password) < 8) {
        $error_msg = "Le mot de passe doit contenir au moins 8 caractères";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Les mots de passe ne correspondent pas !";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        require_once __DIR__ . '/../config/database.php';

        try {
            $conn = new PDO($dsn, $username, $dbpassword);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $req = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $req->execute(['email' => $email]);

            if ($req->fetch()) {
                $error_msg = "Cet email est déjà utilisé.";
            } else {
                $req = $conn->prepare("INSERT INTO users (name, surname, email, password) VALUES (:name, :surname, :email, :password)");
                $req->execute([
                    "name" => $name,
                    "surname" => $surname,
                    "email" => $email,
                    "password" => $hashed_password
                ]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = $conn->lastInsertId();

                header("Location: set-budget.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Erreur lors de l'inscription, réessaie dans un instant.";
        }
    }
}

render_header('Créer un compte');
?>
<div class="auth">
    <div class="card auth-card">
        <div class="auth-head">
            <img src="images/logo-vertical.png" alt="Budgi" class="logo-vertical" width="80" height="112">
            <h2>Crée ton compte</h2>
            <p>Gratuit, et ça prend moins d'une minute.</p>
        </div>

        <?php render_alert($error_msg); ?>

        <form action="register.php" method="post">
            <div class="field-row">
                <div class="field">
                    <label for="surname">Prénom</label>
                    <input type="text" name="surname" id="surname" class="input" placeholder="Camille" value="<?= e($surname) ?>" autocomplete="given-name" required autofocus>
                </div>
                <div class="field">
                    <label for="name">Nom</label>
                    <input type="text" name="name" id="name" class="input" placeholder="Martin" value="<?= e($name) ?>" autocomplete="family-name" required>
                </div>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="input" placeholder="toi@exemple.fr" value="<?= e($email) ?>" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" name="password" id="password" class="input" placeholder="8 caractères minimum" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" name="password_confirm" id="password_confirm" class="input" placeholder="••••••••" minlength="8" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <div class="auth-foot">
            <p>Déjà inscrit ? <a href="signin.php">Se connecter</a></p>
        </div>
    </div>
</div>
<?php render_footer(); ?>
