<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = ($_POST['type'] ?? '') === 'income' ? 'income' : 'expense';
    $amount = (float) ($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $categorie = $_POST['categorie'] ?? '';
    $user_id = $_SESSION['user_id'];
    $label = $type === 'income' ? 'Revenu' : 'Dépense';

    if ($amount <= 0 || $description === '' || !isset(categories_for($type)[$categorie])) {
        flash("Indique un montant positif, une description et une catégorie.", 'error');
        header("Location: gestion.php");
        exit;
    }

    try {
        $conn = new PDO($dsn, $username, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        $req = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, categorie) VALUES (:user_id, :type, :amount, :description, :categorie)");
        $req->execute([
            'user_id' => $user_id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'categorie' => $categorie
        ]);

        $nouvreq = $conn->prepare("UPDATE users SET current_budget = current_budget + :effect WHERE id = :user_id");
        $nouvreq->execute([
            'effect' => balance_effect($type, $amount),
            'user_id' => $user_id
        ]);

        $conn->commit();
        flash("$label « $description » ajouté" . ($type === 'income' ? '' : 'e') . ".");
    } catch (PDOException $e) {
        flash("Impossible d'enregistrer, réessaie dans un instant.", 'error');
    }
}

header("Location: gestion.php");
exit;
