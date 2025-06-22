<?php
// Ensure output buffering starts at the absolute top of the file
ob_start();

// Check if session has already been started by includes/header.php.
// If not, start it. This is a failsafe, but ideally header.php handles it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout logic is now primarily in includes/header.php for global application,
// but we retain the require_once 'includes/db.php'; here as well.
require_once 'includes/db.php';

$latest = $pdo->query("SELECT talent_id FROM talents WHERE is_approved = TRUE ORDER BY created_at DESC LIMIT 1")->fetch();

$total_users = 0;
$total_talents = 0;
$pending_talent_approvals = 0;
$pending_feedback = 0;
$pending_faq_submissions = 0;
$total_sales_amount = 0;

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM users");
        $total_users = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM talents");
        $total_talents = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM talents WHERE is_approved = FALSE");
        $pending_talent_approvals = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM feedback WHERE status = 'pending'");
        $pending_feedback = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT COUNT(*) AS count FROM faqsubmission WHERE status = 'pending'");
        $pending_faq_submissions = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT SUM(amount) AS total_sum FROM transactions WHERE status = 'completed'");
        $result = $stmt->fetch();
        $total_sales_amount = $result['total_sum'] ?? 0;
    } catch (PDOException $e) {
        // echo "Error: " . $e->getMessage();
    }
}

// Include the header file AFTER all PHP logic that might set headers
include 'includes/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <?php if (!isset($_SESSION['user'])): ?>
            <h1 class="hero-title">🎨 MMU Talent Showcase Portal</h1>
            <p class="hero-subtext">Discover, share, and showcase your talent with the MMU community.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn gold">Register</a>
                <a href="login.php" class="btn outline">Login</a>
                <?php if ($latest): ?>
                    <a href="talent_detail.php?id=<?= htmlspecialchars($latest['talent_id']) ?>" class="btn outline">🎯 View Featured Talent</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <h1 class="hero-title">👨‍💼 Admin Dashboard</h1>
                <p class="hero-subtext">Monitor platform activity and manage the community effectively.</p>

                <div class="admin-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">👥 Total Users</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;"><?= htmlspecialchars($total_users) ?></p>
                    </div>
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">🎨 Total Talents</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;"><?= htmlspecialchars($total_talents) ?></p>
                    </div>
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">⏳ Pending Approvals</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;"><?= htmlspecialchars($pending_talent_approvals) ?></p>
                        <?php if ($pending_talent_approvals > 0): ?>
                            <a href="catalogue_manage.php" class="btn outline" style="margin-top: 10px;">Review Talents</a>
                        <?php endif; ?>
                    </div>
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">📝 Pending Feedbacks</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;"><?= htmlspecialchars($pending_feedback) ?></p>
                    </div>
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">❓ FAQ Submissions</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;"><?= htmlspecialchars($pending_faq_submissions) ?></p>
                    </div>
                    <div class="feature-card" style="padding: 25px;">
                        <h2 style="color: #f4c95d;">💰 Total Sales</h2>
                        <p style="font-size: 2.2rem; font-weight: bold; color: #f1f1f1;">RM <?= number_format($total_sales_amount, 2) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <h1 class="hero-title">Welcome back, <?= htmlspecialchars($_SESSION['user']['username']) ?>!</h1>
                <p class="hero-subtext">Explore the latest talents, manage your profile, or share your own creations.</p>
                <div class="hero-buttons">
                    <a href="catalogue.php" class="btn gold">Explore Talents</a>
                    <a href="profile.php" class="btn outline">My Profile</a>
                    <?php if ($latest): ?>
                        <a href="talent_detail.php?id=<?= htmlspecialchars($latest['talent_id']) ?>" class="btn outline">🎯 View Featured Talent</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin'): ?>
<section class="features">
    <div class="feature-card">
        <h2>🎭 Creative Expression</h2>
        <p>Post your artwork, music, writing, or code. Let others see your skills.</p>
    </div>
    <div class="feature-card">
        <h2>💬 Community</h2>
        <p>Engage in forums, get feedback, and connect with other students.</p>
    </div>
    <div class="feature-card">
        <h2>🛒 Commissions</h2>
        <p>Offer or request commissions, and support each other’s talents.</p>
    </div>
</section>
<?php endif; ?>

<?php
include 'includes/footer.php';
// Flush the output buffer at the very end of the script
ob_end_flush();
?>
