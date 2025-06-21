<?php
// PHP Section: Setup and Logic

// 1. Start the session to manage user login status and cart data.
session_start();
// 2. Include the database connection file (still needed for talent details, but not for cart storage).
require_once 'includes/db.php';

// 3. Check if the user is logged in. If not, redirect to the login page.
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit; // Stop script execution after redirect.
}

// 4. Get the current logged-in user's ID from the session.
$user_id = $_SESSION['user']['user_id'];

// 5. Initialize a message variable for user feedback (success/error).
$msg = "";

// 6. Initialize the cart session variable if it doesn't exist.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 7. Handle POST requests for cart actions (updating quantity or removing items).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_quantity'])) {
        $talent_id = (int)$_POST['talent_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) {
            if (isset($_SESSION['cart'][$talent_id])) {
                $_SESSION['cart'][$talent_id]['quantity'] = $quantity;
                $msg = "Item quantity updated.";
            } else {
                $msg = "Error: Item not found in cart.";
            }
        } else {
            $msg = "Quantity must be at least 1. If you want to remove, use the 'Remove' button.";
        }
    } elseif (isset($_POST['remove_item'])) {
        $talent_id = (int)$_POST['talent_id'];

        if (isset($_SESSION['cart'][$talent_id])) {
            unset($_SESSION['cart'][$talent_id]);
            $msg = "Item removed from cart.";
        } else {
            $msg = "Error: Item not found in cart.";
        }
    }
    header("Location: cart.php?msg=" . urlencode($msg));
    exit;
}

// 8. Fetch message from URL query string if redirected (GET request).
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// 9. Prepare cart items for display directly from the session.
$cart_items = [];
$total_price = 0;

foreach ($_SESSION['cart'] as $talent_id => $item) {
    $cart_items[] = $item;
    $total_price += $item['price'] * $item['quantity'];
}

// HTML Section: Page Structure and Display

// Include the standard header.
include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center;">Your Shopping Cart</h2>

    <?php
    if ($msg):
    ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php
    if (count($cart_items) > 0):
    ?>
        <div class="cart-items" style="margin-top: 30px;">
            <?php foreach ($cart_items as $item): ?>
                <div class="news-card" style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding: 25px 30px;">
                    <?php if (!empty($item['media_path'])): ?>
                        <div class="media-preview" style="flex-shrink: 0;">
                            <?php
                                $ext = strtolower(pathinfo($item['media_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    echo '<img src="' . htmlspecialchars($item['media_path']) . '" alt="Talent Media" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">';
                                } else {
                                    $icon_src = 'assets/icons/file.png';
                                    if ($ext === 'mp4' || $ext === 'webm') {
                                        $icon_src = 'assets/icons/video.png';
                                    } elseif ($ext === 'mp3' || $ext === 'wav') {
                                        $icon_src = 'assets/icons/audio.png';
                                    } elseif ($ext === 'pdf') {
                                        $icon_src = 'assets/icons/pdf.png';
                                    }
                                    echo '<img src="' . htmlspecialchars($icon_src) . '" alt="' . htmlspecialchars(ucfirst($ext)) . ' File" style="width: 50px; height: 50px; object-fit: contain;">';
                                }
                            ?>
                        </div>
                    <?php endif; ?>

                    <div style="flex-grow: 1;">
                        <h4 style="color: #f4c95d; margin-bottom: 5px;"><a href="talent_detail.php?id=<?= htmlspecialchars($item['talent_id']) ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($item['title']) ?></a></h4>
                        <p style="color: #ddd; margin-bottom: 10px;">Price: RM <?= number_format($item['price'], 2) ?></p>

                        <form method="POST" style="display: flex; align-items: center; gap: 10px;">
                            <input type="hidden" name="talent_id" value="<?= htmlspecialchars($item['talent_id']) ?>">
                            <label for="quantity-<?= htmlspecialchars($item['talent_id']) ?>" style="color: #ccc;">Quantity:</label>
                            <input type="number" id="quantity-<?= htmlspecialchars($item['talent_id']) ?>" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1"
                                style="width: 70px; padding: 8px; background-color: #2c2c2c; color: #f1f1f1; border-radius: 8px; border: none;">

                            <button type="submit" name="update_quantity" class="btn outline">Update</button>
                            <button type="submit" name="remove_item" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f;" onclick="return confirm('Are you sure you want to remove this item from your cart?');">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: right; margin-top: 40px; font-size: 1.4rem; color: #f4c95d; padding-right: 20px;">
            <strong>Total Cart Price: RM <?= number_format($total_price, 2) ?></strong>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="checkout_details.php" class="btn gold">Proceed to Checkout</a>
        </div>

    <?php
    else:
    ?>
        <p style="text-align: center; font-size: 1.1rem; color: #ccc;">Your shopping cart is currently empty.</p>
        <div style="text-align: center; margin-top: 30px;">
            <a href="catalogue.php" class="btn outline">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Include the standard footer.
include 'includes/footer.php';
?>
