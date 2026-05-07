<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';

$petObj          = new Pet($pdo);
$species         = $_GET['species'] ?? '';
$q               = trim($_GET['q'] ?? '');
$breed           = trim($_GET['breed'] ?? '');
$health_info     = trim($_GET['health'] ?? '');
$vaccinated      = trim($_GET['vaccinated'] ?? '');
$age_value       = trim($_GET['age_value'] ?? '');
$age_unit        = trim($_GET['age_unit'] ?? '');
$sort            = trim($_GET['sort'] ?? 'recent');
$pets            = $petObj->getAvailable($species, $q, $breed, $health_info, $vaccinated, $age_value, $age_unit, $sort);
$total           = count($pets);
$total_available = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
$total_adopted   = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='adopted'")->fetchColumn();
$filter_active   = $species || $q || $breed || $health_info || $vaccinated || $age_value;
$species_list    = ['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'];
$age_units       = ['day'=>'Days','week'=>'Weeks','month'=>'Months','year'=>'Years'];
$sort_options    = ['recent'=>'Most Recent','alpha'=>'Alphabetical'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Browse Pets — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<?php navbar($pdo); ?>

<div class="browse-wrap">
  <div class="filter-box">
    <h3>Filter Pets</h3>
    <form method="GET">
      <div class="form-group">
        <label>Search</label>
        <div class="search-input-group">
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Name, breed, owner, or other details…">
          <button type="submit" class="search-icon-btn">🔍</button>
        </div>
      </div>
      <div class="form-group">
        <label>Species</label>
        <select name="species">
          <option value="">All Pets</option>
          <?php foreach ($species_list as $sp): ?>
            <option value="<?= htmlspecialchars($sp) ?>" <?= $species === $sp ? 'selected' : '' ?>><?= Pet::emoji($sp) ?> <?= $sp ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Breed</label>
        <input type="text" name="breed" value="<?= htmlspecialchars($breed) ?>" placeholder="e.g. Labrador">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Age</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <input type="number" min="0" name="age_value" value="<?= htmlspecialchars($age_value) ?>" placeholder="3">
            <select name="age_unit">
              <option value="">Unit</option>
              <?php foreach ($age_units as $unit => $label): ?>
                <option value="<?= htmlspecialchars($unit) ?>" <?= $age_unit === $unit ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label>Medical / Health Info</label>
        <input type="text" name="health" value="<?= htmlspecialchars($health_info) ?>" placeholder="Vaccinated, neutered, special care…">
      </div>
      <div class="form-group">
        <label>Vaccinated</label>
        <select name="vaccinated">
          <option value="">Any</option>
          <option value="Yes" <?= $vaccinated === 'Yes' ? 'selected' : '' ?>>Yes</option>
          <option value="No" <?= $vaccinated === 'No' ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="form-group">
        <label>Sort</label>
        <select name="sort">
          <?php foreach ($sort_options as $value => $label): ?>
            <option value="<?= htmlspecialchars($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($filter_active): ?>
        <div class="filter-hint">Showing filtered results. Clear filters to view all available pets.</div>
        <a href="index.php" class="btn btn-gray w-full btn-sm" style="margin-top:10px;text-align:center">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <div>
    <div class="browse-header">
      <h1>Browse Pets<?= $species ? " — $species" : '' ?></h1>
      <p>
        <?php if ($filter_active): ?>
          Showing <?= number_format($total) ?> pet<?= $total != 1 ? 's' : '' ?> matching filters out of <?= number_format($total_available) ?> available.
        <?php else: ?>
          Showing <?= number_format($total_available) ?> available pet<?= $total_available != 1 ? 's' : '' ?>.
        <?php endif; ?>
      </p>
    </div>
    <div class="stats-grid" style="margin-bottom:20px;">
      <div class="stat-card">
        <div class="stat-label">Available Pets</div>
        <div class="stat-value text-green"><?= number_format($total_available) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Adopted Pets</div>
        <div class="stat-value text-blue"><?= number_format($total_adopted) ?></div>
      </div>
    </div>
    <?php if (!$pets): ?>
      <div class="empty-state"><div class="empty-icon">🔍</div><p>No pets found. Try a different search.</p></div>
    <?php else: ?>
    <div class="pets-grid">
      <?php foreach ($pets as $pet): ?>
      <a href="<?= is_logged_in() ? 'show.php?id='.encode_id($pet['id']) : '../../register.php' ?>" class="pet-card">        
        <div class="pet-card-img">
          <?php if ($pet['photo']): ?>
            <img src="../../uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="">
          <?php else: ?>
            <div class="pet-card-emoji"><?= Pet::emoji($pet['species']) ?></div>
          <?php endif; ?>
          <div class="pet-card-badge"><?= htmlspecialchars($pet['species']) ?></div>
        </div>
        <div class="pet-card-body">
          <h3><?= htmlspecialchars($pet['name']) ?></h3>
          <p class="pet-breed"><?= htmlspecialchars($pet['breed']?:'Mixed breed') ?> · <?= htmlspecialchars($pet['age']?:'?') ?></p>
          <div class="pet-tags"><span class="tag"><?= htmlspecialchars($pet['gender']) ?></span></div>
          <p class="pet-poster">🧑 <?= htmlspecialchars($pet['full_name']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php footer_bar(); ?>
</body>
</html>
