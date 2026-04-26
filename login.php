<?php
require_once 'db.php';
if (is_logged_in()) { header("Location: dashboard.php"); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password'] ?? '';

    if ($login && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? OR username=? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['username']  = $user['username'];
            header("Location: dashboard.php");
            exit;
        }
    }
    $error = 'Invalid email/username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — PawConnect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="index.php">Return to Home</a>
  </div>
</nav>

<div class="form-page">
  <div class="form-box">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>Welcome Back!</h1>
      <p>Sign in to your PawConnect account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Email or Username</label>
        <input type="text" name="login" placeholder="you@email.com" autofocus required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required>
      </div>

      <button type="submit" class="btn btn-primary w-full">Sign In</button>

      <div class="form-footer">
        No account yet? <a href="register.php">Create one free</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
