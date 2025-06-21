<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?")->execute([$user_id]);
?>

<?php include 'includes/header.php'; ?>
<div class="container auth-box" style="max-width: 800px; padding: 40px;">
    <h2 style="color: #f4c95d;">🔔 Notifications</h2>

    <?php if (count($notifications) > 0): ?>
        <div class="notification-list" style="margin-top: 20px;">
        <?php foreach ($notifications as $notif): ?>
    <div class="notification-card">
        <?php if (isset($notif['type']) && $notif['type'] === 'talent_approved'): ?>
            <p><strong>✅ Your talent was approved!</strong></p>
            <p><strong>Title:</strong> <?= htmlspecialchars($notif['title']) ?></p>
            <p>📅 <strong>Date:</strong> <?= htmlspecialchars($notif['created_at']) ?></p>
        <?php elseif (isset($notif['type']) && $notif['type'] === 'talent_purchased'): ?>
            <p><strong>🛒 Your talent was purchased!</strong></p>
            <p><strong>Title:</strong> <?= htmlspecialchars($notif['title']) ?></p>
            <p>📅 <strong>Date:</strong> <?= htmlspecialchars($notif['created_at']) ?></p>
            <?php else: ?>
            <p><strong>🔔 New notification:</strong></p>
            <p><?= isset($notif['title']) ? htmlspecialchars($notif['title']) : 'No title provided.' ?></p>
            <p>📅 <strong>Date:</strong> <?= htmlspecialchars($notif['created_at']) ?></p>
        <?php endif; ?>

    </div>
<?php endforeach; ?>

        </div>
    <?php else: ?>
        <p style="color: #ccc; margin-top: 20px;">You have no notifications.</p>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
