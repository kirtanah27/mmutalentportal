<?php
ob_start(); // Start output buffering
session_start();
require_once 'includes/db.php'; // Include your database connection

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    ob_end_clean(); // Clear buffer before redirect
    header("Location: index.php");
    exit();
}

$current_admin_id = $_SESSION['user']['user_id'];
$msg = ""; // Initialize message variable

// Handle POST requests for creating, updating, or deleting an admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_admin'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if ($username && $email && $password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$username, $email, $hashed_password]);
                $msg = "✅ New admin account created successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $msg = "❌ Username or email already exists for an admin. Please choose a different one.";
                } else {
                    $msg = "❌ Database error: " . $e->getMessage();
                }
            }
        } else {
            $msg = "❗ Please fill in all fields for the new admin account.";
        }
    } elseif (isset($_POST['update_admin'])) {
        $admin_id_to_update = (int)$_POST['admin_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password']; // New password, optional

        if ($username && $email) {
            $sql = "UPDATE users SET username = ?, email = ? WHERE user_id = ? AND role = 'admin'";
            $params = [$username, $email, $admin_id_to_update];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ? AND role = 'admin'";
                $params = [$username, $email, $hashed_password, $admin_id_to_update];
            }

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $msg = "✅ Admin account updated successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $msg = "❌ Username or email already exists for another admin.";
                } else {
                    $msg = "❌ Error updating admin: " . $e->getMessage();
                }
            }
        } else {
            $msg = "❗ Username and Email are required for update.";
        }
    } elseif (isset($_POST['delete_admin'])) {
        $admin_id_to_delete = (int)$_POST['admin_id'];

        if ($admin_id_to_delete === $current_admin_id) {
            $msg = "❌ You cannot delete your own admin account.";
        } else {
            try {
                // Ensure the user being deleted is indeed an admin
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
                $stmt->execute([$admin_id_to_delete]);
                if ($stmt->rowCount() > 0) {
                    $msg = "🗑️ Admin account deleted successfully.";
                } else {
                    $msg = "❌ Admin account not found or could not be deleted.";
                }
            } catch (PDOException $e) {
                $msg = "❌ Error deleting admin: " . $e->getMessage();
            }
        }
    }

    // Redirect to prevent form re-submission
    ob_end_clean();
    header("Location: admin_manage.php?msg=" . urlencode($msg));
    exit();
}

// Fetch existing admin accounts
try {
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE role = 'admin' ORDER BY username ASC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $msg = "❌ Error fetching admin accounts: " . $e->getMessage();
    $admins = []; // Ensure $admins is an empty array on error
}

// Check for messages from GET requests
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// Include header
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Manage Admin Accounts</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <div style="margin-bottom: 40px;">
        <h3 style="color: #ccc; margin-bottom: 20px;">Create New Admin</h3>
        <form action="admin_manage.php" method="POST">
            <label for="new_admin_username">Username:</label>
            <input type="text" id="new_admin_username" name="username" placeholder="Enter username for new admin" required><br>

            <label for="new_admin_email">Email:</label>
            <input type="email" id="new_admin_email" name="email" placeholder="Enter email for new admin" required><br>

            <label for="new_admin_password">Password:</label>
            <input type="password" id="new_admin_password" name="password" placeholder="Set password for new admin" required><br>

            <button type="submit" name="create_admin" class="btn gold" style="margin-top: 20px;">Create Admin</button>
        </form>
    </div>

    <div style="margin-top: 40px;">
        <h3 style="color: #ccc; margin-bottom: 20px;">Existing Admin Accounts</h3>
        <?php if (!empty($admins)): ?>
            <div class="admin-list">
                <?php foreach ($admins as $admin): ?>
                    <div class="news-card" style="margin-bottom: 15px;">
                        <h4 style="color: #f4c95d;"><?= htmlspecialchars($admin['username']) ?></h4>
                        <p style="color: #ddd;">Email: <?= htmlspecialchars($admin['email']) ?></p>
                        <p style="color: #aaa; font-size: 0.9em;">User ID: <?= htmlspecialchars($admin['user_id']) ?></p>

                        <!-- Edit and Delete Forms -->
                        <form action="admin_manage.php" method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['user_id']) ?>">
                            <label for="edit_username_<?= $admin['user_id'] ?>">New Username:</label>
                            <input type="text" id="edit_username_<?= $admin['user_id'] ?>" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required><br>
                            <label for="edit_email_<?= $admin['user_id'] ?>">New Email:</label>
                            <input type="email" id="edit_email_<?= $admin['user_id'] ?>" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required><br>
                            <label for="edit_password_<?= $admin['user_id'] ?>">New Password (leave blank to keep current):</label>
                            <input type="password" id="edit_password_<?= $admin['user_id'] ?>" name="password" placeholder="New Password"><br>

                            <button type="submit" name="update_admin" class="btn gold" style="margin-top: 10px; margin-right: 10px;">Update Admin</button>
                            <?php if ($admin['user_id'] !== $current_admin_id): ?>
                                <button type="submit" name="delete_admin" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to delete this admin account? This cannot be undone.');">Delete Admin</button>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.9em; margin-left: 10px;">(Cannot delete your own account)</span>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #ccc;">No other admin accounts found.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
ob_end_flush(); // Flush the output buffer
?>
