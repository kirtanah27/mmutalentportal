<?php
// PHP Section: Setup and Logic

session_start();
require_once 'includes/db.php';

$msg = "";
$talent_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($talent_id <= 0) {
    echo "Invalid talent ID.";
    exit;
}

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $posted_talent_id = (int)$_POST['talent_id'];
    $posted_title = trim($_POST['title']);
    $posted_price = floatval($_POST['price']);
    $posted_media_path = trim($_POST['media_path']);
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) $quantity = 1;
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    if (isset($_SESSION['cart'][$posted_talent_id])) {
        $_SESSION['cart'][$posted_talent_id]['quantity'] += $quantity;
        $msg = "Quantity updated in cart!";
    } else {
        $_SESSION['cart'][$posted_talent_id] = [
            'talent_id' => $posted_talent_id,
            'title' => $posted_title,
            'price' => $posted_price,
            'media_path' => $posted_media_path,
            'quantity' => $quantity
        ];
        $msg = "Item added to cart!";
    }

    header("Location: talent_detail.php?id=$talent_id&msg=" . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) {
    $msg = urldecode($_GET['msg']);
}

// Fetch Talent Details
try {
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.user_id FROM talents t JOIN users u ON t.user_id = u.user_id WHERE t.talent_id = ?");
    $stmt->execute([$talent_id]);
    $talent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$talent) {
        echo "Talent not found.";
        exit;
    }

    $can_view_unapproved = false;
    if (isset($_SESSION['user'])) {
        if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['user_id'] === $talent['user_id']) {
            $can_view_unapproved = true;
        }
    }

    if (!$talent['is_approved'] && !$can_view_unapproved) {
        echo "This talent is not yet approved and cannot be viewed.";
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

include 'includes/header.php';
?>

<div class="container" style="padding: 40px; color: white;">
    <h2 style="color: #f4c95d;"><?= htmlspecialchars($talent['title']) ?></h2>

    <div style="display:none;">
        Approved: <?= $talent['is_approved'] ?> |
        Session UID: <?= $_SESSION['user']['user_id'] ?? 'N/A' ?> |
        Owner: <?= $talent['user_id'] ?> |
        Role: <?= $_SESSION['user']['role'] ?? 'N/A' ?>
    </div>

    <?php if (!$talent['is_approved'] && $can_view_unapproved): ?>
        <p style="color: orange; font-weight: bold; margin-bottom: 15px;">
            ⚠️ This talent is pending admin approval.
        </p>
    <?php endif; ?>

    <?php if ($msg): ?>
        <p class="message"><?= htmlspecialchars($msg) ?></p>
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

    <?php if (
        isset($_SESSION['user']) &&
        $_SESSION['user']['role'] === 'buyer' &&
        $_SESSION['user']['user_id'] !== $talent['user_id'] &&
        $talent['is_approved']
    ): ?>
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="talent_id" value="<?= $talent['talent_id'] ?>">
            <input type="hidden" name="title" value="<?= htmlspecialchars($talent['title']) ?>">
            <input type="hidden" name="price" value="<?= htmlspecialchars($talent['price']) ?>">
            <input type="hidden" name="media_path" value="<?= htmlspecialchars($talent['media_path']) ?>">

            <label for="quantity" style="color: #ccc; margin-right: 10px;">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" style="width: 60px; padding: 8px; background-color: #2c2c2c; color: #f1f1f1; border-radius: 8px; border: none; margin-right: 10px;">

            <button type="submit" name="add_to_cart" class="btn gold">Add to Cart</button>
            <a href="cart.php" class="btn outline">View Cart</a>
        </form>
    <?php elseif (
        isset($_SESSION['user']) &&
        $_SESSION['user']['user_id'] === $talent['user_id'] &&
        $_SESSION['user']['role'] === 'talent'
    ): ?>
        <a href="post.php?edit=<?= $talent['talent_id'] ?>" class="btn gold" style="margin-top: 20px;">Edit My Talent</a>
    <?php endif; ?>

    <a href="catalogue.php" class="btn outline" style="margin-top: 20px;">← Back to Catalogue</a>
</div>

<?php include 'includes/footer.php'; ?>
