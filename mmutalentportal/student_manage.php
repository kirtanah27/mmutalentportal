<?php
// PHP Section: Setup and Logic

// 1. Start the session to manage user login status.
session_start();
// 2. Include the database connection file.
require_once 'includes/db.php';

// 3. Check if the user is logged in and has 'admin' role. If not, deny access.
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo "<script>alert('Access denied. Admins only.'); window.location='index.php';</script>";
    exit();
}

// 4. Initialize message variable for user feedback.
$msg = "";

// 5. Handle user deletion if requested by admin.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_to_delete_id = (int)$_POST['user_id'];

    // Prevent admin from deleting their own account (optional, but good practice).
    if ($user_to_delete_id === $_SESSION['user']['user_id']) {
        $msg = "❌ You cannot delete your own account.";
    } else {
        try {
            // Prepare and execute SQL query to delete the user.
            // ON DELETE CASCADE in your schema should handle related data (talents, etc.).
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_to_delete_id]);
            $msg = "✅ User account deleted successfully.";
        } catch (PDOException $e) {
            $msg = "❌ Error deleting user: " . $e->getMessage();
        }
    }
    // Redirect to the same page after POST to prevent re-submission and display message.
    header("Location: student_manage.php?msg=" . urlencode($msg));
    exit;
}

// 6. Fetch message from URL query string if redirected (GET request).
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// 7. Determine the selected role filter.
$filter_role = $_GET['role'] ?? 'all'; // Default to 'all' if no filter is set

// 8. Fetch all registered users based on the selected role filter.
$users = []; // Initialize an empty array for users.
try {
    $sql = "SELECT user_id, username, email, role, is_public FROM users WHERE 1"; // Base query

    $params = [];
    if ($filter_role !== 'all') {
        $sql .= " AND role = ?";
        $params[] = $filter_role;
    }

    $sql .= " ORDER BY role, username ASC"; // Order by role and then username

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as associative array.

} catch (PDOException $e) {
    // Display database error message if fetching users fails.
    $msg = "Error retrieving user accounts: " . $e->getMessage();
}

// HTML Section: Page Structure and Display

// Include the standard header.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <!-- Page Title -->
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Manage User Accounts</h2>

    <?php
    // Display any message (success or error).
    if ($msg):
    ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <!-- Filter Form -->
    <form method="GET" action="student_manage.php" style="margin-bottom: 30px; text-align: right;">
        <label for="role_filter" style="color: #f1f1f1; margin-right: 10px;">Filter by Role:</label>
        <select id="role_filter" name="role" onchange="this.form.submit()" style="padding: 8px; border-radius: 5px; background-color: #2c2c2c; color: #f1f1f1; border: none;">
            <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>All Roles</option>
            <option value="talent" <?= $filter_role === 'talent' ? 'selected' : '' ?>>Talent</option>
            <option value="buyer" <?= $filter_role === 'buyer' ? 'selected' : '' ?>>Buyer</option>
            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
    </form>

    <?php
    // Check if there are any users to display.
    if (count($users) > 0):
    ?>
        <div class="user-list" style="margin-top: 20px;">
            <?php
            // Loop through each user and display their details.
            foreach ($users as $user):
            ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;"><?= htmlspecialchars($user['username']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user['role'])) ?></p>
                    <p style="color: #ddd; margin-bottom: 10px;"><strong>Profile:</strong> <?= $user['is_public'] ? 'Public' : 'Private' ?></p>

                    <div style="margin-top: 10px;">
                        <a href="profile.php?user_id=<?= htmlspecialchars($user['user_id']) ?>" class="btn outline" style="margin-right: 10px;">View Profile</a>
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                            <?php if ($user['user_id'] !== $_SESSION['user']['user_id']): // Prevent admin from deleting themselves ?>
                                <button type="submit" name="delete_user" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</button>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.9em;">(Cannot delete your own account)</span>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    // Display a message if no users are found.
    else:
    ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No user accounts found based on the current filter.</p>
    <?php endif; ?>
</div>

<?php
// Include the standard footer for the page.
include 'includes/footer.php';
?>
