<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';

$petObj  = new Pet($pdo);
$species = $_GET['species'] ?? '';
$q       = trim($_GET['q'] ?? '');
$pets    = $petObj->getAvailable($species, $q);
$total   = count($pets);
$species_list = ['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'];
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
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Name or breed…">
          <button type="submit" class="search-icon-btn">🔍</button>
        </div>
      </div>
      <div class="form-group">
        <label>Species</label>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
          <a href="index.php" style="text-decoration:none;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;background:<?= !$species?'#f97316':'#f9fafb' ?>;color:<?= !$species?'white':'#374151' ?>">All Pets</a>
          <?php foreach ($species_list as $sp): ?>
          <a href="index.php?species=<?= urlencode($sp) ?><?= $q?'&q='.urlencode($q):'' ?>"
             style="text-decoration:none;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;background:<?= $species===$sp?'#f97316':'#f9fafb' ?>;color:<?= $species===$sp?'white':'#374151' ?>">
            <?= Pet::emoji($sp) ?> <?= $sp ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if ($q || $species): ?>
        <a href="index.php" class="btn btn-gray w-full btn-sm" style="margin-top:6px;text-align:center">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <div>
    <div class="browse-header">
      <h1><?= $total ?> Pet<?= $total!=1?'s':'' ?> Available<?= $species?" — $species":'' ?></h1>
      <p>Click a pet to see details and contact the owner</p>
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
