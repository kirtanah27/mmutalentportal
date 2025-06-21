<?php
session_start();
require_once 'includes/db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // In a production environment, direct 'admin' registration should be highly restricted
    // and ideally handled manually or through a separate secure process.
    // This is added here for development/testing convenience as per your request.
    $allowed_roles = ['admin', 'talent', 'buyer']; // Added 'admin' for registration option.

    // Ensure role is one of the allowed values
    if (!in_array($role, $allowed_roles)) {
        $msg = "❌ Invalid role selected.";
    } elseif ($username && $email && $password && $role) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed, $role]);
            $msg = "✅ Account created successfully! You can now login.";
            // Optionally, auto-login the user after successful registration
            // $_SESSION['user'] = ['user_id' => $pdo->lastInsertId(), 'username' => $username, 'role' => $role];
            // header("Location: index.php");
            // exit;
        } catch (PDOException $e) {
            $msg = "❌ Username or email already exists. Or a database error occurred.";
            // For debugging: $msg .= " " . $e->getMessage();
        }
    } else {
        $msg = "❗ Please fill in all fields.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container auth-box">
    <h2>Register</h2>
    <?php if ($msg): ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <form action="register.php" method="POST">
        <input type="hidden" name="action" value="add">
        <label for="username" style="display:block; margin-top:15px; color:#f1f1f1;">Username:</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required><br>
        <label for="email" style="display:block; margin-top:15px; color:#f1f1f1;">Email:</label>
        <input type="email" id="email" name="email" placeholder="Enter email" required><br>
        <label for="password" style="display:block; margin-top:15px; color:#f1f1f1;">Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required><br>

        <label for="role" style="display:block; margin-top:15px; color:#f1f1f1;">Register as:</label>
        <select id="role" name="role" required>
            <option value="">-- Select Role --</option>
            <option value="talent">Talent</option>
            <option value="buyer">Buyer</option>
            <option value="admin">Admin</option> <!-- Added admin option for testing -->
        </select><br>

        <button type="submit" class="btn gold" style="margin-top:20px;">Create Account</button>
    </form>

    <p class="form-footer">Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
