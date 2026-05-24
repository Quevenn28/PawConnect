<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — PawConnect</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="index.php">← Home</a>
    <a href="login.php">Sign In</a>
  </div>
</nav>

<div class="form-page">
  <div class="form-box form-box-wide">

    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>Create Your Account</h1>
      <p>Join PawConnect — list or adopt pets for free</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="registerForm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <?php $maxBirthdate = date('Y-m-d', strtotime('-18 years')); ?>


      <!-- Personal Info -->
      <div class="form-section">Personal Info</div>
      <div class="form-row">
        <div class="form-group">
          <label>Full Name <span class="req">*</span></label>
          <input
            type="text" name="full_name"
            placeholder="Juan dela Cruz"
            maxlength="50"
            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
            required
          >
          <!-- <small class="text-muted">Max 50 characters</small> -->
        </div>
        <div class="form-group">
          <label>Username <span class="req">*</span></label>
          <input
            type="text" name="username"
            placeholder="juanpaws"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            required
          >
          <!-- <small class="text-muted">Letters, numbers, underscores (3-60 chars)</small> -->
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Birthdate <span class="req">*</span></label>
          <input
            type="date" name="birthdate"
            max="<?= $maxBirthdate ?>"
            value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>"
            required
          >
          <!-- <small class="text-muted">Must be 18-116 years old</small> -->
        </div>
        <div class="form-group">
          <label>Sex <span class="req">*</span></label>
          <select name="sex" required>
            <option value="Male" <?= ($_POST['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($_POST['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Prefer not to say" <?= ($_POST['sex'] ?? '') === 'Prefer not to say' || !isset($_POST['sex']) ? 'selected' : '' ?>>Prefer not to say</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address <span class="req">*</span></label>
        <input
          type="email" name="email"
          placeholder="juan@email.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required
        >
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Password <span class="req">*</span></label>
          <div class="password-wrap">
            <input type="password" name="password" id="pw1" placeholder="Min. 6 characters" required>
            <button type="button" class="toggle-pw" onclick="togglePw('pw1')"><i class="fas fa-eye"></i></button>
          </div>
        </div>
        <div class="form-group">
          <label>Confirm Password <span class="req">*</span></label>
          <div class="password-wrap">
            <input type="password" name="confirm" id="pw2" placeholder="Repeat password" required>
            <button type="button" class="toggle-pw" onclick="togglePw('pw2')"><i class="fas fa-eye"></i></button>
          </div>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="form-section">
        Contact Info
        <span class="form-section-note">(All required — so adopters can reach you)</span>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label><i class="fas fa-phone"></i> Phone / Mobile <span class="req">*</span></label>
          <input
            type="text" name="phone"
            placeholder="09123456789"
            maxlength="11"
            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
            required
          >
          <!-- <small class="text-muted">11 digits starting with 09 (numbers only)</small> -->
        </div>
        <div class="form-group">
          <label><i class="fab fa-facebook"></i> Facebook Profile URL <span class="req">*</span></label>
          <input
            type="url" name="facebook"
            placeholder="https://facebook.com/yourname"
            value="<?= htmlspecialchars($_POST['facebook'] ?? '') ?>"
            required
          >
          <!-- <small class="text-muted">Must start with https://facebook.com/</small> -->
        </div>
      </div>

      <div class="form-group">
        <label><i class="fas fa-map-marker-alt"></i> Address / Location <span class="req">*</span></label>
        <input
          type="text" name="address"
          placeholder="City, Province"
          maxlength="100"
          value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
          required
        >
        <!-- <small class="text-muted">Max 100 characters. Format: Barangay, City, Province</small> -->
      </div>

      <!-- Points Notice -->
      <div class="points-notice">
        <i class="fas fa-gift"></i> You'll receive <strong><?= PTS_REGISTER ?> welcome points</strong> for creating your account!
      </div>

      <div class="form-group" style="margin-top:12px">
        <label class="checkbox-label">
          <input type="checkbox" name="agree_terms" value="1" required>
          I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="terms.php#privacy" target="_blank">Privacy Policy</a>.
        </label>
      </div>

      <button type="submit" class="btn btn-primary w-full" style="margin-top:12px">
        Create Account 🐾
      </button>

      <div class="form-footer">
        Already have an account? <a href="login.php">Sign in here</a>
      </div>
    </form>

  </div>
</div>

<?php if ($success): ?>
<script>
Swal.fire({
    title: '🐾 Welcome to PawConnect!',
    html: 'Your account has been created!<br><strong>+<?= PTS_REGISTER ?> welcome points</strong> have been added to your profile.',
    icon: 'success',
    confirmButtonColor: '#f97316',
    confirmButtonText: 'Go to Dashboard'
}).then(() => {
    window.location.href = 'views/users/index.php?welcome=1';
});
</script>
<?php endif; ?>

<script>
function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Live password match check
document.getElementById('pw2').addEventListener('input', function () {
    const pw1 = document.getElementById('pw1').value;
    this.style.borderColor = this.value === pw1 ? '#16a34a' : '#dc2626';
});
</script>

</body>
</html>
