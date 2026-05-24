<?php
require_once 'autoload.php';
require_once 'config/database.php';
require_once 'views/layout/layout.php';

$petObj      = new Pet($pdo);
$total_pets  = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
$total_adopt = $pdo->query("SELECT COUNT(*) FROM adoptions")->fetchColumn();
$recent_pets = $petObj->getAvailable();
$recent_pets = array_slice($recent_pets, 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PawConnect — Find Your Forever Pet</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php navbar($pdo); ?>

<div class="hero">
  <span class="hero-paw">🐾</span>
  <h1>Every pet deserves<br><em>a loving home.</em></h1>
  <p>PawConnect connects pets in need with caring families. Browse, connect, and give a pet a forever home.</p>
  <div class="hero-btns">
    <a href="views/pets/index.php" class="btn btn-primary">🐾 Browse Pets</a>
    <a href="<?= is_logged_in() ? 'views/pets/create.php' : 'register.php' ?>" class="btn btn-outline">List a Pet</a>
  </div>
  <div class="hero-stats">
    <div class="stat"><strong><?= $total_pets ?></strong><span>Pets Available</span></div>
    <div class="stat"><strong><?= $total_adopt ?></strong><span>Adopted</span></div>
  </div>
</div>

<div class="section" style="background:white;border-bottom:1px solid #e5e7eb">
  <div class="container">
    <h2 class="section-title">How PawConnect Works</h2>
    <p class="section-sub">A few simple steps to find a pet its forever home.</p>
    <div class="how-grid">
      <div class="how-card"><span class="how-icon"><i class="fas fa-user-plus"></i></span><div class="how-num">1</div><h3>Create Account</h3><p>Sign up and add your contact info so adopters can reach you directly.</p></div>
      <div class="how-card"><span class="how-icon">🐾</span><div class="how-num">2</div><h3>List or Browse</h3><p>Post your pet with photos, or browse pets available for adoption.</p></div>
      <div class="how-card"><span class="how-icon"><i class="fas fa-comments"></i></span><div class="how-num">3</div><h3>Connect</h3><p>Contact the owner directly via phone, email, or Facebook.</p></div>
      <div class="how-card"><span class="how-icon"><i class="fas fa-home"></i></span><div class="how-num">4</div><h3>Adopt</h3><p>Complete the adoption and give a pet their forever home.</p></div>
    </div>
  </div>
</div>

<?php if ($recent_pets): ?>
<div class="section">
  <div class="container">
    <h2 class="section-title">Pets Looking for a Home</h2>
    <p class="section-sub">Meet some of our available pets</p>
    <div class="pets-grid">
      <?php foreach ($recent_pets as $pet): ?>
      <a href="views/pets/show.php?id=<?= encode_id($pet['id']) ?>" class="pet-card">
        <div class="pet-card-img">
          <?php if ($pet['photo']): ?>
            <img src="uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="">
          <?php else: ?>
            <div class="pet-card-emoji"><?= Pet::emoji($pet['species']) ?></div>
          <?php endif; ?>
          <div class="pet-card-badge"><?= htmlspecialchars($pet['species']) ?></div>
        </div>
        <div class="pet-card-body">
          <h3><?= htmlspecialchars($pet['name']) ?></h3>
          <p class="pet-breed"><?= htmlspecialchars($pet['breed'] ?: 'Mixed breed') ?> · <?= htmlspecialchars($pet['age'] ?: '?') ?></p>
          <div class="pet-tags"><span class="tag"><?= htmlspecialchars($pet['gender']) ?></span></div>
          <p class="pet-poster">🧑 <?= htmlspecialchars($pet['full_name']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="views/pets/index.php" class="btn btn-outline">View All Pets →</a>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="cta-section">
  <h2>Have a pet that needs a home?</h2>
  <p>List them for free and connect with caring adopters.</p>
  <a href="<?= is_logged_in() ? 'views/pets/create.php' : 'register.php' ?>" class="btn-white">
    🐾 <?= is_logged_in() ? 'Add a Pet' : 'Get Started' ?>
  </a>
</div>

<?php footer_bar(); ?>
</body>
</html>
