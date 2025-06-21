<?php
session_start();
require_once 'includes/db.php';

$msg = "";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    header("Location: cart.php?msg=" . urlencode("Your cart is empty. Nothing to checkout!"));
    exit;
}

$cart_items = $_SESSION['cart'];
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');

    if (empty($full_name) || empty($address_line1) || empty($city) || empty($postcode) || empty($phone_number)) {
        $msg = "❗ Please fill in all required details.";
    } else {
        try {
            // Save transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, date, status, full_name, address_line1, address_line2, city, postcode, phone_number) 
                                   VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $total_amount,
                'completed',
                $full_name,
                $address_line1,
                $address_line2,
                $city,
                $postcode,
                $phone_number
            ]);

            // Send notifications to talent owners
            foreach ($cart_items as $item) {
                $talent_id = $item['talent_id'];
                $talent_title = $item['title'];

                // Get the talent owner's user_id
                $owner_stmt = $pdo->prepare("SELECT user_id FROM talents WHERE talent_id = ?");
                $owner_stmt->execute([$talent_id]);
                $owner = $owner_stmt->fetch();

                if ($owner) {
                    $receiver_id = $owner['user_id'];

                    // Insert proper notification (with title + type)
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, type, is_read, created_at) VALUES (?, ?, 'talent_purchased', 0, NOW())");
                    $notif_stmt->execute([$receiver_id, $talent_title]);
                }
            }

            unset($_SESSION['cart']);
            $msg_success = "Purchase completed successfully! Thank you.";
            header("Location: catalogue.php?msg=" . urlencode($msg_success));
            exit;

        } catch (PDOException $e) {
            $msg = "❌ Error processing purchase: " . $e->getMessage();
        }
    }
}

if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

include 'includes/header.php';
?>

<div class="container auth-box" style="max-width: 900px;">
    <h2 style="color: #f4c95d; text-align: center; margin-bottom: 30px;">Confirm Your Order & Details</h2>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

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

<?php include 'includes/footer.php'; ?>
