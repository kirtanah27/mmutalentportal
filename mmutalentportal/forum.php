<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    echo "<script>alert('Please login first'); window.location='login.php';</script>";
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];
$msg = "";

// Handle new thread submission
if (isset($_POST['create_thread'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    if ($title && $content) {
        $stmt = $pdo->prepare("INSERT INTO forum_threads (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $content]);
        $msg = "✅ Thread created successfully.";
    }
}

// Handle reply submission
if (isset($_POST['reply_submit'])) {
    $thread_id = $_POST['thread_id'];
    $reply_content = trim($_POST['reply_content']);
    if ($reply_content) {
        $stmt = $pdo->prepare("INSERT INTO forum_replies (thread_id, user_id, reply_content) VALUES (?, ?, ?)");
        $stmt->execute([$thread_id, $user_id, $reply_content]);
        $msg = "Reply posted.";
    }
}

// Fetch all threads with username
$threads = $pdo->query("SELECT ft.*, u.username FROM forum_threads ft JOIN users u ON ft.user_id = u.user_id ORDER BY ft.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Forum Discussions</h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <!-- Create New Thread Toggle -->
    <button class="btn gold" type="button" onclick="toggleThreadForm()">New Thread</button>
    <div id="threadForm" style="display: none; margin-top: 20px;">
        <form method="POST" style="margin-bottom: 40px;">
            <label>Thread Title</label>
            <input type="text" name="title" required>
            <label>Content</label>
            <textarea name="content" rows="4" required></textarea>
            <button class="btn gold" type="submit" name="create_thread">Post Thread</button>
        </form>
    </div>

    <script>
        function toggleThreadForm() {
            const form = document.getElementById("threadForm");
            form.style.display = form.style.display === "none" ? "block" : "none";
        }
    </script>

    <!-- Display All Threads and Replies -->
    <?php foreach ($threads as $thread): ?>
        <div class="forum-thread">
            <div class="thread-content">
                <h4><?= htmlspecialchars($thread['title']) ?></h4>
                <p><?= nl2br(htmlspecialchars($thread['content'])) ?></p>
                <div class="meta">Posted by <a href="profile.php?user_id=<?= $thread['user_id'] ?>" style="color: #f4c95d; text-decoration: none;"><?= htmlspecialchars($thread['username']) ?></a> on <?= date("F j, Y, g:i a", strtotime($thread['created_at'])) ?></div>

                <!-- Replies -->
                <strong>Replies:</strong>
                <?php
                $stmt = $pdo->prepare("
                    SELECT fr.*, u.username 
                    FROM forum_replies fr 
                    JOIN users u ON fr.user_id = u.user_id 
                    WHERE fr.thread_id = ? 
                    ORDER BY fr.replied_at ASC
                ");
                $stmt->execute([$thread['thread_id']]);
                $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (count($replies) > 0): ?>
                    <?php foreach ($replies as $reply): ?>
                        <div class="reply">
                            <?= nl2br(htmlspecialchars($reply['reply_content'])) ?>
                            <div class="meta">— <a href="profile.php?user_id=<?= $reply['user_id'] ?>" style="color: #f4c95d; text-decoration: none;"><?= htmlspecialchars($reply['username']) ?></a> at <?= date("F j, Y, g:i a", strtotime($reply['replied_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #888;">No replies yet.</p>
                <?php endif; ?>

                <!-- Reply Form -->
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="thread_id" value="<?= $thread['thread_id'] ?>">
                    <textarea name="reply_content" placeholder="Write a reply..." rows="2" required style="width: 100%; padding: 10px; background-color: #2c2c2c; color: #fff; border-radius: 8px; border: none;"></textarea>
                    <button type="submit" name="reply_submit" class="btn outline">Reply</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
