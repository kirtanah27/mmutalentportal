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

// 5. Fetch all sales transactions, including buyer's username.
$sales = []; // Initialize an empty array for sales data.
try {
    // Prepare and execute SQL query to retrieve all transactions.
    // JOIN with 'users' table to display the username of the buyer.
    // ORDER BY clause ensures latest transactions are shown first.
    $stmt = $pdo->prepare("
        SELECT
            t.*,
            u.username AS buyer_username
        FROM
            transactions t
        JOIN
            users u ON t.user_id = u.user_id
        ORDER BY
            t.date DESC
    ");
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as associative array.

} catch (PDOException $e) {
    // Display database error message if fetching sales data fails.
    $msg = "Error retrieving sales data: " . $e->getMessage();
}

// HTML Section: Page Structure and Display

// Include the standard header for the page.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 1000px;">
    <!-- Page Title -->
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">All Sales Transactions</h2>

    <?php
    // Display any message (success or error).
    if ($msg):
    ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php
    // Check if there are any sales transactions to display.
    if (count($sales) > 0):
    ?>
        <div class="sales-list" style="margin-top: 20px;">
            <?php
            // Loop through each sale transaction and display its details.
            foreach ($sales as $sale):
            ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;">Transaction ID: <?= htmlspecialchars($sale['transaction_id']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Bought By:</strong>
                        <a href="profile.php?user_id=<?= htmlspecialchars($sale['user_id']) ?>" style="color: #f4c95d; text-decoration: none;">
                            <?= htmlspecialchars($sale['buyer_username']) ?>
                        </a>
                    </p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Amount:</strong> RM <?= number_format($sale['amount'], 2) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($sale['date'])) ?></p>
                    <p style="color: #ddd;"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($sale['status'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    // Display a message if no sales transactions are found.
    else:
    ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">No sales transactions found yet.</p>
    <?php endif; ?>
</div>

<?php
// Include the standard footer for the page.
include 'includes/footer.php';
?>
