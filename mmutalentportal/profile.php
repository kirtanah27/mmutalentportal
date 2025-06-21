<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user']['user_id'];
$view_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;
$is_own_profile = $current_user_id === $view_user_id;
$isEditing = isset($_GET['edit']) && $is_own_profile;

$msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_own_profile) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $bio = trim($_POST['bio']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $profile_image = null;
    if (isset($_FILES['cropped_image']) && $_FILES['cropped_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cropped_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid("pfp_") . "." . $ext;
        $target_path = "uploads/pfp/" . $filename;
        if (!is_dir("uploads/pfp")) mkdir("uploads/pfp", 0777, true);
        move_uploaded_file($_FILES['cropped_image']['tmp_name'], $target_path);
        $profile_image = $target_path;
    }

    $cv_path = null;
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
        $cv_name = uniqid("cv_") . "." . $ext;
        $cv_path = "uploads/cv/" . $cv_name;
        if (!is_dir("uploads/cv")) mkdir("uploads/cv", 0777, true);
        move_uploaded_file($_FILES['cv_file']['tmp_name'], $cv_path);
    }

    $sql = "UPDATE users SET username = ?, email = ?, bio = ?, is_public = ?";
    $params = [$username, $email, $bio, $is_public];

    if ($profile_image) {
        $sql .= ", profile_image = ?";
        $params[] = $profile_image;
    }

    if ($cv_path) {
        $sql .= ", profile_picture = ?";
        $params[] = $cv_path;
    }

    $sql .= " WHERE user_id = ?";
    $params[] = $current_user_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header("Location: profile.php?user_id=" . $current_user_id);
    exit;
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$view_user_id]);
$user = $stmt->fetch();

if (!$user || (!$user['is_public'] && !$is_own_profile)) {
    echo "❌ User not found or profile is private.";
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<div class="container auth-box">
    <h2><?= $is_own_profile ? 'My Profile' : htmlspecialchars($user['username']) . "'s Profile" ?></h2>
    <?php if ($msg): ?><p class="message"><?= $msg ?></p><?php endif; ?>

    <div class="profile-pic-wrapper">
        <?php if ($user['profile_image']): ?>
            <img src="<?= $user['profile_image'] ?>" alt="Profile Picture" class="glow-pfp" id="currentPfp">
        <?php else: ?>
            <div class="placeholder-pfp" style="width: 120px; height: 120px; background: #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 40px;">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_own_profile && $isEditing): ?>
            <div class="edit-pfp-icon" onclick="document.getElementById('pfpInput').click()">✏️</div>
        <?php endif; ?>
    </div>

    <?php if ($is_own_profile && !$isEditing): ?>
        <a href="profile.php?edit=true" class="btn outline">Edit Profile</a>
    <?php endif; ?>

    <?php if ($isEditing): ?>
    <form action="profile.php" method="POST" enctype="multipart/form-data" style="margin-top:20px;">
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
        <textarea name="bio" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio']) ?></textarea><br>

        <label>Upload CV:</label>
        <input type="file" name="cv_file" accept=".pdf,.doc,.docx"><br>

        <input type="file" name="cropped_image" id="pfpInput" accept="image/*" style="display:none;" onchange="loadCropper(event)">

        <div class="toggle-switch" style="display: flex; align-items: center; gap: 10px;">
            <span class="switch-label">Make profile public</span>
            <label class="switch">
                <input type="checkbox" name="is_public" <?= $user['is_public'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
        </div>

        <button type="submit" class="btn gold">Save Changes</button>
    </form>
    <?php else: ?>
        <p class="profile-info"><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p class="profile-info"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p class="profile-info"><strong>Bio:</strong> <?= $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : "No bio yet." ?></p>

        <?php if ($user['profile_picture']): ?>
            <p class="profile-info"><strong>CV:</strong> <a href="<?= $user['profile_picture'] ?>" download>Download CV</a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Cropper Modal -->
<div id="cropperModal" class="modal hidden">
    <div class="modal-content">
        <span class="close" onclick="closeCropperModal()">&times;</span>
        <img id="cropImage">
        <button onclick="cropAndUpload()" class="btn gold">Crop & Save</button>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
<script>
let cropper;
function loadCropper(event) {
    const modal = document.getElementById('cropperModal');
    const image = document.getElementById('cropImage');
    image.src = URL.createObjectURL(event.target.files[0]);
    modal.classList.remove('hidden');

    image.onload = () => {
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            background: false
        });
    };
}

function cropAndUpload() {
    cropper.getCroppedCanvas().toBlob(blob => {
        const file = new File([blob], "cropped.png", { type: "image/png" });
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById("pfpInput").files = dt.files;
        closeCropperModal();
    });
}

function closeCropperModal() {
    document.getElementById('cropperModal').classList.add('hidden');
    cropper.destroy();
}
</script>

<?php include 'includes/footer.php'; ?>
