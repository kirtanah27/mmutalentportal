<?php
ob_start(); // Start output buffering at the very beginning of the script
// ini_set('display_errors', 1); // For debugging: Uncomment to display all errors
// ini_set('display_startup_errors', 1); // For debugging: Uncomment to display startup errors
// error_reporting(E_ALL); // For debugging: Uncomment to report all PHP errors

session_start();
require_once 'includes/db.php'; // Include your database connection

$msg = ""; // Initialize message variable for user feedback

// Check if the form has been submitted via POST method and action is 'add'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Sanitize and retrieve form inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password will be hashed
    $role = $_POST['role'];

    // Define allowed roles for registration
    $allowed_roles = ['talent', 'buyer', 'admin'];

    // Validate if the selected role is allowed
    if (!in_array($role, $allowed_roles)) {
        $msg = "❌ Invalid role selected.";
    } elseif ($username && $email && $password && $role) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and execute the SQL statement to insert the new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$username, $email, $hashed_password, $role]);

            // Auto-login the user after successful registration
            $new_user_id = $pdo->lastInsertId(); // Get the ID of the newly inserted user

            // Fetch the newly created user's data to populate the session
            $stmt_fetch_user = $pdo->prepare("SELECT user_id, username, email, role FROM users WHERE user_id = ?");
            $stmt_fetch_user->execute([$new_user_id]);
            $user_data = $stmt_fetch_user->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                $_SESSION['user'] = $user_data; // Store user data in session
                session_regenerate_id(true); // Regenerate session ID for security

                // Set a success message to be displayed on the redirected page
                $msg = "✅ Account created successfully! You are now logged in.";
                
                // Clear the output buffer and redirect to the home page (or appropriate starting page)
                ob_end_clean(); // Clean the buffer before redirecting
                header("Location: index.php?msg=" . urlencode($msg));
                exit(); // Terminate script execution after redirection
            } else {
                // This case should ideally not happen if insertion was successful
                $msg = "❌ An error occurred during auto-login. Please try logging in manually.";
            }

        } catch (PDOException $e) {
            // Handle database errors (e.g., duplicate username or email)
            // Check if the error code indicates a duplicate entry (SQLSTATE 23000)
            if ($e->getCode() === '23000') {
                $msg = "❌ Username or email already exists. Please choose a different one.";
            } else {
                $msg = "❌ A database error occurred during registration. Please try again.";
                // For detailed debugging, uncomment the line below:
                // error_log("Registration PDO Error: " . $e->getMessage());
            }
        }
    } else {
        $msg = "❗ Please fill in all required fields.";
    }
}

// Include the header file
include 'includes/header.php';
?>

<div class="container auth-box">
    <h2>Register</h2>
    <?php if ($msg): // Display messages to the user ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

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
            <option value="admin">Admin</option> <!-- Remember to restrict admin registration in a production environment -->
        </select><br>

        <button type="submit" class="btn gold" style="margin-top:20px;">Create Account</button>
    </form>

    <p class="form-footer">Already have an account? <a href="login.php">Login here</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
