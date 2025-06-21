<?php

session_start();
require_once 'includes/db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        // Optional: Redirect based on role
        /*
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'buyer') {
            header("Location: buyer_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
        */

        header("Location: index.php");
        exit;
    } else {
        $msg = "❌ Invalid login credentials.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container auth-box">
    <h2>Login</h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>
    <form action="login.php" method="POST">
        <input type="hidden" name="action" value="login">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" class="btn gold">Login</button>
    </form>
    <p class="form-footer">Don't have an account? <a href="register.php">Register here</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
