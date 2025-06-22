<?php

ob_start();
session_start();
require_once 'includes/db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $allowed_roles = ['talent', 'buyer']; 

    if (!in_array($role, $allowed_roles)) {
        $msg = "Invalid role selected. Please choose 'Talent' or 'Buyer'.";
    } elseif ($username && $email && $password && $role) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);

            $new_user_id = $pdo->lastInsertId();

            $stmt_fetch_user = $pdo->prepare("SELECT user_id, username, email, role FROM users WHERE user_id = ?");
            $stmt_fetch_user->execute([$new_user_id]);
            $user_data = $stmt_fetch_user->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                $_SESSION['user'] = $user_data;
                session_regenerate_id(true);

                $msg = "Account created successfully! You are now logged in as a " . ucfirst($user_data['role']) . ".";
                
                ob_end_clean();
                header("Location: index.php?msg=" . urlencode($msg));
                exit();
            } else {
                $msg = "An error occurred during auto-login. Please try logging in manually.";
            }

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $msg = "Username or email already exists. Please choose a different one.";
            } else {
                $msg = "A database error occurred during registration. Please try again.";
                error_log("Registration PDO Error: " . $e->getMessage());
            }
        }
    } else {
        $msg = "Please fill in all required fields.";
    }
}

include 'includes/header.php';
?>

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
            <!-- The 'admin' option is not available for public registration -->
        </select><br>

        <button type="submit" class="btn gold" style="margin-top:20px;">Create Account</button>
    </form>

    <p class="form-footer">Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
