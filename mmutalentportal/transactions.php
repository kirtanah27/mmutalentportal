<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$msg = "";

$transactions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $msg = "Error retrieving past transactions: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2>Your Past Transactions</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (count($transactions) > 0): ?>
        <div class="transaction-list" style="margin-top: 20px;">
            <?php foreach ($transactions as $transaction): ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;">Transaction ID: <?= htmlspecialchars($transaction['transaction_id']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Amount:</strong> RM <?= number_format($transaction['amount'], 2) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($transaction['date'])) ?></p>
                    <p style="color: #ddd;"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($transaction['status'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">You have no past transactions.</p>
        <div style="text-align: center; margin-top: 30px;">
            <a href="catalogue.php" class="btn outline">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
