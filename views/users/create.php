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

      <!-- Hidden address field stores formatted value -->
      <input type="hidden" name="address" id="addressField" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required>

      <div class="form-row">
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Province <span class="req">*</span></label>
          <select id="province" name="province" required>
            <option value="">Select Province</option>
          </select>
          <small class="text-muted" style="display: none;" id="provinceLoading">Loading provinces...</small>
        </div>
        <div class="form-group">
          <label>Municipality / City <span class="req">*</span></label>
          <select id="municipality" name="municipality" required disabled>
            <option value="">Select after choosing province</option>
          </select>
          <small class="text-muted" style="display: none;" id="municipalityLoading">Loading cities...</small>
        </div>
      </div>

      <div class="form-group">
        <label>Barangay <span class="req">*</span></label>
        <select id="barangay" name="barangay" required disabled>
          <option value="">Select after choosing municipality</option>
        </select>
        <small class="text-muted" style="display: none;" id="barangayLoading">Loading barangays...</small>
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

// PH-API Integration for Cascading Address Dropdowns
const PH_API_BASE = 'https://psgc.gitlab.io/api/';
const provinceSelect = document.getElementById('province');
const municipalitySelect = document.getElementById('municipality');
const barangaySelect = document.getElementById('barangay');
const addressField = document.getElementById('addressField');

// Load provinces on page load
async function loadProvinces() {
    try {
        document.getElementById('provinceLoading').style.display = 'inline';
        const response = await fetch(`${PH_API_BASE}provinces`);
        const provinces = await response.json();
        
        provinces.sort((a, b) => a.name.localeCompare(b.name));
        
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.code;
            option.textContent = province.name;
            option.dataset.name = province.name;
            provinceSelect.appendChild(option);
        });
        document.getElementById('provinceLoading').style.display = 'none';
    } catch (error) {
        console.error('Error loading provinces:', error);
        alert('Failed to load provinces. Please refresh the page.');
    }
}

// Load municipalities when province is selected
provinceSelect.addEventListener('change', async function () {
    const provinceName = this.options[this.selectedIndex].dataset.name;
    municipalitySelect.innerHTML = '<option value="">Loading...</option>';
    municipalitySelect.disabled = true;
    barangaySelect.innerHTML = '<option value="">Select after choosing municipality</option>';
    barangaySelect.disabled = true;
    addressField.value = '';
    
    if (!this.value) {
        municipalitySelect.innerHTML = '<option value="">Select after choosing province</option>';
        return;
    }
    
    try {
        document.getElementById('municipalityLoading').style.display = 'inline';
        const response = await fetch(`${PH_API_BASE}provinces/${this.value}/cities-municipalities`);
        const municipalities = await response.json();
        
        municipalities.sort((a, b) => a.name.localeCompare(b.name));
        
        municipalitySelect.innerHTML = '<option value="">Select Municipality / City</option>';
        municipalities.forEach(mun => {
            const option = document.createElement('option');
            option.value = mun.code;
            option.textContent = mun.name;
            option.dataset.name = mun.name;
            municipalitySelect.appendChild(option);
        });
        municipalitySelect.disabled = false;
        document.getElementById('municipalityLoading').style.display = 'none';
    } catch (error) {
        console.error('Error loading municipalities:', error);
        municipalitySelect.innerHTML = '<option value="">Error loading data</option>';
    }
});

// Load barangays when municipality is selected
municipalitySelect.addEventListener('change', async function () {
    const municipalityName = this.options[this.selectedIndex].dataset.name;
    const provinceName = provinceSelect.options[provinceSelect.selectedIndex].dataset.name;
    barangaySelect.innerHTML = '<option value="">Loading...</option>';
    barangaySelect.disabled = true;
    addressField.value = '';
    
    if (!this.value) {
        barangaySelect.innerHTML = '<option value="">Select after choosing municipality</option>';
        return;
    }
    
    try {
        document.getElementById('barangayLoading').style.display = 'inline';
        const response = await fetch(`${PH_API_BASE}cities-municipalities/${this.value}/barangays`);
        const barangays = await response.json();
        
        barangays.sort((a, b) => a.name.localeCompare(b.name));
        
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangays.forEach(barangay => {
            const option = document.createElement('option');
            option.value = barangay.code;
            option.textContent = barangay.name;
            option.dataset.name = barangay.name;
            barangaySelect.appendChild(option);
        });
        barangaySelect.disabled = false;
        document.getElementById('barangayLoading').style.display = 'none';
    } catch (error) {
        console.error('Error loading barangays:', error);
        barangaySelect.innerHTML = '<option value="">Error loading data</option>';
    }
});

// Update hidden address field when barangay is selected
barangaySelect.addEventListener('change', function () {
    if (this.value) {
        const barangayName = this.options[this.selectedIndex].dataset.name;
        const municipalityName = municipalitySelect.options[municipalitySelect.selectedIndex].dataset.name;
        const provinceName = provinceSelect.options[provinceSelect.selectedIndex].dataset.name;
        
        // Format: Barangay, Municipality, Province
        addressField.value = `${barangayName}, ${municipalityName}, ${provinceName}`;
    } else {
        addressField.value = '';
    }
});

// Load provinces when page loads
document.addEventListener('DOMContentLoaded', loadProvinces);
</script>

</body>
</html>
