<?php

session_start();
require_once 'includes/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

// Optional: restrict admin from viewing this page
if ($_SESSION['user']['role'] === 'admin') {
    header("Location: news_manage.php");
    exit();
}

$newsList = $pdo->query("SELECT n.*, u.username AS admin_name FROM news n JOIN users u ON n.created_by = u.user_id ORDER BY n.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="news-container">
  <h2 style="color: #f4c95d; font-size: 2rem; margin-bottom: 30px;">News & Announcements</h2>

  <?php foreach ($newsList as $item): ?>
    <div class="news-card">
      <h4><?= htmlspecialchars($item['title']) ?></h4>
      <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
      <div class="meta">
        Posted by <?= htmlspecialchars($item['admin_name']) ?> on <?= date("F j, Y, g:i a", strtotime($item['created_at'])) ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
