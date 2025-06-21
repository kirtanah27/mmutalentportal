<?php

ob_start(); // Start output buffering
session_start();
require_once 'includes/db.php';

$msg = ""; // Initialize message variable

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// --- Fetch cart items from the database ---
$cart_items_for_processing = [];
$total_amount = 0;

try {
    $stmt_cart = $pdo->prepare("
        SELECT ci.talent_id, ci.quantity, t.title, t.price, t.media_path, t.user_id as talent_owner_id
        FROM cart_items ci
        JOIN talents t ON ci.talent_id = t.talent_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt_cart->execute([$user_id]);
    $cart_items_for_processing = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

    // If cart is empty from DB, redirect
    if (count($cart_items_for_processing) === 0) {
        ob_end_clean();
        header("Location: cart.php?msg=" . urlencode("Your cart is empty. Nothing to checkout!"));
        exit;
    }

    foreach ($cart_items_for_processing as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

} catch (PDOException $e) {
    // For debugging: error_log("Checkout processing cart fetch error: " . $e->getMessage());
    ob_end_clean();
    header("Location: cart.php?msg=" . urlencode("❌ Error preparing order: " . $e->getMessage()));
    exit;
}


// --- Handle POST request for placing the order ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form data
    $full_name = trim($_POST['full_name'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    // Basic validation
    if (empty($full_name) || empty($address_line1) || empty($city) || empty($postcode) || empty($phone_number)) {
        $msg = "❗ Please fill in all required details.";
    } else {
        try {
            // Start a transaction for atomicity (optional but good practice)
            $pdo->beginTransaction();

            // 1. Save the transaction
            $stmt_transaction = $pdo->prepare("INSERT INTO transactions (user_id, amount, date, status, full_name, address_line1, address_line2, city, postcode, phone_number) 
                                   VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
            $stmt_transaction->execute([
                $user_id,
                $total_amount,
                'completed', // Assuming 'completed' upon submission
                $full_name,
                $address_line1,
                $address_line2,
                $city,
                $postcode,
                $phone_number
            ]);
            $transaction_id = $pdo->lastInsertId(); // Get the ID of the new transaction

            // 2. Send notifications to talent owners and clear cart items
            foreach ($cart_items_for_processing as $item) {
                $talent_id = $item['talent_id'];
                $talent_title = $item['title'];
                $receiver_id = $item['talent_owner_id']; // This is fetched directly from the joined query

                // Insert notification for the talent owner
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, type, is_read, created_at) VALUES (?, ?, 'talent_purchased', 0, NOW())");
                $notif_stmt->execute([$receiver_id, $talent_title]);

                // 3. Delete individual cart item after successful processing
                $stmt_delete_cart_item = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND talent_id = ?");
                $stmt_delete_cart_item->execute([$user_id, $talent_id]);
            }

            // Commit the transaction if all operations were successful
            $pdo->commit();

            $msg_success = "Purchase completed successfully! Your Transaction ID is #{$transaction_id}.";
            ob_end_clean(); // Clear buffer before redirect
            header("Location: transactions.php?msg=" . urlencode($msg_success)); // Redirect to transactions page
            exit;

        } catch (PDOException $e) {
            // Rollback the transaction if any error occurs
            $pdo->rollBack();
            $msg = "❌ Error processing purchase: " . $e->getMessage();
            // For debugging: error_log("Purchase error: " . $e->getMessage());
        }
    }
}

// If there's a message from a failed POST submission or a GET parameter
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// Include the header (only if not redirecting)
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Confirm Your Order & Details</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <h3 style="color: #ccc; margin-bottom: 20px;">Order Summary:</h3>
    <div class="cart-summary" style="margin-bottom: 30px;">
        <?php foreach ($cart_items_for_processing as $item): ?>
            <div class="news-card" style="display: flex; align-items: center; gap: 20px; margin-bottom: 10px; padding: 15px 20px; background-color: #2c2c2c;">
                <?php if (!empty($item['media_path'])): ?>
                    <img src="<?= htmlspecialchars($item['media_path']) ?>" alt="Talent Media" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                <?php endif; ?>
                <div style="flex-grow: 1;">
                    <p style="margin: 0; color: #f1f1f1;"><strong><?= htmlspecialchars($item['title']) ?></strong> x <?= htmlspecialchars($item['quantity']) ?></p>
                    <p style="margin: 0; font-size: 0.9em; color: #ddd;">RM <?= number_format($item['price'], 2) ?> each</p>
                </div>
                <div style="text-align: right; color: #f4c95d;">
                    RM <?= number_format($item['price'] * $item['quantity'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div style="text-align: right; font-size: 1.2rem; color: #f4c95d; margin-top: 20px;">
            <strong>Total: RM <?= number_format($total_amount, 2) ?></strong>
        </div>
    </div>

    <h3 style="color: #ccc; margin-bottom: 20px;">Your Details:</h3>
    <form action="checkout.php" method="POST">
        <!-- Input fields with pre-filled values from POST (if form was submitted with errors) -->
        <input type="text" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required><br>
        <input type="text" name="address_line1" placeholder="Address Line 1" value="<?= htmlspecialchars($_POST['address_line1'] ?? '') ?>" required><br>
        <input type="text" name="address_line2" placeholder="Address Line 2 (Optional)" value="<?= htmlspecialchars($_POST['address_line2'] ?? '') ?>"><br>
        <input type="text" name="city" placeholder="City" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required><br>
        <input type="text" name="postcode" placeholder="Postcode" value="<?= htmlspecialchars($_POST['postcode'] ?? '') ?>" required><br>
        <input type="text" name="phone_number" placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" required><br>

        <p style="color: #ccc; font-size: 0.9em; margin-top: 20px;">By clicking "Place Order", you agree to the terms and conditions.</p>
        <button type="submit" class="btn gold" style="margin-top: 20px;">Place Order</button>
        <a href="cart.php" class="btn outline" style="margin-top: 20px;">← Back to Cart</a>
    </form>
</div>

<?php
include 'includes/footer.php';
ob_end_flush(); // Flush the output buffer
?>
