<?php
// PHP Section: Setup and Logic

// 1. Start the session to manage user login status and cart data.
session_start();
// 2. Include the database connection file.
require_once 'includes/db.php';

// 3. Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// 4. Ensure there are items in the cart before proceeding.
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header("Location: cart.php?msg=" . urlencode("Your cart is empty. Nothing to checkout!"));
    exit;
}

// 5. Get current user's ID for potential pre-filling details (though not implemented to pull user profile directly here for simplicity).
$user_id = $_SESSION['user']['user_id'];

// 6. Calculate total amount from session cart.
$cart_items = $_SESSION['cart'];
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// HTML Section: Page Structure and Display

// Include the standard header.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Confirm Your Order & Details</h2>

    <h3 style="color: #ccc; margin-bottom: 20px;">Order Summary:</h3>
    <div class="cart-summary" style="margin-bottom: 30px;">
        <?php foreach ($cart_items as $item): ?>
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
        <input type="text" name="full_name" placeholder="Full Name" required><br>
        <input type="text" name="address_line1" placeholder="Address Line 1" required><br>
        <input type="text" name="address_line2" placeholder="Address Line 2 (Optional)"><br>
        <input type="text" name="city" placeholder="City" required><br>
        <input type="text" name="postcode" placeholder="Postcode" required><br>
        <input type="text" name="phone_number" placeholder="Phone Number" required><br>

        <p style="color: #ccc; font-size: 0.9em; margin-top: 20px;">By clicking "Place Order", you agree to the terms and conditions.</p>
        <button type="submit" class="btn gold" style="margin-top: 20px;">Place Order</button>
        <a href="cart.php" class="btn outline" style="margin-top: 20px;">← Back to Cart</a>
    </form>
</div>

<?php
// Include the standard footer.
include 'includes/footer.php';
?>
