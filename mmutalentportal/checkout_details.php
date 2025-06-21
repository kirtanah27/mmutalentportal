<?php

ob_start(); // Start output buffering
session_start();
require_once 'includes/db.php';

// Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user'])) {
    ob_end_clean(); // Clear buffer before redirect
    header("Location: login.php");
    exit;
}

// Get current user's ID
$user_id = $_SESSION['user']['user_id'];

// --- Fetch cart items from the database ---
$cart_items_display = [];
$total_amount = 0;

try {
    // Select cart items and join with talents table to get details
    $stmt_cart = $pdo->prepare("
        SELECT ci.talent_id, ci.quantity, t.title, t.price, t.media_path
        FROM cart_items ci
        JOIN talents t ON ci.talent_id = t.talent_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt_cart->execute([$user_id]);
    $cart_items_display = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

    // If cart is empty, redirect back to cart page
    if (count($cart_items_display) === 0) {
        ob_end_clean(); // Clear buffer before redirect
        header("Location: cart.php?msg=" . urlencode("Your cart is empty. Nothing to checkout!"));
        exit;
    }

    // Calculate total amount
    foreach ($cart_items_display as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

} catch (PDOException $e) {
    // Handle database error during cart fetching
    // For debugging: error_log("Checkout details cart fetch error: " . $e->getMessage());
    ob_end_clean(); // Clear buffer before redirect
    header("Location: cart.php?msg=" . urlencode("❌ Error fetching cart details: " . $e->getMessage()));
    exit;
}


// Include the standard header.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Confirm Your Order & Details</h2>

    <h3 style="color: #ccc; margin-bottom: 20px;">Order Summary:</h3>
    <div class="cart-summary" style="margin-bottom: 30px;">
        <?php foreach ($cart_items_display as $item): ?>
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
