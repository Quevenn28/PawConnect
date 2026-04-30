<?php
// ============================================================
//  login.php
// ============================================================
require_once 'autoload.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login    = trim($_POST['login']    ?? '');
    $password = $_POST['password']      ?? '';

    if ($login && $password) {
        $userObj = new User($pdo);
        $user    = $userObj->findByEmailOrUsername($login);

        if ($user && password_verify($password, $user['password'])) {

            // --- BAN CHECK ---
            if (check_ban($user, $pdo)) {
                $ban_msg = "Your account has been suspended.";

                if ($user['ban_until'] !== null) {
                    $until   = new DateTime($user['ban_until']);
                    $now     = new DateTime();
                    $diff    = $now->diff($until);

                    $parts = [];
                    if ($diff->days > 0) $parts[] = $diff->days . 'd';
                    if ($diff->h > 0)    $parts[] = $diff->h . 'h';
                    if ($diff->i > 0)    $parts[] = $diff->i . 'm';

                    $remaining = implode(' ', $parts) ?: 'less than a minute';
                    $ban_msg   = "Your account has been temporarily suspended. Time remaining: <strong>$remaining</strong>.";
                } else {
                    $ban_msg = "Your account has been permanently suspended.";
                }

                if ($user['ban_reason']) {
                    $ban_msg .= "<br><small>Reason: " . htmlspecialchars($user['ban_reason']) . "</small>";
                }

                $error = $ban_msg;

            } else {
                // --- LOGIN SUCCESS ---
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: views/admin/dashboard.php");
                } elseif ($user['role'] === 'moderator') {
                    header("Location: views/admin/dashboard.php");
                } else {
                    header("Location: views/users/index.php");
                }
                exit;
            }

        } else {
            $error = 'Invalid email/username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — PawConnect</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="index.php">← Home</a>
    <a href="register.php" class="btn btn-primary btn-sm">Join Free</a>
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
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label>Email or Username</label>
        <input
          type="text"
          name="login"
          placeholder="you@email.com or username"
          value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
          autofocus
          required
        >
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="password-wrap">
          <input type="password" name="password" id="passwordInput" placeholder="Your password" required>
          <button type="button" class="toggle-pw" onclick="togglePassword()">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-full">Sign In 🐾</button>

      <div class="form-footer">
        No account yet? <a href="register.php">Create one free</a>
      </div>
    </form>

  </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}

<?php if ($error && str_contains($error, 'suspended')): ?>
Swal.fire({
    title: 'Account Suspended',
    html: `<?= addslashes($error) ?>`,
    icon: 'error',
    confirmButtonColor: '#f97316',
    confirmButtonText: 'Okay'
});
<?php endif; ?>
</script>

</body>
</html>
