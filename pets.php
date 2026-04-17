<?php
require_once 'db.php';

$species = $_GET['species'] ?? '';
$q       = trim($_GET['q'] ?? '');

$where  = ["p.status='available'"];
$params = [];
if ($species) { $where[] = "p.species=?"; $params[] = $species; }
if ($q)       { $where[] = "(p.name LIKE ? OR p.breed LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$where_sql = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.phone, u.email, u.facebook FROM pets p JOIN users u ON u.id=p.user_id $where_sql ORDER BY p.created_at DESC");
$stmt->execute($params);
$pets = $stmt->fetchAll();

$total = count($pets);
$species_list = ['Dog','Cat','Bird','Rabbit','Hamster','Fish','Reptile','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Pets — PawConnect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <?php if (is_logged_in()): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="add-pet.php" class="btn btn-primary btn-sm">+ Add Pet</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Join Free</a>
    <?php endif; ?>
  </div>
</nav>

<div class="browse-wrap">

  <!-- Filters -->
  <div class="filter-box">
    <h3>Filter Pets</h3>
    <form method="GET">
      <div class="form-group">
        <label>Search</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Name or breed…">
      </div>
      <div class="form-group">
        <label>Species</label>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">
          <a href="pets.php" style="text-decoration:none;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;background:<?= !$species?'#f97316':'#f9fafb' ?>;color:<?= !$species?'white':'#374151' ?>">All Pets</a>
          <?php foreach ($species_list as $sp): ?>
          <a href="pets.php?species=<?= urlencode($sp) ?><?= $q?'&q='.urlencode($q):'' ?>"
             style="text-decoration:none;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;background:<?= $species===$sp?'#f97316':'#f9fafb' ?>;color:<?= $species===$sp?'white':'#374151' ?>">
            <?= $sp==='Dog'?'🐕':($sp==='Cat'?'🐈':($sp==='Bird'?'🦜':($sp==='Rabbit'?'🐇':'🐾'))) ?> <?= $sp ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-full btn-sm">Search</button>
      <?php if ($q || $species): ?>
        <a href="pets.php" class="btn btn-gray w-full btn-sm" style="margin-top:6px;text-align:center">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Grid -->
  <div>
    <div class="browse-header">
      <h1><?= $total ?> Pet<?= $total!=1?'s':'' ?> Available<?= $species?" — $species":'' ?></h1>
      <p>Click a pet to see details and contact the owner</p>
    </div>

    <?php if (!$pets): ?>
      <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <p>No pets found. Try a different search.</p>
      </div>
    <?php else: ?>
    <div class="pets-grid">
      <?php foreach ($pets as $pet): ?>
      <a href="pet.php?id=<?= $pet['id'] ?>" class="pet-card">
        <div class="pet-card-img">
          <?php if ($pet['photo']): ?>
            <img src="uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
          <?php else: ?>
            <div class="pet-card-emoji">
              <?= $pet['species']==='Dog'?'🐕':($pet['species']==='Cat'?'🐈':($pet['species']==='Bird'?'🦜':'🐾')) ?>
            </div>
          <?php endif; ?>
          <div class="pet-card-badge"><?= htmlspecialchars($pet['species']) ?></div>
        </div>
        <div class="pet-card-body">
          <h3><?= htmlspecialchars($pet['name']) ?></h3>
          <p class="pet-breed"><?= htmlspecialchars($pet['breed'] ?: 'Mixed breed') ?> &middot; <?= htmlspecialchars($pet['age'] ?: '?') ?></p>
          <div class="pet-tags">
            <span class="tag"><?= htmlspecialchars($pet['gender']) ?></span>
          </div>
          <p class="pet-poster">🧑 <?= htmlspecialchars($pet['full_name']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<div class="footer">
  <div class="footer-logo">🐾 PawConnect</div>
  <p>© <?= date('Y') ?> PawConnect</p>
</div>

</body>
</html>
