<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Admins only'); window.location='index.php';</script>";
    exit();
}

$admin_id = $_SESSION['user']['user_id'];
?>

<?php include 'includes/header.php'; ?>

<div class="auth-box">
    <h2>Admin FAQ Management</h2>

    <!-- Post New FAQ -->
    <div style="margin-bottom: 40px;">
        <h3 style="color: #f4c95d;">Post New FAQ</h3>
        <form method="POST">
            <textarea name="new_question" rows="2" placeholder="FAQ question..." required></textarea>
            <textarea name="new_answer" rows="3" placeholder="FAQ answer..." required></textarea>
            <button type="submit" name="post_faq" class="btn gold">Publish FAQ</button>
        </form>
    </div>

    <!-- Answer Pending -->
    <div>
        <h3 style="color: #f4c95d;">Pending Questions</h3>
        <?php
        $stmt = $pdo->query("SELECT s.*, u.username FROM faqsubmission s JOIN users u ON s.user_id = u.user_id WHERE s.status = 'pending' ORDER BY submitted_at DESC");
        $submissions = $stmt->fetchAll();

        if ($submissions):
            foreach ($submissions as $s):
        ?>
        <div class="faq-item" style="margin-top: 20px; background:#2c2c2c; padding:20px; border-radius:10px;">
            <p><strong>User:</strong> <?= htmlspecialchars($s['username']) ?> |
               <strong>Submitted:</strong> <?= $s['submitted_at'] ?></p>
            <p><strong>Question:</strong> <?= htmlspecialchars($s['question']) ?></p>
            <form method="POST">
                <input type="hidden" name="submission_id" value="<?= $s['submission_id'] ?>">
                <input type="hidden" name="question" value="<?= htmlspecialchars($s['question']) ?>">
                <textarea name="answer" rows="3" placeholder="Type your answer..." required></textarea>
                <button type="submit" name="submit_answer" class="btn gold">Submit Answer</button>
                <button type="submit" name="delete_submission" class="btn outline" style="margin-left: 10px;" onclick="return confirm('Delete this question?');">Delete</button>
            </form>
        </div>
        <?php
            endforeach;
        else:
            echo "<p>No pending questions.</p>";
        endif;
        ?>
    </div>

    <!-- View Answered -->
    <div style="margin-top: 50px;">
        <h3 style="color: #f4c95d;">Answered Submissions</h3>
        <?php
        $answered = $pdo->query("
            SELECT s.question AS submitted_question, s.submitted_at, u.username, f.answer 
            FROM faqsubmission s
            JOIN users u ON s.user_id = u.user_id
            JOIN faq f ON f.question = s.question
            WHERE s.status = 'answered'
            ORDER BY s.submitted_at DESC
        ")->fetchAll();

        if ($answered):
            foreach ($answered as $a):
        ?>
        <div class="faq-item" style="margin-top: 20px; background:#1a1a1a; padding:20px; border-radius:10px;">
            <p><strong>User:</strong> <?= htmlspecialchars($a['username']) ?> |
               <strong>Submitted:</strong> <?= $a['submitted_at'] ?></p>
            <p><strong>Q:</strong> <?= htmlspecialchars($a['submitted_question']) ?></p>
            <p><strong>A:</strong> <?= htmlspecialchars($a['answer']) ?></p>
        </div>
        <?php
            endforeach;
        else:
            echo "<p>No answered questions yet.</p>";
        endif;
        ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- PHP Actions -->
<?php
// New FAQ
if (isset($_POST['post_faq'])) {
    $q = trim($_POST['new_question']);
    $a = trim($_POST['new_answer']);

    if ($q && $a) {
        $stmt = $pdo->prepare("INSERT INTO faq (question, answer, created_by_admin_id) VALUES (?, ?, ?)");
        $stmt->execute([$q, $a, $admin_id]);
        echo "<script>alert('✅ FAQ posted.'); window.location='faq_manage.php';</script>";
    }
}

// Answer pending
if (isset($_POST['submit_answer'])) {
    $sid = (int)$_POST['submission_id'];
    $q = trim($_POST['question']);
    $a = trim($_POST['answer']);

    if ($q && $a) {
        $pdo->prepare("INSERT INTO faq (question, answer, created_by_admin_id) VALUES (?, ?, ?)")
            ->execute([$q, $a, $admin_id]);

        $pdo->prepare("UPDATE faqsubmission SET status = 'answered' WHERE submission_id = ?")
            ->execute([$sid]);

        echo "<script>alert('✅ Answer submitted.'); window.location='faq_manage.php';</script>";
    }
}

// Delete pending
if (isset($_POST['delete_submission'])) {
    $sid = (int)$_POST['submission_id'];
    $pdo->prepare("DELETE FROM faqsubmission WHERE submission_id = ?")->execute([$sid]);
    echo "<script>alert('🗑 Question deleted.'); window.location='faq_manage.php';</script>";
}
?>
