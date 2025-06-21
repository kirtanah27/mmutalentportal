<?php
// PHP Section: Setup and Logic

// 1. Start the session to manage user login status.
session_start();
// 2. Include the database connection file.
require_once 'includes/db.php';

// 3. Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// 4. Get the current logged-in user's ID.
$user_id = $_SESSION['user']['user_id'];

// 5. Initialize message variable for user feedback.
$msg = "";

// 6. Fetch past transactions for the current user from the database.
$transactions = []; // Initialize an empty array for transactions.
try {
    // Prepare and execute SQL query to retrieve transactions for the logged-in user.
    // ORDER BY clause ensures latest transactions are shown first.
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results as associative array.

} catch (PDOException $e) {
    // Display database error message if fetching transactions fails.
    $msg = "Error retrieving past transactions: " . $e->getMessage();
}

// HTML Section: Page Structure and Display

// Include the standard header for the page, which contains the navigation and overall layout.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <!-- Page Title -->
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Your Past Transactions</h2>

    <?php
    // Display any message (success or error).
    // htmlspecialchars() is used to prevent XSS (Cross-Site Scripting) vulnerabilities.
    if ($msg):
    ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php
    // Check if there are any transactions to display.
    // count() function returns the number of elements in an array.
    if (count($transactions) > 0):
    ?>
        <div class="transaction-list" style="margin-top: 20px;">
            <?php
            // Loop through each transaction and display its details.
            // foreach loop is ideal for iterating through arrays.
            foreach ($transactions as $transaction):
            ?>
                <div class="news-card" style="margin-bottom: 20px; padding: 25px 30px;">
                    <h4 style="color: #f4c95d; margin-bottom: 10px;">Transaction ID: <?= htmlspecialchars($transaction['transaction_id']) ?></h4>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Amount:</strong> RM <?= number_format($transaction['amount'], 2) ?></p>
                    <p style="color: #ddd; margin-bottom: 5px;"><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($transaction['date'])) ?></p>
                    <p style="color: #ddd;"><strong>Status:</strong> <?= htmlspecialchars(ucfirst($transaction['status'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php
    // Display a message if no transactions are found.
    else:
    ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">You have no past transactions.</p>
        <div style="text-align: center; margin-top: 30px;">
            <a href="catalogue.php" class="btn outline">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the standard footer for the page.
include 'includes/footer.php';
?>
