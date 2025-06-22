<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_resolved'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        try {
            $stmt = $pdo->prepare("UPDATE feedback SET status = 'resolved' WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $msg = "Feedback marked as resolved.";
        } catch (PDOException $e) {
            $msg = "Error marking as resolved: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_feedback'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $msg = "Feedback deleted.";
        } catch (PDOException $e) {
            $msg = "Error deleting feedback: " . $e->getMessage();
        }
    }
    header("Location: feedback_manage.php?msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

$feedbacks = [];
try {
    $stmt = $pdo->query("SELECT f.*, u.username FROM feedback f JOIN users u ON f.user_id = u.user_id ORDER BY FIELD(f.status, 'pending', 'resolved'), f.submitted_at DESC");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $msg = "Error retrieving feedback: " . $e->getMessage();
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="feedback_export_' . date('Ymd_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Feedback ID', 'Username', 'Message', 'Submitted At', 'Status']);

    $stmt = $pdo->query("
        SELECT 
            f.feedback_id,
            u.username,
            f.message,
            f.submitted_at,
            f.status
        FROM 
            feedback f 
        JOIN 
            users u ON f.user_id = u.user_id 
        ORDER BY 
            FIELD(f.status, 'pending', 'resolved'), f.submitted_at DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 1000px;">
    <h2>Manage User Feedbacks</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (count($feedbacks) > 0): ?>
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="feedback_manage.php?export=csv" class="btn gold">📥 Export as CSV</a>
        </div>

        <div class="feedback-list" style="margin-top: 20px;">
            <?php foreach ($feedbacks as $fb): ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px; border-left: 5px solid <?= $fb['status'] === 'pending' ? '#f4c95d' : '#4CAF50' ?>;">
                    <p style="font-size: 1.1rem; color: #ddd; margin-bottom: 10px;"><?= nl2br(htmlspecialchars($fb['message'])) ?></p>
                    <div class="meta" style="font-size: 0.85rem; color: #888;">
                        From <a href="profile.php?user_id=<?= htmlspecialchars($fb['user_id']) ?>" style="color: #f4c95d; text-decoration: none;"><strong><?= htmlspecialchars($fb['username']) ?></strong></a>
                        on <?= date("F j, Y, g:i a", strtotime($fb['submitted_at'])) ?>
                        <span style="float: right;">Status: <strong><?= htmlspecialchars(ucfirst($fb['status'])) ?></strong></span>
                    </div>

                    <form method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="feedback_id" value="<?= htmlspecialchars($fb['feedback_id']) ?>">
                        <?php if ($fb['status'] === 'pending'): ?>
                            <button type="submit" name="mark_resolved" class="btn gold" style="margin-right: 10px;">Mark as Resolved</button>
                        <?php endif; ?>
                        <button type="submit" name="delete_feedback" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this feedback?');">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No user feedbacks found yet.</p>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
