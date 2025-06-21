<?php
ob_start(); // Start output buffering at the very beginning of the script
session_start();
require_once 'includes/db.php'; // Include your database connection file

$msg = ""; // Initialize message variable for user feedback

// Get the talent ID from the URL. If not set or invalid, exit.
$talent_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($talent_id <= 0) {
    echo "Invalid talent ID.";
    exit;
}

// --- PHP Logic for Adding to Cart (Database Persistence) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if a user is logged in
    if (!isset($_SESSION['user'])) {
        // If not logged in, redirect to login page
        ob_end_clean(); // Clear buffer before redirect
        header("Location: login.php?msg=" . urlencode("Please log in to add items to your cart."));
        exit();
    }

    $user_id = $_SESSION['user']['user_id'];
    $posted_talent_id = (int)$_POST['talent_id'];
    $quantity = (int)$_POST['quantity'];

    // Ensure quantity is at least 1
    if ($quantity < 1) {
        $quantity = 1;
    }

    try {
        // Check if the talent is already in the user's cart
        $stmt_check_cart = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND talent_id = ?");
        $stmt_check_cart->execute([$user_id, $posted_talent_id]);
        $existing_item = $stmt_check_cart->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // If item exists, update its quantity
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt_update_cart = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND talent_id = ?");
            $stmt_update_cart->execute([$new_quantity, $user_id, $posted_talent_id]);
            $msg = "✅ Quantity updated in cart!";
        } else {
            // If item does not exist, insert it into the cart
            $stmt_insert_cart = $pdo->prepare("INSERT INTO cart_items (user_id, talent_id, quantity) VALUES (?, ?, ?)");
            $stmt_insert_cart->execute([$user_id, $posted_talent_id, $quantity]);
            $msg = "✅ Item added to cart!";
        }
    } catch (PDOException $e) {
        $msg = "❌ Error adding item to cart: " . $e->getMessage();
        // Log the error for debugging: error_log("Cart error: " . $e->getMessage());
    }

    // Redirect back to the talent detail page with a message
    ob_end_clean(); // Clear the output buffer before sending header
    header("Location: talent_detail.php?id=$talent_id&msg=" . urlencode($msg));
    exit; // Terminate script execution after redirection
}
// --- End PHP Logic for Adding to Cart ---


// --- PHP Logic for Fetching Talent Details (Remains largely the same) ---
if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

