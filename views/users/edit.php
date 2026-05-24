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
      <!-- Hidden address field stores formatted value -->
      <input type="hidden" name="address" id="addressField" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>

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

<script>
// PH-API Integration for Cascading Address Dropdowns
const PH_API_BASE = 'https://psgc.gitlab.io/api/';
const provinceSelect = document.getElementById('province');
const municipalitySelect = document.getElementById('municipality');
const barangaySelect = document.getElementById('barangay');
const addressField = document.getElementById('addressField');
const currentAddress = addressField.value; // Parse existing address to prepopulate

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
        
        // Pre-populate if user has existing address
        if (currentAddress) {
            await prepopulateAddresses();
        }
    } catch (error) {
        console.error('Error loading provinces:', error);
        alert('Failed to load provinces. Please refresh the page.');
    }
}

// Parse stored address and pre-populate dropdowns
async function prepopulateAddresses() {
    // Address format: "Barangay, Municipality, Province"
    const parts = currentAddress.split(', ').map(p => p.trim());
    if (parts.length !== 3) return;
    
    const [barangayName, municipalityName, provinceName] = parts;
    
    // Find and select province
    const provinceOption = Array.from(provinceSelect.options).find(opt => opt.dataset.name === provinceName);
    if (provinceOption) {
        provinceSelect.value = provinceOption.value;
        await loadMunicipalities(provinceOption.value, municipalityName, barangayName);
    }
}

// Load municipalities for prepopulation
async function loadMunicipalities(provinceCode, municipalityName, barangayName) {
    try {
        const response = await fetch(`${PH_API_BASE}provinces/${provinceCode}/cities-municipalities`);
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
        
        // Find and select municipality
        const municipalityOption = Array.from(municipalitySelect.options).find(opt => opt.dataset.name === municipalityName);
        if (municipalityOption) {
            municipalitySelect.value = municipalityOption.value;
            await loadBarangays(municipalityOption.value, barangayName);
        }
    } catch (error) {
        console.error('Error loading municipalities:', error);
    }
}

// Load barangays for prepopulation
async function loadBarangays(municipalityCode, barangayName) {
    try {
        const response = await fetch(`${PH_API_BASE}cities-municipalities/${municipalityCode}/barangays`);
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
        
        // Find and select barangay
        const barangayOption = Array.from(barangaySelect.options).find(opt => opt.dataset.name === barangayName);
        if (barangayOption) {
            barangaySelect.value = barangayOption.value;
        }
    } catch (error) {
        console.error('Error loading barangays:', error);
    }
}

// Load municipalities when province is selected
provinceSelect.addEventListener('change', async function () {
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

<?php footer_bar(); ?>
</body>
</html>
