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

// 7. Fetch all registered users (excluding admins, unless desired).
//    Ordered by role and then username for better readability.
$users = []; // Initialize an empty array for users.
try {
    // Select all users, excluding admins from this list if preferred, or include them.
    // For this implementation, we will show all non-admin users (talents and buyers).
    $stmt = $pdo->prepare("SELECT user_id, username, email, role, is_public FROM users WHERE role IN ('buyer', 'talent') ORDER BY role, username ASC");
    $stmt->execute();
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
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Manage Student Accounts</h2>

    <?php
    // Display any message (success or error).
    if ($msg):
    ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

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
                            <button type="submit" name="delete_user" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    // Display a message if no users are found.
    else:
    ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No student accounts found.</p>
    <?php endif; ?>
</div>

<?php
// Include the standard footer for the page.
include 'includes/footer.php';
?>