try {
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.user_id FROM talents t JOIN users u ON t.user_id = u.user_id WHERE t.talent_id = ?");
    $stmt->execute([$talent_id]);
    $talent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$talent) {
        echo "Talent not found.";
        exit;
    }

    $can_view_unapproved = false;
    // Check if the current user is an admin or the owner of the talent
    if (isset($_SESSION['user'])) {
        if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['user_id'] === $talent['user_id']) {
            $can_view_unapproved = true;
        }
    }

    // If talent is not approved and user cannot view unapproved talents, deny access
    if (!$talent['is_approved'] && !$can_view_unapproved) {
        echo "This talent is not yet approved and cannot be viewed.";
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Include the standard header for the page
include 'includes/header.php';
?>

<!-- HTML Section: Page Structure and Display -->
<div class="container" style="padding: 40px; color: white;">
    <h2 style="color: #f4c95d;"><?= htmlspecialchars($talent['title']) ?></h2>

    <?php if (!$talent['is_approved'] && $can_view_unapproved): ?>
        <p style="color: orange; font-weight: bold; margin-bottom: 15px;">
            ⚠️ This talent is pending admin approval.
        </p>
    <?php endif; ?>

    <?php if ($msg): ?>
        <p class="message" id="message-box"><?= htmlspecialchars($msg) ?></p>
    <?php else: ?>
        <p class="message" id="message-box" style="display:none;"></p>
    <?php endif; ?>


    <?php if (!empty($talent['tagline'])): ?>
        <p style="font-style: italic; color: #aaa;"><?= htmlspecialchars($talent['tagline']) ?></p>
    <?php endif; ?>

    <p><strong>By:</strong>
        <a href="profile.php?user_id=<?= $talent['user_id'] ?>" style="color: #f4c95d;">
            <?= htmlspecialchars($talent['username']) ?>
        </a>
    </p>

    <?php if (!empty($talent['media_path'])): ?>
        <div class="media-preview" style="margin: 20px 0;">
            <?php
            $ext = strtolower(pathinfo($talent['media_path'], PATHINFO_EXTENSION));
            $media = htmlspecialchars($talent['media_path']);

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo "<img src='$media' alt='preview' style='width: 100%; border-radius: 12px;'>";
            } elseif (in_array($ext, ['mp4', 'webm'])) {
                echo "<video controls style='width: 100%; border-radius: 12px;'><source src='$media' type='video/$ext'></video>";
            } elseif (in_array($ext, ['mp3', 'wav'])) {
                echo "<audio controls style='width: 100%; margin-top: 10px;'><source src='$media' type='audio/$ext'></audio>";
            } elseif ($ext === 'pdf') {
                echo "<iframe src='$media' style='width: 100%; height: 600px; border: none;'></iframe>";
            } else {
                echo "<p>📁 <a href='$media' target='_blank' style='color: #f4c95d;'>Download File</a></p>";
            }
            ?>
        </div>
    <?php endif; ?>

    <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($talent['description'])) ?></p>
    <p><strong>Category:</strong> <?= htmlspecialchars($talent['category']) ?></p>
    <p><strong>Price:</strong> RM <?= number_format($talent['price'], 2) ?></p>
    <p><strong>Delivery Time:</strong> <?= htmlspecialchars($talent['delivery_time']) ?> day(s)</p>

    <?php if (!empty($talent['tags'])): ?>
        <p><strong>Tags:</strong>
            <?php foreach (explode(',', $talent['tags']) as $tag): ?>
                <span style="background:#444; padding:4px 10px; border-radius:8px; margin-right:6px;">
                    <?= htmlspecialchars(trim($tag)) ?>
                </span>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>

    <?php
    // Display 'Add to Cart' form only if logged in as a buyer and not viewing own talent
    if (
        isset($_SESSION['user']) &&
        $_SESSION['user']['role'] === 'buyer' &&
        $_SESSION['user']['user_id'] !== $talent['user_id'] &&
        $talent['is_approved']
    ):
    ?>
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="talent_id" value="<?= $talent['talent_id'] ?>">
            <!-- These hidden fields are useful for initial display in cart.php,
                 but the definitive price and title should always be fetched from the database
                 in cart.php for security and data integrity. -->
            <input type="hidden" name="title" value="<?= htmlspecialchars($talent['title']) ?>">
            <input type="hidden" name="price" value="<?= htmlspecialchars($talent['price']) ?>">
            <input type="hidden" name="media_path" value="<?= htmlspecialchars($talent['media_path']) ?>">

            <label for="quantity" style="color: #ccc; margin-right: 10px;">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1"
                   style="width: 60px; padding: 8px; background-color: #2c2c2c; color: #f1f1f1; border-radius: 8px; border: none; margin-right: 10px;">

            <button type="submit" name="add_to_cart" class="btn gold">Add to Cart</button>
            <a href="cart.php" class="btn outline">View Cart</a>
        </form>
    <?php
    // Display 'Edit My Talent' button if logged in as talent and viewing own talent
    elseif (
        isset($_SESSION['user']) &&
        $_SESSION['user']['user_id'] === $talent['user_id'] &&
        $_SESSION['user']['role'] === 'talent'
    ):
    ?>
        <a href="post.php?edit=<?= $talent['talent_id'] ?>" class="btn gold" style="margin-top: 20px;">Edit My Talent</a>
    <?php endif; ?>

    <a href="catalogue.php" class="btn outline" style="margin-top: 20px;">← Back to Catalogue</a>
</div>

<?php
include 'includes/footer.php';
ob_end_flush(); // Flush the output buffer at the end of the script
?>
