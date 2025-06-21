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
    $pdo->prepare("DELETE FROM news WHERE news_id = ?")->execute([$delete_id]);
    $msg = "News deleted.";
}

// Update News
if (isset($_POST['update'])) {
    $news_id = (int)$_POST['news_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if ($title && $content) {
        $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE news_id = ? AND created_by = ?");
        $stmt->execute([$title, $content, $news_id, $admin_id]);
        $msg = "News updated.";
    } else {
        $msg = "❗ Title and content are required.";
    }
}

// Fetch all admin's posts
$news = $pdo->prepare("SELECT * FROM news WHERE created_by = ? ORDER BY created_at DESC");
$news->execute([$admin_id]);
$news_list = $news->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Post / Manage News</h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <!-- Create Form -->
    <form action="news_manage.php" method="POST" style="margin-bottom: 30px;">
        <label for="title">Title</label>
        <input type="text" name="title" required>

        <label for="content">Content</label>
        <textarea name="content" rows="5" required></textarea>

        <button type="submit" name="create" class="btn gold">Post News</button>
    </form>

    <!-- Manage Existing Posts -->
    <?php if (count($news_list) > 0): ?>
        <?php foreach ($news_list as $item): ?>
            <div class="admin-news-card">
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
