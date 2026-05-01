<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_login();

$userObj = new User($pdo);
$user    = $userObj->findById($_SESSION['user_id']);
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name    = trim($_POST['full_name'] ?? '');
    $phone        = trim($_POST['phone']     ?? '');
    $facebook     = trim($_POST['facebook']  ?? '');
    $address      = trim($_POST['address']   ?? '');
    $profile_photo = null;

    if (!$full_name) {
        $error = 'Full name is required.';
    } elseif (!$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link.';
    } elseif (!$address) {
        $error = 'Address is required.';
    } else {
        // Handle photo upload
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $file    = $_FILES['profile_photo'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Photo upload failed. Please try again.';
            } elseif (!in_array($ext, $allowed)) {
                $error = 'Invalid format. Use JPG, PNG, or GIF.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Photo must be under 5MB.';
            } else {
                if (!is_dir('../../uploads/users')) mkdir('../../uploads/users', 0755, true);
                $profile_photo = uniqid('user_') . '.' . $ext;
                move_uploaded_file($file['tmp_name'], '../../uploads/users/' . $profile_photo);
            }
        }

        if (!$error) {
            $userObj->update(
                $_SESSION['user_id'],
                $full_name, $phone, $facebook, $address,
                $profile_photo
            );
            $_SESSION['user_name'] = $full_name;
            $success = true;
            $user    = $userObj->findById($_SESSION['user_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Profile — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw">✏️</div>
      <h1>Edit Profile</h1>
      <p>Update your contact information</p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Full Name <span class="req">*</span></label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
      </div>
      <div class="form-group">
        <label>📞 Phone Number <span class="req">*</span></label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+63 9XX XXX XXXX">
      </div>
      <div class="form-group">
        <label>📘 Facebook URL <span class="req">*</span></label>
        <input type="url" name="facebook" value="<?= htmlspecialchars($user['facebook'] ?? '') ?>" placeholder="https://facebook.com/yourname">
      </div>
      <div class="form-group">
        <label>📍 Address <span class="req">*</span></label>
        <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="City, Province" required>
      </div>
      <div class="form-group">
        <label>Profile Picture</label>
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="../../uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;margin-bottom:8px">
        <?php endif; ?>
        <input type="file" name="profile_photo" accept="image/*">
        <small style="color:var(--gray-4)">JPG, PNG or GIF under 5MB</small>
      </div>

      <div style="font-size:12px;color:var(--gray-4);margin-bottom:10px">
        <span class="req">*</span> Required. At least one of Phone or Facebook is required.
      </div>

      <div style="display:flex;gap:10px">
        <a href="index.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php if ($success): ?>
<script>
Swal.fire({ title:'Saved!', text:'Your profile has been updated.', icon:'success', confirmButtonColor:'#f97316' })
  .then(() => window.location.href = 'index.php');
</script>
<?php endif; ?>

<?php footer_bar(); ?>
</body>
</html>
