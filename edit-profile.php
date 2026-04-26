<?php
require_once 'db.php';
require_login();

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

// Ensure profile photo column exists for this installation
try {
    $pdo->query("SELECT profile_photo FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(200)");
    } catch (PDOException $e) {
        // ignore if unable to migrate automatically
    }
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $facebook  = trim($_POST['facebook']  ?? '');
    $address   = trim($_POST['address']   ?? '');
    $profile_photo = $user['profile_photo'] ?? null;
    $photo_uploaded = false;

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['profile_photo'];
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Unable to upload profile photo. Please try again.';
        } elseif (!in_array($ext, $allowed)) {
            $error = 'Invalid photo format. Use JPG, PNG, or GIF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Profile photo must be under 5MB.';
        } elseif (!getimagesize($file['tmp_name'])) {
            $error = 'Invalid image file.';
        } else {
            if (!is_dir('uploads/users')) {
                mkdir('uploads/users', 0755, true);
            }
            $profile_photo = uniqid('user_') . '.' . $ext;
            move_uploaded_file($file['tmp_name'], 'uploads/users/' . $profile_photo);
            $photo_uploaded = true;
        }
    }

    if (!$full_name) {
        $error = 'Full name is required.';
    } elseif (!$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link.';
    }

    if (!$error) {
        $update_sql = "UPDATE users SET full_name=?, phone=?, facebook=?, address=?";
        $params = [$full_name, $phone, $facebook, $address];

        if ($photo_uploaded) {
            $update_sql .= ", profile_photo=?";
            $params[] = $profile_photo;
        }

        $update_sql .= " WHERE id=?";
        $params[] = $_SESSION['user_id'];

        $pdo->prepare($update_sql)->execute($params);

        $_SESSION['user_name'] = $full_name;
        $success = 'Profile updated successfully!';

        // Refresh user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile — PawConnect</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links"><a href="dashboard.php">← Dashboard</a></div>
</nav>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw">✏️</div>
      <h1>Edit Profile</h1>
      <p>Update your contact information</p>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label>📞 Phone Number</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+63 9XX XXX XXXX">
      </div>
        <div class="form-group">
        <label>📘 Facebook URL</label>
        <input type="url" name="facebook" value="<?= htmlspecialchars($user['facebook'] ?? '') ?>" placeholder="https://facebook.com/yourname">
      </div>
      <div class="form-group">
        <label>📍 Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="City, Province">
      </div>
      <div class="form-group">
        <label>Profile Picture</label>
        <?php if (!empty($user['profile_photo'])): ?>
          <div class="profile-photo-preview"><img src="uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo"></div>
        <?php else: ?>
          <div class="profile-photo-preview"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <input type="file" name="profile_photo" accept="image/*">
        <small style="color:#6b7280;">Upload JPG, PNG or GIF under 5MB.</small>
      </div>

      <div style="display:flex;gap:10px">
        <a href="dashboard.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($success)): ?>
<script>
Swal.fire({
  title: "Success",
  text: "Your account has been updated successfully!",
  icon: "success",
  draggable: true
}).then(() => {
  window.location.href = "dashboard.php?welcome=1";
});
</script>
<?php endif; ?>

</body>
</html>
