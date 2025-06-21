<?php

session_start();
require_once 'includes/db.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role']; // Get role from the hidden input, set by buttons

    // Basic validation for role
    $allowed_roles = ['talent', 'buyer', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $msg = "❌ Invalid role selected.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ];

            header("Location: index.php");
            exit;
        } else {
            $msg = "❌ Invalid login credentials or role. Please try again.";
        }
    }
} else if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}
?>

<?php include 'includes/header.php'; ?>

<style>
/* Styles for the Role Buttons in login.php */
.role-buttons-container {
    margin-bottom: 25px;
    text-align: center;
    display: flex;
    justify-content: center;
    gap: 10px; /* Space between buttons */
    flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
}

.role-button {
    flex: 1; /* Allow buttons to grow and shrink */
    max-width: 120px; /* Max width for each button */
    padding: 12px 15px;
    font-size: 1rem;
    font-weight: bold;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    background-color: #2c2c2c; /* Default button background */
    color: #f1f1f1; /* Default button text color */
    border: 1px solid #444; /* Default border */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.role-button:hover {
    background-color: #3a3a3a;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.role-button.active {
    background-color: #f4c95d; /* Active button background */
    color: #121212; /* Active button text color */
    border-color: #f4c95d;
    box-shadow: 0 0 15px rgba(244, 201, 93, 0.5); /* Gold glow for active */
}

/* Light mode adjustments for the buttons */
body.light-mode .role-button {
    background-color: #e0e0e0;
    color: #333;
    border: 1px solid #ccc;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

body.light-mode .role-button:hover {
    background-color: #d0d0d0;
}

body.light-mode .role-button.active {
    background-color: #f4c95d;
    color: #121212;
    border-color: #f4c95d;
    box-shadow: 0 0 15px rgba(244, 201, 93, 0.3);
}
</style>

<div class="container auth-box">
    <h2>Login</h2>
    <?php if ($msg): ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <form action="login.php" method="POST">
        <input type="hidden" name="action" value="login">

        <!-- Role Buttons -->
        <div class="role-buttons-container">
            <button type="button" class="role-button active" data-role="talent">Login as Talent</button>
            <button type="button" class="role-button" data-role="buyer">Login as Buyer</button>
            <button type="button" class="role-button" data-role="admin">Login as Admin</button>
        </div>
        <input type="hidden" name="role" id="selected-role" value="talent">

        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" class="btn gold">Login</button>
    </form>
    <p class="form-footer">Don't have an account? <a href="register.php">Register here</a>.</p>
</div>

<script>
    const roleButtons = document.querySelectorAll('.role-button');
    const selectedRoleInput = document.getElementById('selected-role');

    // Function to update active button and hidden input
    function updateActiveRole(selectedButton) {
        roleButtons.forEach(button => {
            button.classList.remove('active');
        });
        selectedButton.classList.add('active');
        selectedRoleInput.value = selectedButton.dataset.role;
    }

    // Set initial active state based on the default value of the hidden input
    // This is useful if you want to pre-select a role, e.g., from a query parameter
    let initialRole = selectedRoleInput.value;
    roleButtons.forEach(button => {
        if (button.dataset.role === initialRole) {
            button.classList.add('active');
        }
    });

    // Add event listeners to buttons
    roleButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateActiveRole(this);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
