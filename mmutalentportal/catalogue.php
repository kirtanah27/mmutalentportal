<?php
session_start();
require_once 'includes/db.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$msg = $_GET['msg'] ?? ''; // Retrieve message from URL if present

$sql = "SELECT t.*, u.username, u.user_id FROM talents t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.is_approved = TRUE";  // ✅ Only show approved talents

$params = [];

if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR t.tagline LIKE ? OR t.tags LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $sql .= " AND t.category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$talents = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="container" style="padding: 40px;">
    <h2 style="color: #f4c95d;">🎨 Talent Catalogue</h2>

    <form method="GET" action="catalogue.php" style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Search talents..." value="<?= htmlspecialchars($search) ?>" style="padding: 10px; width: 250px;">
        <select name="category" style="padding: 10px;">
            <option value="">All Categories</option>
            <option value="Art" <?= $category == 'Art' ? 'selected' : '' ?>>Art</option>
            <option value="Music" <?= $category == 'Music' ? 'selected' : '' ?>>Music</option>
            <option value="Writing" <?= $category == 'Writing' ? 'selected' : '' ?>>Writing</option>
            <option value="Tutoring" <?= $category == 'Tutoring' ? 'selected' : '' ?>>Tutoring</option>
            <option value="Programming" <?= $category == 'Programming' ? 'selected' : '' ?>>Programming</option>
            <option value="Photography" <?= $category == 'Photography' ? 'selected' : '' ?>>Photography</option>
            <option value="Design" <?= $category == 'Design' ? 'selected' : '' ?>>Design</option>
        </select>
        <button type="submit" class="btn gold">Search</button>
        <?php if (isset($_SESSION['user'])): ?>
            <a href="post.php" class="btn outline">+ Post Your Talent</a>
        <?php endif; ?>
    </form>

    <div style="margin-top: 40px; display: flex; flex-wrap: wrap; gap: 20px;">
        <?php if ($talents): ?>
            <?php foreach ($talents as $talent): ?>
                <div class="feature-card clickable-card"
                     data-id="<?= $talent['talent_id'] ?>"
                     style="cursor: pointer; width: 300px; background: #1c1c1c; padding: 20px; border-radius: 12px; color: white;">

                    <h3 style="color: #f4c95d;"><?= htmlspecialchars($talent['title']) ?></h3>

                    <?php if (!empty($talent['tagline'])): ?>
                        <p style="font-style: italic; color: #aaa;"><?= htmlspecialchars($talent['tagline']) ?></p>
                    <?php endif; ?>

                    <p><strong>By:</strong>
                        <a href="profile.php?id=<?= $talent['user_id'] ?>" style="color: #f4c95d;" onclick="event.stopPropagation();">
                            <?= htmlspecialchars($talent['username']) ?>
                        </a>
                    </p>

                    <?php if (!empty($talent['media_path'])): ?>
                        <div class="media-preview" style="margin-bottom: 10px;">
                            <?php
                                $ext = strtolower(pathinfo($talent['media_path'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    echo '<img src="' . $talent['media_path'] . '" alt="preview" style="width: 100%; border-radius: 8px;">';
                                } elseif (in_array($ext, ['mp4', 'webm'])) {
                                    echo '<img src="assets/icons/video.png" alt="Video File" style="width: 100px;">';
                                } elseif (in_array($ext, ['mp3', 'wav'])) {
                                    echo '<img src="assets/icons/audio.png" alt="Audio File" style="width: 100px;">';
                                } elseif ($ext === 'pdf') {
                                    echo '<img src="assets/icons/pdf.png" alt="PDF File" style="width: 100px;">';
                                } else {
                                    echo '<img src="assets/icons/file.png" alt="File" style="width: 100px;">';
                                }
                            ?>
                        </div>
                    <?php endif; ?>

                    <p style="margin-top: 10px;"><?= nl2br(htmlspecialchars($talent['description'])) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($talent['category']) ?></p>
                    <p><strong>Price:</strong> RM <?= number_format($talent['price'], 2) ?></p>
                    <p><strong>Delivery Time:</strong> <?= $talent['delivery_time'] ?> day(s)</p>

                    <?php if (!empty($talent['tags'])): ?>
                        <p><strong>Tags:</strong>
                            <?php foreach (explode(',', $talent['tags']) as $tag): ?>
                                <span style="background:#444; padding:3px 8px; border-radius:6px; margin-right:5px;">
                                    <?= htmlspecialchars(trim($tag)) ?>
                                </span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="margin-top: 20px;">No matching talents found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Make entire card clickable
    document.querySelectorAll('.clickable-card').forEach(card => {
        card.addEventListener('click', () => {
            const id = card.getAttribute('data-id');
            if (id) {
                window.location.href = 'talent_detail.php?id=' + id;
            }
        });
    });

    window.onload = function() {
        var urlParams = new URLSearchParams(window.location.search);
        var message = urlParams.get('msg');
        if (message) {
            alert(message);
            history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/&?msg=[^&]*/, ''));
        }
    };
</script>

<?php include 'includes/footer.php'; ?>
