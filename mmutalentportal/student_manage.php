<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_to_delete_id = (int)$_POST['user_id'];

    if ($user_to_delete_id === $_SESSION['user']['user_id']) {
        $msg = "You cannot delete your own account.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_to_delete_id]);
            $msg = "User account deleted successfully.";
        } catch (PDOException $e) {
            $msg = "Error deleting user: " . $e->getMessage();
        }
    }
    header("Location: student_manage.php?msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

$filter_role = $_GET['role'] ?? 'all';

$users = [];
try {
    $sql = "SELECT user_id, username, email, role, is_public FROM users WHERE 1";

    $params = [];
    if ($filter_role !== 'all') {
        $sql .= " AND role = ?";
        $params[] = $filter_role;
    }

    $sql .= " ORDER BY role, username ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $msg = "Error retrieving user accounts: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2>Manage User Accounts</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="GET" action="student_manage.php" style="margin-bottom: 30px; text-align: right;">
        <label for="role_filter" style="color: #f1f1f1; margin-right: 10px;">Filter by Role:</label>
        <select id="role_filter" name="role" onchange="this.form.submit()" style="padding: 8px; border-radius: 5px; background-color: #2c2c2c; color: #f1f1f1; border: none;">
            <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>All Roles</option>
            <option value="talent" <?= $filter_role === 'talent' ? 'selected' : '' ?>>Talent</option>
            <option value="buyer" <?= $filter_role === 'buyer' ? 'selected' : '' ?>>Buyer</option>
            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </form>

    <?php if (count($users) > 0): ?>
        <div class="user-list" style="margin-top: 20px;">
            <?php foreach ($users as $user): ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;"><?= htmlspecialchars($user['username']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                    <p style="color: #ddd; margin-bottom: 10px;"><strong>Profile:</strong> <?= $user['is_public'] ? 'Public' : 'Private' ?></p>

                    <div style="margin-top: 10px;">
                        <a href="profile.php?user_id=<?= htmlspecialchars($user['user_id']) ?>" class="btn outline" style="margin-right: 10px;">View Profile</a>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                            <?php if ($user['user_id'] !== $_SESSION['user']['user_id']): ?>
                                <button type="submit" name="delete_user" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</button>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.9em;">(Cannot delete your own account)</span>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No user accounts found based on the current filter.</p>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
