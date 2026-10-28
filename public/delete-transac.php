<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: gestion.php");
    exit;
}

$transaction_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $conn = new PDO($dsn, $username, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get transaction details and verify ownership
    $stmt = $conn->prepare("SELECT amount, type FROM transactions WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $transaction_id, 'user_id' => $user_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        $amount = $transaction['amount'];

        // Start transaction
        $conn->beginTransaction();

        // Delete the transaction
        $deleteStmt = $conn->prepare("DELETE FROM transactions WHERE id = :id");
        $deleteStmt->execute(['id' => $transaction_id]);

        // Annule l'effet de la transaction sur le solde
        $updateStmt = $conn->prepare("UPDATE users SET current_budget = current_budget - :effect WHERE id = :user_id");
        $updateStmt->execute(['effect' => balance_effect($transaction['type'], $amount), 'user_id' => $user_id]);

        $conn->commit();
        flash($transaction['type'] === 'income'
            ? "Revenu supprimé, " . euro($amount) . " retirés de ton solde."
            : "Dépense supprimée, " . euro($amount) . " recrédités sur ton solde.");
    }
} catch (PDOException $e) {
    flash("Impossible de supprimer cette transaction.", 'error');
}

header("Location: gestion.php");
exit;
