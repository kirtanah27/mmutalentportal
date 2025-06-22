<?php

ob_start();
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    ob_end_clean();
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$msg = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    if (isset($_POST['update_quantity'])) { 
        $talent_id = (int)$_POST['talent_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) { 
            try {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND talent_id = ?");
                $stmt->execute([$quantity, $user_id, $talent_id]);
                $msg = "Item quantity updated in cart.";
            } catch (PDOException $e) {
                $msg = "Error updating quantity: " . $e->getMessage();
                error_log("Cart update error: " . $e->getMessage());
            }
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND talent_id = ?");
                $stmt->execute([$user_id, $talent_id]);
                $msg = "Item removed from cart.";
            } catch (PDOException $e) {
                $msg = "Error removing item: " . $e->getMessage();
                error_log("Cart remove error: " . $e->getMessage());
            }
        }
    } elseif (isset($_POST['remove_item'])) { 
        $talent_id = (int)$_POST['talent_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND talent_id = ?");
            $stmt->execute([$user_id, $talent_id]);
            $msg = "Item removed from cart.";
        } catch (PDOException $e) {
            $msg = "Error removing item: " . $e->getMessage();
            error_log("Cart remove error: " . $e->getMessage());
        }
    }
    ob_end_clean();
    header("Location: cart.php?msg=" . urlencode($msg));
    exit;
}

$cart_items = [];
$total_amount = 0;

try {
    $stmt = $pdo->prepare("
        SELECT ci.talent_id, ci.quantity, t.title, t.price, t.media_path, t.user_id as talent_owner_id
        FROM cart_items ci
        JOIN talents t ON ci.talent_id = t.talent_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

} catch (PDOException $e) {
    $msg = "Error retrieving cart details: " . $e->getMessage();
    error_log("Cart fetch error: " . $e->getMessage());
}

if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

include 'includes/header.php';
?>

<!-- HTML Section: Page Structure and Display -->
<div class="container auth-box" style="max-width: 900px;">
    <h2>Your Shopping Cart</h2>

    <?php if ($msg): ?><p class="message"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <?php if (count($cart_items) > 0): ?>
        <div class="cart-items-list" style="margin-top: 30px;">
            <?php foreach ($cart_items as $item): ?>
                <div class="news-card" style="margin-bottom: 20px; display: flex; align-items: center; gap: 20px; padding: 20px 25px;">
                    <?php if (!empty($item['media_path'])): ?>
                        <img src="<?= htmlspecialchars($item['media_path']) ?>" alt="Talent Media" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <?php endif; ?>
                    <div style="flex-grow: 1;">
                        <h4 style="color: #f4c95d; margin-bottom: 5px;"><?= htmlspecialchars($item['title']) ?></h4>
                        <p style="color: #ddd; margin-bottom: 5px;">RM <?= number_format($item['price'], 2) ?></p>
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                            <input type="hidden" name="talent_id" value="<?= htmlspecialchars($item['talent_id']) ?>">
                            <label for="quantity_<?= $item['talent_id'] ?>" style="color: #ccc;">Qty:</label>
                            <input type="number" id="quantity_<?= $item['talent_id'] ?>" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" min="1"
                                   style="width: 60px; padding: 8px; background-color: #2c2c2c; color: #f1f1f1; border-radius: 8px; border: none;">
                            <button type="submit" name="update_quantity" class="btn outline" style="padding: 8px 15px;">Update</button>
                            <button type="submit" name="remove_item" class="btn outline" style="background-color: #d9534f; color: white; border-color: #d9534f; padding: 8px 15px;" onclick="return confirm('Are you sure you want to remove this item?')">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: right; margin-top: 30px; font-size: 1.5rem; color: #f4c95d;">
            <strong>Total: RM <?= number_format($total_amount, 2) ?></strong>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="catalogue.php" class="btn outline" style="margin-right: 15px;">← Back to Catalogue</a>
            <a href="checkout_details.php" class="btn gold">Proceed to Checkout →</a>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.2rem; color: #ccc;">Your cart is empty. Start shopping!</p>
        <div style="text-align: center; margin-top: 30px;">
            <a href="catalogue.php" class="btn gold">Start Shopping Now</a>
        </div>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
ob_end_flush();
?>
