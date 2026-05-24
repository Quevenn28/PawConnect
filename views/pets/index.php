<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/layout.php';

$petObj          = new Pet($pdo);
$species         = $_GET['species'] ?? '';
$q               = trim($_GET['q'] ?? '');
$breed           = trim($_GET['breed'] ?? '');
$vaccinated      = trim($_GET['vaccinated'] ?? '');
$spayed_neutered = trim($_GET['spayed_neutered'] ?? '');
$age_value       = trim($_GET['age_value'] ?? '');
$age_unit        = trim($_GET['age_unit'] ?? '');
$sort            = trim($_GET['sort'] ?? 'recent');
$pets            = $petObj->getAvailable($species, $q, $breed, $vaccinated, $spayed_neutered, $age_value, $age_unit, $sort);
$total           = count($pets);
$total_available = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
$total_adopted   = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='adopted'")->fetchColumn();
$filter_active   = $species || $q || $breed || $vaccinated || $spayed_neutered || $age_value;
$species_list    = ['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'];
$age_units       = ['day'=>'Days','week'=>'Weeks','month'=>'Months','year'=>'Years'];
$sort_options    = ['recent'=>'Most Recent','alpha'=>'Alphabetical'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Pets — PawConnect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="/assets/js/main.js"></script>
</head>
<body class="has-fixed-sidebar">
<?php navbar($pdo); ?>

<div class="browse-wrap page-with-fixed-sidebar">
  <div class="container-fluid">
    <div class="row">
      
      <!-- SIDEBAR WRAPPER - 3 columns (25%) -->
      <div class="col-lg-3 sidebar-wrapper">
        <aside class="browse-sidebar">
          <!-- Close button (✕) inside sidebar - top right corner (same as dashboard) -->
          <button class="sidebar-close-btn" id="closeSidebarBtn" aria-label="Close sidebar">✕</button>
          
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
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="form-group">
                  <label>Age</label>
                  <input type="number" min="0" name="age_value" value="<?= htmlspecialchars($age_value) ?>" placeholder="3">
                </div>
                <div class="form-group">
                  <label>Unit</label>
                  <select name="age_unit">
                    <?php foreach ($age_units as $unit => $label): ?>
                      <option value="<?= htmlspecialchars($unit) ?>" <?= $age_unit === $unit ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
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
                <label>Spayed / Neutered</label>
                <select name="spayed_neutered">
                  <option value="">Any</option>
                  <option value="Yes" <?= $spayed_neutered === 'Yes' ? 'selected' : '' ?>>Yes</option>
                  <option value="No" <?= $spayed_neutered === 'No' ? 'selected' : '' ?>>No</option>
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
        </aside>
      </div>

      <!-- MAIN CONTENT WRAPPER - 9 columns (75%) -->
      <div class="col-lg-9 main-content-wrapper">
        <!-- Wrapper that contains open button + content side by side (same as dashboard) -->
        <div class="content-header-wrapper">
          <!-- Open button (☰) - only visible when sidebar is collapsed -->
          <button class="content-open-btn" id="openSidebarBtn" aria-label="Open sidebar">☰</button>
          
          <div class="browse-main">
            <div class="browse-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
              <div>
                <h1>Browse Pets<?= $species ? " — $species" : '' ?></h1>
                <p>
                  <?php if ($filter_active): ?>
                    Showing <?= number_format($total) ?> pet<?= $total != 1 ? 's' : '' ?> matching filters out of <?= number_format($total_available) ?> available.
                  <?php else: ?>
                    Showing <?= number_format($total_available) ?> available pet<?= $total_available != 1 ? 's' : '' ?>.
                  <?php endif; ?>
                </p>
              </div>
              <?php if (is_logged_in()): ?>
                <a href="../pets/create.php" class="btn btn-primary">🐾 Add Pet for Adoption</a>
              <?php endif; ?>
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
      </div>
      
    </div>
  </div>
</div>

<script src="/assets/js/pets.js"></script>

<?php footer_bar(); ?>
</body>
</html>