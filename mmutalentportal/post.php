<?php

session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'talent') {
    header("Location: login.php");
    exit;
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $tagline = trim($_POST['tagline']);
    $description = trim($_POST['description']);
    $category = $_POST['category'] === 'Other' ? trim($_POST['custom_category']) : $_POST['category'];
    $media_type = $_POST['media_type'] === 'other' ? trim($_POST['custom_media_type']) : $_POST['media_type'];
    $tags = trim($_POST['tags']);
    $price = floatval($_POST['price']);
    $delivery_time = intval($_POST['delivery_time']);
    $user_id = $_SESSION['user']['user_id'];

    $media_path = "";

    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/media/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['media']['name']);
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_path)) {
            $media_path = $target_path;
        } else {
            $msg = "❌ Failed to upload media.";
        }
    }

    if ($title && $description && $category && $media_type && $media_path && $price > 0 && $delivery_time > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO talents (user_id, title, tagline, description, media_type, media_path, category, price, delivery_time, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $tagline, $description, $media_type, $media_path, $category, $price, $delivery_time, $tags]);
            header("Location: catalogue.php?posted=1");
            exit;
        } catch (PDOException $e) {
            $msg = "❌ Failed to post talent. Database error: " . $e->getMessage();
        }
    } else {
        if (!$msg) {
            $msg = "❗ Please fill in all fields correctly.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container auth-box">
    <h2>Post Your Talent</h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <form action="post.php" method="POST" enctype="multipart/form-data" id="talentForm">
        <input type="text" name="title" placeholder="Title" required><br>

        <input type="text" name="tagline" maxlength="100" placeholder="Short tagline (e.g. Fast, creative logo design)" required><br>

        <textarea name="description" placeholder="Describe your work..." rows="4" required></textarea><br>

        <select name="category" onchange="toggleCustom('category')" required>
            <option value="">-- Select Category --</option>
            <option value="Art">Art</option>
            <option value="Music">Music</option>
            <option value="Design">Design</option>
            <option value="Tutoring">Tutoring</option>
            <option value="Programming">Programming</option>
            <option value="Photography">Photography</option>
            <option value="Writing">Writing</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" name="custom_category" id="custom_category" placeholder="Enter custom category" style="display:none;"><br>

        <select name="media_type" onchange="toggleCustom('media')" required>
            <option value="">-- Select Media Type --</option>
            <option value="image">Image</option>
            <option value="video">Video</option>
            <option value="audio">Audio</option>
            <option value="pdf">PDF</option>
            <option value="other">Other</option>
        </select>
        <input type="text" name="custom_media_type" id="custom_media_type" placeholder="Enter custom media type" style="display:none;"><br>

        <input type="file" name="media" accept="image/*,video/*,audio/*,application/pdf" required onchange="previewMedia(event)"><br>

        <div id="mediaPreview" style="margin: 10px 0;"></div>

        <input type="text" name="tags" placeholder="Skills or tags (comma separated)"><br>

        <input type="number" name="price" placeholder="Price (RM)" min="1" step="0.01" required><br>
        <input type="number" name="delivery_time" placeholder="Delivery Time (in days)" min="1" required><br>

        <button type="submit" class="btn gold">Submit Talent</button>
    </form>
</div>

<script>
function toggleCustom(type) {
    const categoryInput = document.getElementById('custom_category');
    const mediaInput = document.getElementById('custom_media_type');

    if (type === 'category') {
        const categorySelect = document.querySelector('select[name="category"]');
        categoryInput.style.display = categorySelect.value === 'Other' ? 'block' : 'none';
    }

    if (type === 'media') {
        const mediaSelect = document.querySelector('select[name="media_type"]');
        mediaInput.style.display = mediaSelect.value === 'other' ? 'block' : 'none';
    }
}

function previewMedia(event) {
    const preview = document.getElementById('mediaPreview');
    preview.innerHTML = "";

    const file = event.target.files[0];
    const url = URL.createObjectURL(file);
    const type = file.type;

    if (type.startsWith("image")) {
        preview.innerHTML = `<img src="${url}" style="max-width: 200px; border-radius: 8px;">`;
    } else if (type.startsWith("video")) {
        preview.innerHTML = `<video controls style="max-width: 300px;"><source src="${url}" type="${type}">Your browser does not support the video tag.</video>`;
    } else if (type.startsWith("audio")) {
        preview.innerHTML = `<audio controls><source src="${url}" type="${type}">Your browser does not support the audio tag.</audio>`;
    } else if (type.includes("pdf")) {
        preview.innerHTML = `<p>📄 PDF selected: ${file.name}</p>`;
    } else {
        preview.innerHTML = `<p>📁 File selected: ${file.name}</p>`;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
