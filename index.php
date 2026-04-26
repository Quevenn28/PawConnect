<?php
require_once 'db.php';

// Get stats
$total_pets  = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
$total_adopt = $pdo->query("SELECT COUNT(*) FROM adoptions")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get recent pets
$stmt = $pdo->query("SELECT p.*, u.full_name FROM pets p JOIN users u ON u.id=p.user_id WHERE p.status='available' ORDER BY p.created_at DESC LIMIT 6");
$recent_pets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script
  src="https://www.tuqlas.com/chatbot.js"
  data-key="tq_live_a9917a05459b29796ff98c8c8b5c0576a7eafbe0"
  data-api="https://www.tuqlas.com"
  defer
></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PawConnect — Find Your Forever Pet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <span>🐾</span> PawConnect
  </a>
  <div class="nav-links">
    <a href="pets.php">Browse Pets</a>
    <?php if (is_logged_in()): ?>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php" class="btn-nav btn">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php" class="btn-nav btn">Join Free</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <span class="hero-paw">🐾</span>
  <h1>Every pet deserves<br><em>a loving home.</em></h1>
  <p>PawConnect connects pets in need with caring families. Browse, connect, and give a pet a forever home.</p>
  <div class="hero-btns">
    <a href="pets.php" class="btn btn-primary">🐾 Browse Pets</a>
    <a href="register.php" class="btn btn-outline">List a Pet Free</a>
  </div>
  <div class="hero-stats">
    <div class="stat">
      <strong><?= $total_pets ?></strong>
      <span>Pets Available</span>
    </div>
    <div class="stat">
      <strong><?= $total_adopt ?></strong>
      <span>Adopted</span>
    </div>
    <div class="stat">
      <strong><?= $total_users ?></strong>
      <span>Members</span>
    </div>
  </div>
</div>

<!-- HOW IT WORKS -->
<div class="section" style="background: white; border-bottom: 1px solid #e5e7eb;">
  <div class="container">
    <h2 class="section-title">How PawConnect Works</h2>
    <p class="section-sub">Easy as few steps, you can rehome a pet of your dreams.</p>
    <div class="how-grid">
      <div class="how-card">
        <span class="how-icon">📝</span>
        <div class="how-num">1</div>
        <h3>Create Account</h3>
        <p>Sign up and add your contact info so adopters can reach you directly.</p>
      </div>
      <div class="how-card">
        <span class="how-icon">🐾</span>
        <div class="how-num">2</div>
        <h3>List or Browse</h3>
        <p>Post your pet with photos, or browse pets available for adoption.</p>
      </div>
      <div class="how-card">
        <span class="how-icon">💬</span>
        <div class="how-num">3</div>
        <h3>Connect</h3>
        <p>Contact the owner directly via phone, email, or Facebook. There, negotiations will begin.</p>
      </div>
      <div class="how-card">
        <span class="how-icon">🏠</span>
        <div class="how-num">4</div>
        <h3>Adopt</h3>
        <p>Complete the adoption and give a pet their forever home.</p>
      </div>
    </div>
  </div>
</div>

<!-- FEATURED PETS -->
<?php if ($recent_pets): ?>
<div class="section">
  <div class="container">
    <h2 class="section-title">Pets Looking for a Home</h2>
    <p class="section-sub">Meet some of our available pets</p>
    <div class="pets-grid">
      <?php foreach ($recent_pets as $pet): ?>
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
          <p class="pet-breed"><?= htmlspecialchars($pet['breed'] ?: 'Mixed breed') ?> &middot; <?= htmlspecialchars($pet['age'] ?: 'Unknown age') ?></p>
          <div class="pet-tags">
            <span class="tag"><?= htmlspecialchars($pet['gender']) ?></span>
          </div>
          <p class="pet-poster">🧑 <?= htmlspecialchars($pet['full_name']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="pets.php" class="btn btn-outline">View All Pets →</a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- CTA -->
<div class="cta-section">
  <h2>Have a pet that needs a home?</h2>
  <p>List them for free and connect with caring adopters.</p>
  <a href="<?= is_logged_in() ? 'add-pet.php' : 'register.php' ?>" class="btn-white">
    🐾 <?= is_logged_in() ? 'Add a Pet' : 'Get Started Free' ?>
  </a>
</div>

<!-- FOOTER -->
<div class="footer">
  <div class="footer-logo">🐾 PawConnect</div>
  <div class="footer-links">
    <a href="pets.php">Browse Pets</a>
    <a href="register.php">Register</a>
    <a href="login.php">Login</a>
  </div>
  <p>© <?= date('Y') ?> PawConnect. Connecting pets with forever homes.</p>
</div>

</body>
</html>

