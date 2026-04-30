<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_once '../../controllers/pets/create.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Pet — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="form-page">
  <div class="form-box form-box-wide">
    <div class="form-logo">
      <div class="paw">🐾</div>
      <h1>List a Pet for Adoption</h1>
      <p>Help find your pet a loving new home. Earn <strong>+<?= PTS_POST_PET ?> points</strong> for posting!</p>
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
          <input type="text" name="name" placeholder="e.g. Buddy" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Species <span class="req">*</span></label>
          <select name="species" required>
            <option value="">-- Select --</option>
            <?php foreach (['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'] as $sp): ?>
              <option <?= ($_POST['species']??'')===$sp?'selected':'' ?>><?= $sp ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Breed</label>
          <input type="text" name="breed" placeholder="e.g. Labrador" value="<?= htmlspecialchars($_POST['breed'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Age</label>
          <input type="text" name="age" placeholder="e.g. 3 months" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Gender</label>
        <select name="gender">
          <?php foreach (['Male','Female','Unknown'] as $g): ?>
            <option <?= ($_POST['gender']??'Unknown')===$g?'selected':'' ?>><?= $g ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>About this Pet</label>
        <textarea name="description" rows="4" placeholder="Personality, habits, ideal home…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="form-section">Photo</div>
      <div class="form-group">
        <div class="file-upload">
          <input type="file" name="photo" id="photoInput" accept="image/*" onchange="previewPhoto(this)">
          <label for="photoInput" class="file-upload-btn">📷 Choose Photo</label>
          <span class="file-name" id="fileName">No file chosen</span>
        </div>
        <div class="photo-preview" id="photoPreview" style="display:none;margin-top:10px">
          <img id="previewImg" src="" alt="" style="max-width:200px;border-radius:8px">
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:8px">
        <a href="../users/index.php" class="btn btn-gray">Cancel</a>
        <button type="submit" class="btn btn-primary">🐾 List Pet for Adoption</button>
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
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php footer_bar(); ?>
</body>
</html>