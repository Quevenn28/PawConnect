<?php
require_once 'db.php';
require_login();

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user info
$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$user_id]);
$user = $user->fetch();

// Get my pets
$my_pets = $pdo->prepare("SELECT * FROM pets WHERE user_id=? ORDER BY created_at DESC");
$my_pets->execute([$user_id]);
$my_pets = $my_pets->fetchAll();

// Get adoption requests for my pets (pending)
$pending = $pdo->prepare("
    SELECT ar.*, u.full_name, u.phone, u.email as req_email, u.facebook, p.name as pet_name
    FROM adoption_requests ar
    JOIN users u ON u.id = ar.requester_id
    JOIN pets p  ON p.id = ar.pet_id
    WHERE p.user_id = ? AND ar.status = 'pending'
    ORDER BY ar.created_at DESC
");
$pending->execute([$user_id]);
$pending = $pending->fetchAll();

// My adoption requests sent
$my_requests = $pdo->prepare("
    SELECT ar.*, p.name as pet_name, p.photo, p.species,
           u.full_name as owner_name, u.phone as owner_phone,
           u.email as owner_email, u.facebook as owner_fb
    FROM adoption_requests ar
    JOIN pets p  ON p.id = ar.pet_id
    JOIN users u ON u.id = p.user_id
    WHERE ar.requester_id = ?
    ORDER BY ar.created_at DESC
");
$my_requests->execute([$user_id]);
$my_requests = $my_requests->fetchAll();

// Adoption history (pets I rehomed)
$adopted_hist = $pdo->prepare("SELECT * FROM adoptions WHERE owner_email=? ORDER BY adopted_at DESC LIMIT 5");
$adopted_hist->execute([$user['email']]);
$adopted_hist = $adopted_hist->fetchAll();

$welcome = isset($_GET['welcome']);

// Check if adoption_requests table exists, create if not
try {
    $pdo->query("SELECT 1 FROM adoption_requests LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("CREATE TABLE adoption_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pet_id INT NOT NULL,
        requester_id INT NOT NULL,
        message TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY (pet_id, requester_id),
        FOREIGN KEY (pet_id) REFERENCES pets(id),
        FOREIGN KEY (requester_id) REFERENCES users(id)
    )");
}
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
  <title>Dashboard — PawConnect</title>
  <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><span>🐾</span> PawConnect</a>
  <div class="nav-links">
    <a href="pets.php">Browse Pets</a>
    <a href="logout.php" class="btn btn-gray btn-sm">Logout</a>
  </div>
</nav>

<div class="dashboard-wrap">

  <?php if ($welcome): ?>
    <div class="alert alert-success">🎉 Welcome to PawConnect, <?= htmlspecialchars($user_name) ?>! Start by adding a pet or browsing available ones.</div>
  <?php endif; ?>

  <div class="dash-header">
    <div>
      <h1>My Dashboard</h1>
      <p>Manage your pets and adoption requests</p>
    </div>
    <a href="add-pet.php" class="btn btn-primary">🐾 Add Pet for Adoption</a>
  </div>

  <div class="dash-grid">

    <!-- Left: Profile -->
    <div>
      <div class="profile-card">
        <?php if (!empty($user['profile_photo'])): ?>
          <div class="profile-avatar"><img src="uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo"></div>
        <?php else: ?>
          <div class="profile-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
        <?php endif; ?>
        <h3><?= htmlspecialchars($user['full_name']) ?></h3>
        <p>@<?= htmlspecialchars($user['username']) ?></p>

        <div class="contact-list">
          <?php if ($user['phone']): ?>
            <div class="contact-item">📞 <a href="tel:<?= htmlspecialchars($user['phone']) ?>"><?= htmlspecialchars($user['phone']) ?></a></div>
          <?php endif; ?>
          <div class="contact-item">✉️ <a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a></div>
          <?php if ($user['facebook']): ?>
            <div class="contact-item">📘 <a href="<?= htmlspecialchars($user['facebook']) ?>" target="_blank">Facebook</a></div>
          <?php endif; ?>
          <?php if ($user['address']): ?>
            <div class="contact-item">📍 <?= htmlspecialchars($user['address']) ?></div>
          <?php endif; ?>
        </div>

        <a href="edit-profile.php" class="btn btn-outline w-full btn-sm">✏️ Edit Profile</a>
      </div>
    </div>

    <!-- Right: Main content -->
    <div>

      <!-- Pending requests for my pets -->
      <?php if ($pending): ?>
      <div class="panel">
        <div class="panel-header">
          <h2>🔔 Adoption Requests <span style="background:#fee2e2;color:#dc2626;font-size:11px;padding:2px 8px;border-radius:99px;margin-left:6px"><?= count($pending) ?></span></h2>
        </div>
        <?php foreach ($pending as $req): ?>
        <div class="request-card">
          <div class="request-info">
            <strong><?= htmlspecialchars($req['full_name']) ?></strong> wants to adopt <strong><?= htmlspecialchars($req['pet_name']) ?></strong>
            <?php if ($req['message']): ?>
              <p>"<?= htmlspecialchars($req['message']) ?>"</p>
            <?php endif; ?>
            <div class="contact-chips">
              <?php if ($req['phone']): ?><a href="tel:<?= htmlspecialchars($req['phone']) ?>" class="chip">📞 <?= htmlspecialchars($req['phone']) ?></a><?php endif; ?>
              <a href="mailto:<?= htmlspecialchars($req['req_email']) ?>" class="chip">✉️ Email</a>
              <?php if ($req['facebook']): ?><a href="<?= htmlspecialchars($req['facebook']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
            </div>
          </div>
          <form method="POST" action="handle-request.php" style="display:flex;gap:6px">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
            <button name="action" value="approve" class="btn btn-green btn-sm">✓ Approve</button>
            <button name="action" value="reject"  class="btn btn-red   btn-sm">✕ Reject</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- My Pet Listings -->
      <div class="panel">
        <div class="panel-header">
          <h2>🐾 My Pet Listings</h2>
        </div>
        <?php if (!$my_pets): ?>
          <div class="empty-state">
            <div class="empty-icon">🐾</div>
            <p>You haven't listed any pets yet.</p>
          </div>
        <?php else: ?>
        <div class="my-pet-list">
          <?php foreach ($my_pets as $pet): ?>
          <div class="my-pet-item">
            <div class="my-pet-img">
              <?php if ($pet['photo']): ?>
                <img src="uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="">
              <?php else: ?>
                <div class="my-pet-img-emoji">
                  <?= $pet['species']==='Dog'?'🐕':($pet['species']==='Cat'?'🐈':'🐾') ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="my-pet-body">
              <strong><?= htmlspecialchars($pet['name']) ?></strong>
              <span><?= htmlspecialchars($pet['species']) ?></span>
              <div><span class="my-pet-status <?= $pet['status']==='available'?'status-available':'status-adopted' ?>"><?= ucfirst($pet['status']) ?></span></div>
              <?php if ($pet['status'] === 'available'): ?>
              <div class="my-pet-actions">
                <a href="pet.php?id=<?= $pet['id'] ?>" class="btn btn-outline btn-sm">View</a>
                <form method="POST" action="mark-adopted.php" class="mark-adopted-form" data-pet-name="<?= htmlspecialchars($pet['name']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                  <button type="submit" class="btn btn-green btn-sm">✓ Adopted</button>
                </form>
                <form method="POST" action="delete-pet.php" class="delete-pet-form" data-pet-name="<?= htmlspecialchars($pet['name']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                  <button class="btn btn-red btn-sm">Delete</button>
                </form>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- My Adoption Requests -->
      <?php if ($my_requests): ?>
      <div class="panel">
        <div class="panel-header"><h2>📋 My Adoption Requests</h2></div>
        <?php foreach ($my_requests as $r): ?>
        <div class="my-request-row">
          <div class="req-thumb">
            <?php if ($r['photo']): ?>
              <img src="uploads/pets/<?= htmlspecialchars($r['photo']) ?>" alt="">
            <?php else: ?>
              <?= $r['species']==='Dog'?'🐕':'🐾' ?>
            <?php endif; ?>
          </div>
          <div class="req-info">
            <strong><?= htmlspecialchars($r['pet_name']) ?></strong>
            <span class="req-badge req-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
            <?php if ($r['status'] === 'approved'): ?>
              <div class="contact-chips" style="margin-top:6px">
                <span style="font-size:12px;color:#6b7280">Owner:</span>
                <?php if ($r['owner_phone']): ?><a href="tel:<?= htmlspecialchars($r['owner_phone']) ?>" class="chip">📞 <?= htmlspecialchars($r['owner_phone']) ?></a><?php endif; ?>
                <a href="mailto:<?= htmlspecialchars($r['owner_email']) ?>" class="chip">✉️ Email</a>
                <?php if ($r['owner_fb']): ?><a href="<?= htmlspecialchars($r['owner_fb']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
          <span style="font-size:12px;color:#9ca3af"><?= date('M j', strtotime($r['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Adoption History -->
      <?php if ($adopted_hist): ?>
      <div class="panel">
        <div class="panel-header"><h2>🏠 Pets Rehomed</h2></div>
        <?php foreach ($adopted_hist as $a): ?>
        <div class="my-request-row">
          <div class="req-thumb">🐾</div>
          <div class="req-info">
            <strong><?= htmlspecialchars($a['pet_name']) ?></strong>
            <span style="font-size:13px;color:#6b7280">Adopted by <?= htmlspecialchars($a['adopter_name']) ?></span>
          </div>
          <span style="font-size:12px;color:#9ca3af"><?= date('M j, Y', strtotime($a['adopted_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div><!-- /right -->
  </div><!-- /dash-grid -->
</div>

<script>
  document.querySelectorAll('.mark-adopted-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      var petName = this.dataset.petName || 'this pet';
      Swal.fire({
        title: 'Mark adopted?',
        text: 'Mark ' + petName + ' as adopted? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark adopted',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.delete-pet-form').forEach(function(form) {
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      var petName = this.dataset.petName || 'this listing';
      Swal.fire({
        title: 'Delete pet listing?',
        text: 'Are you sure you want to delete ' + petName + '? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
      }).then(function(result) {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
</body>
</html>
