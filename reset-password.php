<?php
// ============================================================
//  reset-password.php
//  Password reset completion page
// ============================================================
require_once 'autoload.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = false;
$token = $_GET['token'] ?? '';

$userObj = new User($pdo);
$user = $userObj->verifyPasswordResetToken($token);

if (!$user) {
    $error = 'Invalid or expired password reset link. Please <a href="forgot-password.php">request a new one</a>.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    verify_csrf();

    $new_password   = $_POST['new_password'] ?? '';
    $confirm_pw     = $_POST['confirm_password'] ?? '';

    if (!$new_password) {
        $error = 'Please enter a new password.';
    } elseif ($new_password !== $confirm_pw) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $userObj->resetPassword($user['id'], $new_password);
        $success = true;
    }
}

require_once 'views/layout/layout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — PawConnect</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1><?= $success ? 'Password Reset!' : 'Create New Password' ?></h1>
      <p><?= $success ? 'Your password has been successfully reset.' : 'Enter your new password below.' ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fas fa-check"></i> Your password has been reset successfully. You can now log in with your new password.</div>
      <div style="margin-top:16px">
        <a href="login.php" class="btn btn-primary w-full text-center">Go to Login</a>
      </div>
    <?php elseif (!$error): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>New Password <span class="req">*</span></label>
        <input type="password" name="new_password" placeholder="Min. 8 characters" required autofocus>
      </div>

      <div class="form-group">
        <label>Confirm Password <span class="req">*</span></label>
        <input type="password" name="confirm_password" placeholder="Repeat new password" required>
      </div>

      <div style="font-size:12px;color:var(--gray-4);margin-bottom:16px">
        <span class="req">*</span> Required fields
      </div>

      <div style="display:flex;gap:10px">
        <a href="login.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">Reset Password</button>
      </div>
    </form>
    <?php else: ?>
      <div style="margin-top:16px">
        <a href="forgot-password.php" class="btn btn-primary w-full text-center">← Request New Link</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php footer_bar(); ?>
</body>
</html>
