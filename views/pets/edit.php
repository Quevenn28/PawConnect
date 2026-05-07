<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_once '../../controllers/pets/update.php';

$age_value = '';
$age_unit  = 'month';
$existing_vaccinated = 'No';
$existing_spayed_neutered = 'No';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($pet['age'])) {
    if (preg_match('/^(\d+)\s*(day|week|month|year)s?/i', $pet['age'], $matches)) {
        $age_value = $matches[1];
        $age_unit  = strtolower($matches[2]);
    }
    $existing_vaccinated = ($pet['vaccinated'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
    $existing_spayed_neutered = ($pet['spayed_neutered'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Pet Listing — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box form-box-wide">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>Edit Pet Listing</h1>
      <p>Update your pet's information and keep the listing current.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-section">Basic Info</div>
      <div class="form-row">
        <div class="form-group">
          <label>Pet Name <span class="req">*</span></label>
          <input type="text" name="name" placeholder="e.g. Buddy" value="<?= htmlspecialchars($_POST['name'] ?? $pet['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Species <span class="req">*</span></label>
          <select name="species" required>
            <?php foreach (['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'] as $sp): ?>
              <option value="<?= $sp ?>" <?= ($_POST['species'] ?? $pet['species']) === $sp ? 'selected' : '' ?>><?= $sp ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Breed <span class="req">*</span></label>
          <input type="text" name="breed" placeholder="e.g. Labrador" value="<?= htmlspecialchars($_POST['breed'] ?? $pet['breed']) ?>" required>
        </div>
        <div class="form-group">
          <label>Age <span class="req">*</span></label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <input type="number" min="0" name="age_value" value="<?= htmlspecialchars($_POST['age_value'] ?? $age_value) ?>" placeholder="3" required>
            <select name="age_unit" required>
              <option value="">Unit</option>
              <option value="day" <?= ($_POST['age_unit'] ?? $age_unit) === 'day' ? 'selected' : '' ?>>Days</option>
              <option value="week" <?= ($_POST['age_unit'] ?? $age_unit) === 'week' ? 'selected' : '' ?>>Weeks</option>
              <option value="month" <?= ($_POST['age_unit'] ?? $age_unit) === 'month' ? 'selected' : '' ?>>Months</option>
              <option value="year" <?= ($_POST['age_unit'] ?? $age_unit) === 'year' ? 'selected' : '' ?>>Years</option>
            </select>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Sex <span class="req">*</span></label>
        <select name="gender" required>
          <?php foreach (['Male','Female','Unknown'] as $g): ?>
            <option value="<?= $g ?>" <?= ($_POST['gender'] ?? $pet['gender']) === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>About this Pet <span class="req">*</span></label>
        <textarea name="description" rows="4" placeholder="Personality, habits, ideal home…" required><?= htmlspecialchars($_POST['description'] ?? $pet['description']) ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group" style="padding-top:8px;">
          <label class="check-label"><input type="checkbox" name="vaccinated" value="1" <?= (isset($_POST['vaccinated']) ? 'checked' : ($existing_vaccinated === 'Yes' ? 'checked' : '')) ?>> Vaccinated</label>
        </div>
        <div class="form-group" style="padding-top:8px;">
          <label class="check-label"><input type="checkbox" name="spayed_neutered" value="1" <?= (isset($_POST['spayed_neutered']) ? 'checked' : ($existing_spayed_neutered === 'Yes' ? 'checked' : '')) ?>> Spayed / Neutered</label>
        </div>
      </div>
      <div class="form-group">
        <label>Medical Notes <span class="text-gray">(optional)</span></label>
        <textarea name="health_info" rows="3" placeholder="Additional medical details or special care notes…"><?= htmlspecialchars($_POST['health_info'] ?? $pet['health_info']) ?></textarea>
      </div>
      <div class="form-group">
        <label>Good with Children <span class="req">*</span></label>
        <select name="good_with_children" required>
          <?php foreach (['Unknown','Yes','No'] as $option): ?>
            <option value="<?= $option ?>" <?= ($_POST['good_with_children'] ?? $pet['good_with_children']) === $option ? 'selected' : '' ?>><?= $option ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-section">Photo</div>
      <div class="form-group">
        <div class="file-upload">
          <input type="file" name="photo" id="photoInput" accept="image/*" onchange="previewPhoto(this)">
          <label for="photoInput" class="file-upload-btn">📷 Choose New Photo</label>
          <span class="file-name" id="fileName">No file chosen</span>
        </div>
        <?php if (!empty($pet['photo'])): ?>
          <div class="photo-preview" style="margin-top:10px">
            <img id="previewImg" src="../../uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="Current pet photo" style="max-width:200px;border-radius:8px">
          </div>
          <div style="font-size:12px;color:var(--gray-4);margin-top:6px">Leave blank to keep the existing photo.</div>
        <?php endif; ?>
      </div>

      <div style="font-size:12px;color:var(--gray-4);margin-top:4px">
        <span class="req">*</span> Required fields
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <a href="../users/index.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function previewPhoto(input) {
    const file = input.files[0];
    document.getElementById('fileName').textContent = file ? file.name : 'No file chosen';
    if (file) {
        const reader = new FileReader();
        reader.onload = e => {
            let img = document.getElementById('previewImg');
            if (!img) {
                const preview = document.createElement('div');
                preview.className = 'photo-preview';
                preview.style.marginTop = '10px';
                img = document.createElement('img');
                img.id = 'previewImg';
                img.style.maxWidth = '200px';
                img.style.borderRadius = '8px';
                preview.appendChild(img);
                document.querySelector('.form-group').appendChild(preview);
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php footer_bar(); ?>
</body>
</html>
