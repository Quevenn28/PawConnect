<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/layout.php';
require_login();

$userObj = new User($pdo);
$user    = $userObj->findById($_SESSION['user_id']);
$error   = '';
$success = false;

require_once '../../controllers/users/update.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Profile — PawConnect</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>Edit Profile</h1>
      <p>Update your contact information</p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Full Name <span class="req">*</span></label>
        <input type="text" name="full_name" maxlength="50" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        <!-- <small style="color:var(--gray-4)">Max 50 characters</small> -->
      </div>
      <div class="form-group">
        <label>Sex <span class="req">*</span></label>
        <select name="sex" required>
          <option value="Male" <?= ($user['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($user['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Prefer not to say" <?= ($user['sex'] ?? '') === 'Prefer not to say' ? 'selected' : '' ?>>Prefer not to say</option>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-phone"></i> Phone Number <span class="req">*</span></label>
        <input type="text" name="phone" maxlength="11" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09123456789">
        <!-- <small style="color:var(--gray-4)">11 digits starting with 09 (numbers only)</small> -->
      </div>
      <div class="form-group">
        <label><i class="fab fa-facebook"></i> Facebook URL <span class="req">*</span></label>
        <input type="url" name="facebook" value="<?= htmlspecialchars($user['facebook'] ?? '') ?>" placeholder="https://facebook.com/yourname">
        <!-- <small style="color:var(--gray-4)">Must start with https://facebook.com/</small> -->
      </div>
      <div class="form-group">
        <label><i class="fas fa-map-marker-alt"></i> Address <span class="req">*</span></label>
        <input type="text" name="address" maxlength="100" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="City, Province" required>
        <!-- <small style="color:var(--gray-4)">Max 100 characters. Format: Barangay, City, Province</small> -->
      </div>

      <div class="form-divider"><span>Change Password</span></div>
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" placeholder="Enter current password">
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Min. 6 characters">
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat new password">
      </div>

      <div class="form-group">
        <label>Profile Picture</label>
        <?php if (!empty($user['profile_photo'])): ?>
          <img src="../../uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;display:block;margin-bottom:8px">
        <?php endif; ?>
        <input type="file" name="profile_photo" accept="image/*">
        <small style="color:var(--gray-4)">JPG, PNG or GIF under 5MB</small>
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
