<?php
// ============================================================
//  forgot-password.php
//  Password reset request page
// ============================================================
require_once 'autoload.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login = trim($_POST['login'] ?? '');

    if (!$login) {
        $error = 'Please enter your email or username.';
    } else {
        $userObj = new User($pdo);
        $user = $userObj->findByEmailOrUsername($login);

        if ($user) {
            $token = $userObj->generatePasswordReset($user['id']);
            $message = 'Password reset link sent! Copy the token below or check your email inbox.';
            
            // In a real application, you would send this via email
            // For now, we display it to the user
            $reset_link = "reset-password.php?token=" . urlencode($token);
        } else {
            // Don't reveal if account exists (security best practice)
            $message = 'If an account exists with that email or username, a reset link has been sent.';
        }
    }
}

require_once 'views/layout/layout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — PawConnect</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw"><i class="fas fa-key"></i></div>
      <h1>Reset Password</h1>
      <p>Enter your email or username to reset your password</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
      <div class="alert alert-success"><i class="fas fa-check"></i> <?= htmlspecialchars($message) ?></div>
      <?php if (isset($reset_link)): ?>
        <div style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-top:12px">
          <p style="font-size:12px;color:var(--gray-4);margin-bottom:6px">Your reset token (valid for 24 hours):</p>
          <code style="background:white;padding:8px;border-radius:4px;display:block;word-break:break-all;font-size:11px"><?= htmlspecialchars($token) ?></code>
          <p style="font-size:12px;color:var(--gray-4);margin-top:8px">👉 <a href="<?= htmlspecialchars($reset_link) ?>" style="color:var(--blue)">Click here to reset your password</a></p>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!$message): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Email or Username <span class="req">*</span></label>
        <input type="text" name="login" placeholder="your@email.com or username" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autofocus>
      </div>

      <div style="display:flex;gap:10px;margin-top:16px">
        <a href="login.php" class="btn btn-gray">← Back to Login</a>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
      </div>
    </form>
    <?php else: ?>
    <div style="margin-top:16px">
      <a href="login.php" class="btn btn-primary w-full text-center">← Back to Login</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php footer_bar(); ?>
</body>
</html>
