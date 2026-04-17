<?php
require_once 'db.php';
if (is_logged_in()) { header("Location: dashboard.php"); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $facebook  = trim($_POST['facebook']  ?? '');
    $address   = trim($_POST['address']   ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    // Simple validation
    if (!$full_name || !$username || !$email || !$password) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link so adopters can contact you.';
    } else {
        // Check if email or username already exists
        $check = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $error = 'Email or username already taken.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, phone, facebook, address) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$full_name, $username, $email, $hashed, $phone, $facebook, $address]);

            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id']   = $user_id;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['username']  = $username;

            header("Location: dashboard.php?welcome=1");
            
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — PawConnect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="login.php">Already a member? Login →</a>
  </div>
</nav>

<div class="form-page">
  <div class="form-box form-box-wide">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>Create Your Account</h1>
      <p>Join PawConnect to list or adopt pets</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-section">Personal Info</div>
      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" placeholder="Juan dela Cruz" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" placeholder="juanpaws" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address *</label>
        <input type="email" name="email" placeholder="juan@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="confirm" placeholder="Repeat password" required>
        </div>
      </div>

      <div class="form-section">Contact Info <span style="font-weight:400;color:#9ca3af;font-size:11px">(at least one required)</span></div>

      <div class="form-group">
        <label>📞 Phone / Mobile Number</label>
        <input type="tel" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>📘 Facebook Profile URL</label>
        <input type="url" name="facebook" placeholder="https://facebook.com/yourname" value="<?= htmlspecialchars($_POST['facebook'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>📍 Address / Location</label>
        <input type="text" name="address" placeholder="City, Province" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
      </div>

      <button type="submit" class="btn btn-primary w-full" style="margin-top:8px">Create Account 🐾</button>

      <div class="form-footer">
        Already have an account? <a href="login.php">Sign in here</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>
