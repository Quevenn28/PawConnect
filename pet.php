<?php
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: pets.php"); exit; }

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.phone, u.email, u.facebook, u.address FROM pets p JOIN users u ON u.id=p.user_id WHERE p.id=? AND p.status='available'");
$stmt->execute([$id]);
$pet = $stmt->fetch();
if (!$pet) { header("Location: pets.php"); exit; }

// Check if adoption_requests table exists
try { $pdo->query("SELECT 1 FROM adoption_requests LIMIT 1"); }
catch (PDOException $e) {
    $pdo->exec("CREATE TABLE adoption_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pet_id INT NOT NULL,
        requester_id INT NOT NULL,
        message TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY (pet_id, requester_id)
    )");
}

$already_requested = false;
if (is_logged_in()) {
    $chk = $pdo->prepare("SELECT id FROM adoption_requests WHERE pet_id=? AND requester_id=?");
    $chk->execute([$id, $_SESSION['user_id']]);
    $already_requested = (bool)$chk->fetch();
}

$msg_sent   = isset($_GET['requested']);
$msg_error  = $_GET['error'] ?? '';

$emoji = $pet['species']==='Dog'?'🐕':($pet['species']==='Cat'?'🐈':($pet['species']==='Bird'?'🦜':'🐾'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pet['name']) ?> — PawConnect</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="pets.php">← All Pets</a>
    <?php if (is_logged_in()): ?>
      <a href="dashboard.php">Dashboard</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Join Free</a>
    <?php endif; ?>
  </div>
</nav>

<div class="pet-detail-wrap">

  <?php if ($msg_sent): ?>
    <div class="alert alert-success">✓ Your adoption request has been sent! The owner will contact you soon. 🐾</div>
  <?php endif; ?>
  <?php if ($msg_error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($msg_error) ?></div>
  <?php endif; ?>

  <div class="pet-detail-grid">

    <!-- Photo -->
    <div>
      <?php if ($pet['photo']): ?>
        <div class="pet-detail-img">
          <img src="uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
        </div>
      <?php else: ?>
        <div class="pet-detail-no-img"><?= $emoji ?></div>
      <?php endif; ?>
    </div>

    <!-- Info -->
    <div>
      <div style="display:inline-block;background:#fff7ed;border:1px solid #fed7aa;border-radius:99px;padding:3px 14px;font-size:12px;font-weight:700;color:#f97316;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px">
        <?= htmlspecialchars($pet['species']) ?>
      </div>
      <h1 class="pet-detail-info" style="font-family:'Lora',serif;font-size:36px;margin-bottom:4px"><?= htmlspecialchars($pet['name']) ?></h1>
      <p class="breed" style="font-size:16px;color:#6b7280;margin-bottom:18px"><?= htmlspecialchars($pet['breed'] ?: 'Mixed breed') ?></p>

      <div class="attrs-grid">
        <div class="attr-item"><span>Age</span><strong><?= htmlspecialchars($pet['age'] ?: 'Unknown') ?></strong></div>
        <div class="attr-item"><span>Gender</span><strong><?= htmlspecialchars($pet['gender']) ?></strong></div>
      </div>

      <?php if ($pet['description']): ?>
      <div class="about-section">
        <h3>About <?= htmlspecialchars($pet['name']) ?></h3>
        <p><?= nl2br(htmlspecialchars($pet['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Owner card -->
      <div class="owner-card">
        <div class="owner-card-top">
          <div class="owner-av"><?= strtoupper(substr($pet['full_name'],0,1)) ?></div>
          <div class="owner-av-name">
            <strong><?= htmlspecialchars($pet['full_name']) ?></strong>
            <span>Pet Owner <?= $pet['address'] ? '· '.$pet['address'] : '' ?></span>
          </div>
        </div>
        <div class="contact-chips">
          <?php if ($pet['phone']): ?>
            <a href="tel:<?= htmlspecialchars($pet['phone']) ?>" class="chip">📞 <?= htmlspecialchars($pet['phone']) ?></a>
          <?php endif; ?>
          <?php if ($pet['email']): ?>
            <a href="mailto:<?= htmlspecialchars($pet['email']) ?>" class="chip">✉️ <?= htmlspecialchars($pet['email']) ?></a>
          <?php endif; ?>
          <?php if ($pet['facebook']): ?>
            <a href="<?= htmlspecialchars($pet['facebook']) ?>" target="_blank" class="chip">📘 Facebook</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Adopt section -->
      <?php if (is_logged_in() && $_SESSION['user_id'] != $pet['user_id']): ?>
        <?php if ($already_requested): ?>
          <div class="alert alert-info">✓ You already sent an adoption request for <?= htmlspecialchars($pet['name']) ?>.</div>
        <?php else: ?>
        <div class="adopt-form">
          <h3>🐾 Request Adoption</h3>
          <form method="POST" action="send-request.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
            <div class="form-group">
              <label>Message to Owner (optional)</label>
              <textarea name="message" rows="3" placeholder="Tell the owner why you'd be a great match…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-full">Send Adoption Request 🐾</button>
          </form>
        </div>
        <?php endif; ?>
      <?php elseif (!is_logged_in()): ?>
        <div class="login-prompt">
          <p>Want to adopt <?= htmlspecialchars($pet['name']) ?>?</p>
          <div style="display:flex;gap:10px;justify-content:center">
            <a href="login.php" class="btn btn-primary">Sign In</a>
            <a href="register.php" class="btn btn-outline">Create Account</a>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<div class="footer">
  <div class="footer-logo">🐾 PawConnect</div>
  <p>© <?= date('Y') ?> PawConnect</p>
</div>

</body>
</html>
