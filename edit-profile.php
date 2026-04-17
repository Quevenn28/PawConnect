<?php
require_once 'db.php';
require_login();

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $facebook  = trim($_POST['facebook']  ?? '');
    $address   = trim($_POST['address']   ?? '');

    if (!$full_name) {
        $error = 'Full name is required.';
    } elseif (!$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link.';
    } else {
        $pdo->prepare("UPDATE users SET full_name=?, phone=?, facebook=?, address=? WHERE id=?")
            ->execute([$full_name, $phone, $facebook, $address, $_SESSION['user_id']]);

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

    <form method="POST">
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

      <div style="display:flex;gap:10px">
        <a href="dashboard.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
