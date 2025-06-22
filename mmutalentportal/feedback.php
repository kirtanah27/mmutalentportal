<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user']['user_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);

    if ($message) {
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->execute([$user_id, $message]);
        $msg = "Thank you for your feedback!";
    } else {
        $msg = "Please enter your message.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$user_id]);
$user_feedbacks = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Send Us Some Feedback!</h2>
    <?php if ($msg): ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <form method="POST" style="margin-bottom: 30px;">
        <label for="message">Your Message:</label>
        <textarea name="message" rows="4" placeholder="Share your thoughts..." required></textarea>
        <button type="submit" class="btn gold">Send Feedback</button>
    </form>

    <?php if (count($user_feedbacks) > 0): ?>
        <h3 style="margin-bottom: 10px;">Your Previous Feedback</h3>
        <?php foreach ($user_feedbacks as $feedback): ?>
            <div class="news-card">
                <p><?= nl2br(htmlspecialchars($feedback['message'])) ?></p>
                <div class="meta">Submitted on <?= date("F j, Y, g:i a", strtotime($feedback['submitted_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
