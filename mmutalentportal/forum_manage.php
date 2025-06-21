<?php
session_start();
require_once 'includes/db.php';

// Ensure only admins can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

// Handle thread deletion
if (isset($_GET['delete_thread'])) {
    $id = (int)$_GET['delete_thread'];
    $pdo->prepare("DELETE FROM forum_threads WHERE thread_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM forum_replies WHERE thread_id = ?")->execute([$id]);
    $msg = "✅ Thread deleted.";
}

// Handle reply deletion
if (isset($_GET['delete_reply'])) {
    $id = (int)$_GET['delete_reply'];
    $pdo->prepare("DELETE FROM forum_replies WHERE reply_id = ?")->execute([$id]);
    $msg = "✅ Reply deleted.";
}

// Fetch all threads
$threads = $pdo->query("
    SELECT ft.*, u.username 
    FROM forum_threads ft 
    JOIN users u ON ft.user_id = u.user_id 
    ORDER BY ft.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Manage Forum Threads</h2>
    <?php if (isset($msg)): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <?php foreach ($threads as $thread): ?>
        <div class="forum-thread">
            <div class="thread-content">
                <h4><?= htmlspecialchars($thread['title']) ?></h4>
                <p><?= nl2br(htmlspecialchars($thread['content'])) ?></p>
                <div class="meta">
                    Posted by <a href="profile.php?user_id=<?= $thread['user_id'] ?>" style="color:#f4c95d"><?= htmlspecialchars($thread['username']) ?></a> on <?= date("F j, Y, g:i a", strtotime($thread['created_at'])) ?>
                </div>
                <a href="forum_manage.php?delete_thread=<?= $thread['thread_id'] ?>" class="btn outline" onclick="return confirm('Delete this thread and all its replies?')">Delete Thread</a>

                <!-- Fetch Replies -->
                <div style="margin-top: 15px;">
                    <strong>Replies:</strong>
                    <?php
                    $stmt = $pdo->prepare("SELECT fr.*, u.username FROM forum_replies fr JOIN users u ON fr.user_id = u.user_id WHERE thread_id = ? ORDER BY replied_at ASC");
                    $stmt->execute([$thread['thread_id']]);
                    $replies = $stmt->fetchAll();
                    ?>
                    <?php if (count($replies) > 0): ?>
                        <?php foreach ($replies as $reply): ?>
                            <div class="reply">
                                <?= nl2br(htmlspecialchars($reply['reply_content'])) ?>
                                <div class="meta">— <a href="profile.php?user_id=<?= $reply['user_id'] ?>" style="color: #f4c95d;"><?= htmlspecialchars($reply['username']) ?></a> at <?= date("F j, Y, g:i a", strtotime($reply['replied_at'])) ?></div>
                                <a href="forum_manage.php?delete_reply=<?= $reply['reply_id'] ?>" class="btn outline" style="margin-top: 5px;" onclick="return confirm('Delete this reply?')">Delete Reply</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #777;">No replies yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
