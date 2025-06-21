<?php

session_start();
require_once 'includes/db.php';

// Redirect if not admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

$admin_id = $_SESSION['user']['user_id'];
$msg = "";

// Create News
if (isset($_POST['create'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO news (title, content, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $admin_id]);
        $msg = "✅ News posted successfully.";
    } else {
        $msg = "❗ Title and content are required.";
    }
}

// Delete News
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // Allow any admin to delete the news item
    $stmt = $pdo->prepare("DELETE FROM news WHERE news_id = ?");
    $stmt->execute([$delete_id]);
    $msg = "News deleted.";
}

// Update News
if (isset($_POST['update'])) {
    $news_id = (int)$_POST['news_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title && $content) {
        // Allow any admin to update the news item
        $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE news_id = ?");
        $stmt->execute([$title, $content, $news_id]);
        $msg = "News updated.";
    } else {
        $msg = "❗ Title and content are required.";
    }
}

// Fetch ALL news posts for admin view
$news = $pdo->query("SELECT n.*, u.username AS created_by_username FROM news n JOIN users u ON n.created_by = u.user_id ORDER BY n.created_at DESC");
$news_list = $news->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Post / Manage News</h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <!-- Create Form -->
    <h3 style="color: #f4c95d; margin-bottom: 15px;">Create New News Post</h3>
    <form action="news_manage.php" method="POST" style="margin-bottom: 30px;">
        <label for="title">Title</label>
        <input type="text" name="title" placeholder="News title" required>

        <label for="content">Content</label>
        <textarea name="content" rows="5" placeholder="News content" required></textarea>

        <button type="submit" name="create" class="btn gold">Post News</button>
    </form>

    <!-- Manage Existing Posts -->
    <h3 style="color: #f4c95d; margin-bottom: 15px;">All News Posts</h3>
    <?php if (count($news_list) > 0): ?>
        <?php foreach ($news_list as $item): ?>
            <div class="admin-news-card">
                <p style="font-size: 0.9em; color: #888; margin-bottom: 5px;">Posted by: <strong><?= htmlspecialchars($item['created_by_username']) ?></strong> on <?= date("F j, Y, g:i a", strtotime($item['created_at'])) ?></p>
                <form action="news_manage.php" method="POST">
                    <input type="hidden" name="news_id" value="<?= $item['news_id'] ?>">

                    <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
                    <textarea name="content" rows="4" required><?= htmlspecialchars($item['content']) ?></textarea>

                    <button type="submit" name="update" class="btn gold">Update</button>
                    <a href="news_manage.php?delete=<?= $item['news_id'] ?>" class="btn outline" onclick="return confirm('Are you sure you want to delete this news item?')">Delete</a>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No news posted yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
