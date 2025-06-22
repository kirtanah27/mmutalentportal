<?php
// PHP session and database connection setup.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout logic.
$session_timeout = 600;

if (isset($_SESSION['user']) && isset($_SESSION['last_activity'])) {
    if ((time() - $_SESSION['last_activity']) > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?msg=" . urlencode("You have been logged out due to inactivity."));
        exit;
    }
}
$_SESSION['last_activity'] = time();

require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Talent Showcase Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<header>
    <div class="logo">
        <h2>🎨 MMU Talent Showcase</h2>
    </div>

    <nav class="nav">
    <?php if (isset($_SESSION['user'])): ?>
        <a href="index.php">Home</a>

        <?php if ($_SESSION['user']['role'] === 'talent'): ?>
            <a href="profile.php">Profile</a>
            <a href="catalogue.php">E-Catalogue</a>
            <a href="post.php">Post Talent</a>
            <a href="feedback.php">Feedback</a>
            <a href="forum.php">Forum</a>
            <a href="faq.php">FAQ</a>
            <a href="news.php">News</a>
            <a href="notifications.php">Notifications</a>
            <a href="logout.php">Logout</a>
            
        <?php elseif ($_SESSION['user']['role'] === 'buyer'): ?>
            <a href="profile.php">Profile</a>
            <a href="catalogue.php">E-Catalogue</a>
            <a href="feedback.php">Feedback</a>
            <a href="forum.php">Forum</a>
            <a href="faq.php">FAQ</a>
            <a href="news.php">News</a>
            <a href="transactions.php">Purchase History</a>
            <a href="cart.php">Cart</a>
            <a href="logout.php">Logout</a>

        <?php elseif ($_SESSION['user']['role'] === 'admin'): ?>
            <div class="dropdown">
                <a href="#">Manage ▾</a>
                <div class="dropdown-content">
                    <a href="student_manage.php">Student Accounts</a>
                    <a href="admin_manage.php">Admin Accounts</a>
                    <a href="catalogue_manage.php">E-Catalogue</a>
                    <a href="faq_manage.php">FAQ</a>
                    <a href="news_manage.php">News</a>
                    <a href="forum_manage.php">Forum</a>
                    <a href="feedback_manage.php">Feedback</a>
                    <a href="sales_manage.php">Sales History</a>
                </div>
            </div>
            <a href="logout.php">Logout</a>
        <?php endif; ?>

    <?php else: ?>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
    <?php endif; ?>
    
    <a href="about_us.php">About Us</a> <!-- New About Us link -->

    <!-- Dark Mode Toggle Button -->
    <div class="dark-mode-toggle">
        <label class="switch">
            <input type="checkbox" id="darkModeToggle">
            <span class="slider round"></span>
        </label>
    </div>
</nav>

</header>

<script>
    var darkModeToggle = document.getElementById('darkModeToggle');
    var bodyElement = document.body;

    window.onload = function() {
        var savedMode = localStorage.getItem('darkMode');

        if (savedMode === 'enabled') {
            bodyElement.classList.add('light-mode');
            darkModeToggle.checked = true;
        } else {
            bodyElement.classList.remove('light-mode');
            darkModeToggle.checked = false;
        }
    };

    darkModeToggle.addEventListener('change', function() {
        if (this.checked) {
            bodyElement.classList.add('light-mode');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            bodyElement.classList.remove('light-mode');
            localStorage.setItem('darkMode', 'disabled');
        }
        const event = new Event('themeChanged');
        document.body.dispatchEvent(event);
    });

    document.body.addEventListener('themeChanged', function() {
        if (document.body.classList.contains('light-mode')) {
            darkModeToggle.checked = true;
        } else {
            darkModeToggle.checked = false;
        }
    });
</script>
