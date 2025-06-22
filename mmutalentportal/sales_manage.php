<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

$msg = "";

$sales = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            t.*,
            u.username AS buyer_username
        FROM
            transactions t
        JOIN
            users u ON t.user_id = u.user_id
        ORDER BY
            t.date DESC
    ");
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $msg = "Error retrieving sales data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 1000px;">
    <h2>All Sales Transactions</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (count($sales) > 0): ?>
        <div class="sales-list" style="margin-top: 20px;">
            <?php foreach ($sales as $sale): ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;">Transaction ID: <?= htmlspecialchars($sale['transaction_id']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Bought By:</strong>
                        <a href="profile.php?user_id=<?= htmlspecialchars($sale['user_id']) ?>" style="color: #f4c95d; text-decoration: none;">
                            <?= htmlspecialchars($sale['buyer_username']) ?>
                        </a>
                    </p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Amount:</strong> RM <?= number_format($sale['amount'], 2) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($sale['date'])) ?></p>
                    <p style="color: #ddd;"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($sale['status'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No sales transactions found yet.</p>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
