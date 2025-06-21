<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
            <a href="notifications.php">Notifications</a>
            <a href="logout.php">Logout</a>

        <?php elseif ($_SESSION['user']['role'] === 'buyer'): ?>
            <a href="profile.php">Profile</a>
            <a href="catalogue.php">E-Catalogue</a>
            <a href="feedback.php">Feedback</a>
            <a href="forum.php">Forum</a>
            <a href="transactions.php">Purchase History</a>
            <a href="cart.php">Cart</a>
            <a href="logout.php">Logout</a>

        <?php elseif ($_SESSION['user']['role'] === 'admin'): ?>
            <!-- Admin-only dropdown -->
            <div class="dropdown">
                <a href="#">Manage ▾</a>
                <div class="dropdown-content">
                    <a href="student_manage.php">Student Accounts</a>
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
    // JavaScript for Dark Mode Toggle
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
    });
</script>

<style>
/* These styles should ideally be in your assets/css/style.css */

/* Styles for the dark mode toggle switch */
.dark-mode-toggle {
    display: flex;
    align-items: center;
    margin-left: 20px; /* Adjust spacing as needed */
}

/* The switch - the box around the slider */
.switch {
  position: relative;
  display: inline-block;
  width: 45px; /* Smaller width */
  height: 25px; /* Smaller height */
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #333; /* Dark background for the slider track */
  -webkit-transition: .4s;
  transition: .4s;
  border-radius: 25px; /* Make it round */
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px; /* Smaller circle */
  width: 18px; /* Smaller circle */
  left: 3px; /* Position the circle */
  bottom: 3.5px; /* Align vertically */
  background-color: #f4c95d; /* Gold color for the handle */
  -webkit-transition: .4s;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #555; /* Slightly lighter track when checked */
}

input:checked + .slider:before {
  -webkit-transform: translateX(20px); /* Move slider to the right */
  -ms-transform: translateX(20px);
  transform: translateX(20px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 25px;
}

.slider.round:before {
  border-radius: 50%;
}

/* Light mode styles */
body.light-mode {
    background: #f0f0f0; /* Light background */
    color: #333; /* Darker text */
}

body.light-mode header {
    background-color: #f8f8f8;
    border-bottom: 1px solid #e0e0e0;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
}

body.light-mode .logo h2 {
    color: #333; /* Darker logo text */
}

body.light-mode nav.nav a {
    color: #555;
}

body.light-mode nav.nav a:hover {
    color: #f4c95d; /* Gold hover remains */
}

body.light-mode .dropdown-content {
    background-color: #ffffff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border: 1px solid #e0e0e0;
}

body.light-mode .dropdown-content a {
    color: #333;
}

body.light-mode .dropdown-content a:hover {
    background-color: #f0f0f0;
    color: #f4c95d;
}

body.light-mode .hero {
    background: linear-gradient(to bottom right, rgba(240, 240, 240, 0.9), rgba(255, 255, 255, 0.95));
}

body.light-mode .hero-title {
    color: #333;
}

body.light-mode .hero-subtext {
    color: #666;
}

body.light-mode .btn.gold {
    background-color: #f4c95d; /* Gold remains */
    color: #121212;
}

body.light-mode .btn.outline {
    background: transparent;
    color: #f4c95d; /* Gold outline remains */
    border: 2px solid #f4c95d;
}

body.light-mode .btn.outline:hover {
    background-color: #f4c95d;
    color: #121212;
}

body.light-mode .features {
    background-color: #e0e0e0;
}

body.light-mode .feature-card {
    background-color: #ffffff;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
}

body.light-mode .feature-card h2 {
    color: #333;
}

body.light-mode .feature-card p {
    color: #666;
}

body.light-mode .news-card,
body.light-mode .admin-news-card,
body.light-mode .forum-thread {
    background-color: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
}

body.light-mode .news-card h4,
body.light-mode .forum-thread h4 {
    color: #333;
}

body.light-mode .news-card p,
body.light-mode .forum-thread p {
    color: #555;
}

body.light-mode .news-card .meta,
body.light-mode .forum-thread .meta {
    color: #888;
}

body.light-mode .auth-box {
    background-color: #ffffff;
    box-shadow: 0 0 50px rgba(0, 0, 0, 0.05), 0 0 10px rgba(0, 0, 0, 0.02);
    border: 1px solid rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(0px); /* Remove blur for light mode */
}

body.light-mode .auth-box h2 {
    color: #333;
}

body.light-mode .auth-box label {
    color: #333;
}

body.light-mode .auth-box input,
body.light-mode .auth-box select,
body.light-mode .auth-box textarea {
    background-color: #eee;
    color: #333;
    border: 1px solid #ccc;
}

body.light-mode .auth-box input:focus,
body.light-mode .auth-box select:focus,
body.light-mode .auth-box textarea:focus {
    border: 2px solid #f4c95d;
}

body.light-mode .auth-box .form-footer {
    color: #666;
}

body.light-mode .auth-box a {
    color: #f4c95d;
}

body.light-mode .profile-info p {
    color: #333;
}

body.light-mode .toggle-switch label.switch-label {
    color: #333;
}

body.light-mode footer {
    background-color: #f8f8f8;
    border-top: 1px solid #e0e0e0;
    color: #777;
}

body.light-mode .reply {
  border-left: 2px solid #ccc !important;
}

body.light-mode .faq-item {
    background: #f5f5f5 !important;
}

body.light-mode .faq-item h4 {
    color: #333 !important;
}

</style>
