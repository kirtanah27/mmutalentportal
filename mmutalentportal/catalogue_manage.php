<?php

ob_start(); // Start output buffering at the very beginning of the script
ini_set('display_errors', 1); // For debugging: Display all errors
ini_set('display_startup_errors', 1); // For debugging: Display startup errors
error_reporting(E_ALL); // For debugging: Report all PHP errors

session_start();
require_once 'includes/db.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    // Using JavaScript for redirection as a fallback if headers already sent,
    // though ob_start() should prevent this.
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

$msg = ""; // Initialize message variable

// Handle POST actions for approving or deleting talents
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_id'])) {
        $approve_id = (int)$_POST['approve_id'];

        // Prepare and execute the SQL statement to approve the talent
        $stmt = $pdo->prepare("UPDATE talents SET is_approved = TRUE WHERE talent_id = ?");
        $stmt->execute([$approve_id]);

        // Fetch talent information to send a notification
        $stmtUser = $pdo->prepare("SELECT user_id, title FROM talents WHERE talent_id = ?");
        $stmtUser->execute([$approve_id]);
        $talentInfo = $stmtUser->fetch();

        // If talent info is found, insert a notification for the talent owner
        if ($talentInfo) {
            $notify = $pdo->prepare("INSERT INTO notifications (user_id, title, type, created_at, is_read) VALUES (?, ?, ?, NOW(), 0)");
            $notify->execute([
                $talentInfo['user_id'],
                $talentInfo['title'],
                'talent_approved'
            ]);
        }

        $msg = "✅ Talent approved successfully.";
    } elseif (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        // Prepare and execute the SQL statement to delete the talent
        $stmt = $pdo->prepare("DELETE FROM talents WHERE talent_id = ?");
        $stmt->execute([$delete_id]);
        $msg = "✅ Talent removed successfully.";
    }

    // Redirect back to the catalogue_manage.php page with a message
    // ob_end_clean() clears the buffer and prevents any buffered output from being sent before the header.
    ob_end_clean(); // Clear the output buffer before sending header
    header("Location: catalogue_manage.php?msg=" . urlencode($msg));
    exit; // Terminate script execution after redirection
}

// Handle messages passed via GET request (e.g., after a redirect)
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// Determine filtering status for talents (all, pending, or approved)
$filter_status = $_GET['status'] ?? 'all';
$sql = "SELECT t.*, u.username, u.user_id FROM talents t JOIN users u ON t.user_id = u.user_id WHERE 1";

// Apply status filter to the SQL query
if ($filter_status === 'pending') {
    $sql .= " AND t.is_approved = FALSE";
} elseif ($filter_status === 'approved') {
    $sql .= " AND t.is_approved = TRUE";
}
$sql .= " ORDER BY t.created_at DESC"; // Order results by creation date

// Prepare and execute the query to fetch talents
$stmt = $pdo->prepare($sql);
$stmt->execute();
$talents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the header file (ensure no accidental output before this in header.php)
include 'includes/header.php';
?>

<div class="auth-box" style="max-width: 1600px;">
    <h2>Manage E-Catalogue</h2>
    <?php if (!empty($msg)): ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <!-- Form for filtering talents by status -->
    <form method="GET" action="catalogue_manage.php" style="margin-bottom: 30px; text-align: right;">
        <label for="status_filter" style="color: #f1f1f1; margin-right: 10px;">Filter by Status:</label>
        <select id="status_filter" name="status" onchange="this.form.submit()" style="padding: 8px; border-radius: 5px; background-color: #2c2c2c; color: #f1f1f1; border: none;">
            <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Talents</option>
            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
            <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved Talents</option>
        </select>
    </form>

    <?php if (count($talents) === 0): ?>
        <p>No talents found based on the current filter.</p>
    <?php else: ?>
        <div style="margin-top: 40px; display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
            <?php foreach ($talents as $talent): ?>
                <?php $border_color = $talent['is_approved'] ? '#4CAF50' : '#f4c95d'; // Green for approved, gold for pending ?>
                <div class="feature-card" style="width: 300px; border: 2px solid <?= $border_color ?>; box-shadow: 0 0 10px <?= $border_color ?>; position: relative;">
                    <h3 style="color: #f4c95d;"><?= htmlspecialchars($talent['title']) ?></h3>
                    <?php if (!empty($talent['tagline'])): ?>
                        <p style="font-style: italic; color: #aaa;"><?= htmlspecialchars($talent['tagline']) ?></p>
                    <?php endif; ?>
                    <p><strong>By:</strong>
                        <a href="profile.php?user_id=<?= htmlspecialchars($talent['user_id']) ?>" style="color: #f4c95d;">
                            <?= htmlspecialchars($talent['username']) ?>
                        </a>
                    </p>
                    <p style="font-size: 0.9em; color: <?= $border_color ?>;">Status: <strong><?= $talent['is_approved'] ? 'Approved' : 'Pending' ?></strong></p>

                    <?php if (!empty($talent['media_path'])): ?>
                        <div style="margin-bottom: 10px;">
                            <?php
                                // Display appropriate icon or image based on media file type
                                $ext = strtolower(pathinfo($talent['media_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    echo '<img src="' . htmlspecialchars($talent['media_path']) . '" alt="preview" style="width: 100%; border-radius: 8px;">';
                                } elseif (in_array($ext, ['mp4', 'webm'])) {
                                    echo '<img src="assets/icons/video.png" alt="Video File" style="width: 100px;">';
                                } elseif (in_array($ext, ['mp3', 'wav'])) {
                                    echo '<img src="assets/icons/audio.png" alt="Audio File" style="width: 100px;">';
                                } elseif ($ext === 'pdf') {
                                    echo '<img src="assets/icons/pdf.png" alt="PDF File" style="width: 100px;">';
                                } else {
                                    echo '<img src="assets/icons/file.png" alt="File" style="width: 100px;">';
                                }
                            ?>
                        </div>
                    <?php endif; ?>

                    <p><?= nl2br(htmlspecialchars($talent['description'])) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($talent['category']) ?></p>
                    <p><strong>Price:</strong> RM <?= number_format($talent['price'], 2) ?></p>
                    <p><strong>Delivery Time:</strong> <?= htmlspecialchars($talent['delivery_time']) ?> day(s)</p>

                    <?php if (!empty($talent['tags'])): ?>
                        <p><strong>Tags:</strong>
                            <?php foreach (explode(',', $talent['tags']) as $tag): ?>
                                <span style="background:#444; padding:3px 8px; border-radius:6px; margin-right:5px;">
                                    <?= htmlspecialchars(trim($tag)) ?>
                                </span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!$talent['is_approved']): ?>
                        <!-- Form to approve talent -->
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="approve_id" value="<?= htmlspecialchars($talent['talent_id']) ?>">
                            <button type="submit" class="btn gold" style="width: 100%; margin-bottom: 10px;">Approve Talent</button>
                        </form>
                    <?php endif; ?>

                    <!-- Form to remove/delete talent -->
                    <form method="POST">
                        <input type="hidden" name="delete_id" value="<?= htmlspecialchars($talent['talent_id']) ?>">
                        <button type="submit" class="btn outline" style="width: 100%;" onclick="return confirm('Are you sure you want to remove this talent?');">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
