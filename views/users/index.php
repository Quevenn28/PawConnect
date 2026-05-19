<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_login();

$userObj    = new User($pdo);
$petObj     = new Pet($pdo);
$reqObj     = new AdoptionRequest($pdo);
$notifObj   = new Notification($pdo);

$user_id    = $_SESSION['user_id'];
$user       = $userObj->findById($user_id);
$my_pets    = $petObj->getByUser($user_id);
$pending    = $reqObj->getPendingForOwner($user_id);
$my_reqs    = $reqObj->getSentByUser($user_id);
$notifications = $notifObj->getForUser($user_id, 8);
$unread_notifications = $notifObj->countUnread($user_id);
$notifObj->markAllRead($user_id);

// Adoption history (rehomer)
$adopted_hist = $pdo->prepare("SELECT * FROM adoptions WHERE owner_id=? ORDER BY adopted_at DESC LIMIT 10");
$adopted_hist->execute([$user_id]);
$adopted_hist = $adopted_hist->fetchAll();

// Adoption history (adopter)
$my_adopted = $pdo->prepare("SELECT * FROM adoptions WHERE adopter_id=? ORDER BY adopted_at DESC LIMIT 10");
$my_adopted->execute([$user_id]);
$my_adopted = $my_adopted->fetchAll();

// Points-based titles
$rehomer_points = get_rehomer_points($pdo, $user_id);
$adopter_points = get_adopter_points($pdo, $user_id);
$rehomer_title  = get_rehomer_title($rehomer_points);
$adopter_title  = get_adopter_title($adopter_points);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — PawConnect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="/assets/js/main.js"></script>
</head>
<body class="has-fixed-sidebar">
<?php navbar($pdo); ?>

<div class="dashboard-wrap page-with-fixed-sidebar">

  <?php if (isset($_GET['welcome'])): ?>
    <div class="alert alert-success">🎉 Welcome to PawConnect, <?= htmlspecialchars($user['full_name']) ?>! You've earned <strong><?= PTS_REGISTER ?> welcome points</strong>!</div>
  <?php endif; ?>

  <div class="container-fluid">
    <div class="row">
      
      <!-- SIDEBAR WRAPPER - 3 columns (25%) -->
      <div class="col-lg-3 sidebar-wrapper">
        <aside class="dashboard-sidebar">
          <!-- Close button (✕) inside sidebar - top right corner -->
          <button class="sidebar-close-btn" id="closeSidebarBtn" aria-label="Close sidebar">✕</button>
          
          <div class="sidebar-profile">
            <?php if (!empty($user['profile_photo'])): ?>
              <div class="sidebar-avatar"><img src="../../uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" alt=""></div>
            <?php else: ?>
              <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
            <?php endif; ?>
            <div class="sidebar-name">
              <h3><?= htmlspecialchars($user['full_name']) ?></h3>
              <span>@<?= htmlspecialchars($user['username']) ?></span>
            </div>
            <div class="sidebar-badges">
              <?php if ($user['role'] !== 'admin'): ?>
                <span class="title-badge rehomer"><?= get_rehomer_badge($rehomer_title) ?> <?= $rehomer_title ?></span>
                <span class="title-badge adopter"><?= get_adopter_badge($adopter_title) ?> <?= $adopter_title ?></span>
                <?php if (is_moderator()): ?>
                  <?php $mod_title = get_mod_title($user['mod_points']); ?>
                  <span class="title-badge moderator"><?= get_mod_badge($mod_title) ?> <?= $mod_title ?></span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

          <nav class="sidebar-nav" aria-label="Dashboard sections">
            <button type="button" class="sidebar-link active" data-section="profile">Profile</button>
            <button type="button" class="sidebar-link" data-section="pets">Pet Listings</button>
            <button type="button" class="sidebar-link" data-section="requests">Adoption Requests</button>
            <button type="button" class="sidebar-link" data-section="adopted">Adopted Pets</button>
            <button type="button" class="sidebar-link" data-section="notifications">Notifications</button>
          </nav>
        </aside>
      </div>

      <!-- MAIN CONTENT WRAPPER - 9 columns (75%) -->
      <div class="col-lg-9 main-content-wrapper">
        <!-- Wrapper that contains open button + content side by side -->
        <div class="content-header-wrapper">
          <!-- Open button (☰) - only visible when sidebar is collapsed -->
          <button class="content-open-btn" id="openSidebarBtn" aria-label="Open sidebar">☰</button>
          
          <main class="dashboard-main">
            
            <section id="profile" class="dashboard-section active">
              <div class="panel">
                <div class="panel-header"><h2>👤 Profile Summary</h2></div>
                <div class="profile-card">
                  <?php if (!empty($user['profile_photo'])): ?>
                    <div class="profile-avatar"><img src="../../uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" alt=""></div>
                  <?php else: ?>
                    <div class="profile-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
                  <?php endif; ?>
                  <h3><?= htmlspecialchars($user['full_name']) ?></h3>
                  <p>@<?= htmlspecialchars($user['username']) ?></p>

                  <div class="profile-points-bar">
                    <div class="pts-label">Total Points</div>
                    <div class="pts-score"><?= number_format($user['points']) ?></div>
                    <div class="titles-row" style="margin-top:10px">
                      <?php if ($user['role'] !== 'admin'): ?>
                        <span class="title-badge rehomer"><?= get_rehomer_badge($rehomer_title) ?> <?= $rehomer_title ?></span>
                        <span class="title-badge adopter"><?= get_adopter_badge($adopter_title) ?> <?= $adopter_title ?></span>
                        <?php if (is_moderator()): ?>
                          <?php $mod_title = get_mod_title($user['mod_points']); ?>
                          <span class="title-badge moderator"><?= get_mod_badge($mod_title) ?> <?= $mod_title ?></span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="contact-list">
                    <?php if ($user['phone']): ?><div class="contact-item">📞 <a href="tel:<?= htmlspecialchars($user['phone']) ?>"><?= htmlspecialchars($user['phone']) ?></a></div><?php endif; ?>
                    <div class="contact-item">✉️ <a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a></div>
                    <?php if ($user['facebook']): ?><div class="contact-item">📘 <a href="<?= htmlspecialchars($user['facebook']) ?>" target="_blank">Facebook</a></div><?php endif; ?>
                    <?php if ($user['address']): ?><div class="contact-item">📍 <?= htmlspecialchars($user['address']) ?></div><?php endif; ?>
                  </div>
                  <a href="edit.php" class="btn btn-outline w-full btn-sm" style="margin-top:14px;">✏️ Edit Profile</a>
                </div>
              </div>
            </section>

            <section id="pets" class="dashboard-section">
              <div class="panel">
                <div class="panel-header">
                  <h2>🐾 My Pet Listings</h2>
                  <a href="../pets/create.php" class="btn btn-primary">🐾 Add Pet for Adoption</a>
                </div>
                <?php if (!$my_pets): ?>
                  <div class="empty-state"><div class="empty-icon">🐾</div><p>You haven't listed any pets yet.</p></div>
                <?php else: ?>
                <div class="my-pet-list">
                  <?php foreach ($my_pets as $pet): ?>
                  <div class="my-pet-item">
                    <div class="my-pet-img">
                      <?php if ($pet['photo']): ?>
                        <img src="../../uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt="">
                      <?php else: ?>
                        <div class="my-pet-img-emoji"><?= Pet::emoji($pet['species']) ?></div>
                      <?php endif; ?>
                    </div>
                    <div class="my-pet-body">
                      <strong><?= htmlspecialchars($pet['name']) ?></strong>
                      <span><?= htmlspecialchars($pet['species']) ?></span>
                      <div><span class="my-pet-status <?= $pet['status']==='available'?'status-available':'status-adopted' ?>"><?= ucfirst($pet['status']) ?></span></div>
                      <div class="my-pet-actions">
                        <a href="../pets/show.php?id=<?= encode_id($pet['id']) ?>" class="btn btn-outline btn-sm">View</a>
                        <?php if ($pet['status'] === 'available'): ?>
                          <a href="../pets/edit.php?id=<?= encode_id($pet['id']) ?>" class="btn btn-blue btn-sm">✏️ Edit</a>
                          <form method="POST" action="../../controllers/pets/mark_adopted.php" class="mark-adopted-form" data-name="<?= htmlspecialchars($pet['name']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                            <button class="btn btn-green btn-sm">✓ Adopted</button>
                          </form>
                          <form method="POST" action="../../controllers/pets/delete.php" class="delete-pet-form" data-name="<?= htmlspecialchars($pet['name']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                            <button class="btn btn-red btn-sm">🗑️ Remove</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </section>

            <section id="requests" class="dashboard-section">
              <div class="panel">
                <div class="panel-header">
                  <h2>🔔 Adoption Requests</h2>
                </div>
                <?php if ($pending): ?>
                  <?php foreach ($pending as $req): ?>
                  <div class="request-card">
                    <div class="request-info">
                      <span class="user-popup">
                          <strong><?= htmlspecialchars($req['full_name']) ?></strong>
                          <div class="user-tooltip">
                              <?php if (!empty($req['profile_photo'])): ?>
                                  <div class="tooltip-avatar"><img src="../../uploads/users/<?= htmlspecialchars($req['profile_photo']) ?>" alt=""></div>
                              <?php else: ?>
                                  <div class="tooltip-avatar"><?= strtoupper(substr($req['full_name'], 0, 1)) ?></div>
                              <?php endif; ?>
                              <div class="tooltip-name"><?= htmlspecialchars($req['full_name']) ?></div>
                              <div class="tooltip-title">@<?= htmlspecialchars($req['username']) ?></div>
                              <?php
                              $requester_points = get_rehomer_points($pdo, $req['user_id']);
                              $requester_title = get_rehomer_title($requester_points);
                              ?>
                              <div class="tooltip-badge">🏠 <?= $requester_title ?></div>
                          </div>
                      </span>
                      <span>wants to adopt</span>
                      <a href="../pets/show.php?id=<?= encode_id($req['pet_id']) ?>" class="pet-name-link" style="font-weight: 800; color: var(--gray-1); text-decoration: none; border-bottom: 1px dashed var(--orange);">
                          <?= htmlspecialchars($req['pet_name']) ?>
                      </a>
                      <?php if ($req['message']): ?><p>"<?= htmlspecialchars($req['message']) ?>"</p><?php endif; ?>
                      <div class="contact-chips">
                        <?php if ($req['phone']): ?><a href="tel:<?= htmlspecialchars($req['phone']) ?>" class="chip">📞 <?= htmlspecialchars($req['phone']) ?></a><?php endif; ?>
                        <?php if ($req['facebook']): ?><a href="<?= htmlspecialchars($req['facebook']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
                      </div>
                    </div>
                    <form method="POST" action="../../controllers/requests/handle.php" style="display:flex;gap:6px">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                      <button name="action" value="approved" class="btn btn-green btn-sm">✓ Approve</button>
                      <button name="action" value="rejected" class="btn btn-red btn-sm">✕ Reject</button>
                    </form>
                  </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="empty-state"><div class="empty-icon">📭</div><p>No new adoption requests yet.</p></div>
                <?php endif; ?>
              </div>

              <?php if ($my_reqs): ?>
                <div class="panel">
                  <div class="panel-header"><h2>📋 My Adoption Requests</h2></div>
                  <?php foreach ($my_reqs as $r): ?>
                  <div class="my-request-row">
                    <div class="req-thumb">
                      <?php if ($r['photo']): ?>
                        <img src="../../uploads/pets/<?= htmlspecialchars($r['photo']) ?>" alt="">
                      <?php else: ?>
                        <?= Pet::emoji($r['species']) ?>
                      <?php endif; ?>
                    </div>
                    <div class="req-info">
                      <a href="../pets/show.php?id=<?= encode_id($r['pet_id']) ?>" style="font-weight: 800; color: var(--gray-1); text-decoration: none;">
                        <?= htmlspecialchars($r['pet_name']) ?>
                      </a>
                      <span class="req-badge req-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                      <?php if ($r['status'] === 'approved'): ?>
                        <div class="contact-chips" style="margin-top:6px">
                          <span style="font-size:12px;color:var(--gray-4)">Owner:</span>
                          <?php if ($r['owner_phone']): ?><a href="tel:<?= htmlspecialchars($r['owner_phone']) ?>" class="chip">📞 <?= htmlspecialchars($r['owner_phone']) ?></a><?php endif; ?>
                          <a href="mailto:<?= htmlspecialchars($r['owner_email']) ?>" class="chip">✉️ Email</a>
                          <?php if ($r['owner_fb']): ?><a href="<?= htmlspecialchars($r['owner_fb']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                      <div style="display:flex;gap:6px;">
                        <a href="../pets/show.php?id=<?= encode_id($r['pet_id']) ?>" class="btn btn-outline btn-sm">👁️ View</a>
                        <form method="POST" action="../../controllers/requests/delete.php" class="del-req-form" data-name="<?= htmlspecialchars($r['pet_name']) ?>">
                          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                          <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                          <button class="btn btn-gray btn-sm">🗑️ Remove</button>
                        </form>
                      </div>
                      <span style="font-size:12px;color:var(--gray-4)"><?= date('M j', strtotime($r['created_at'])) ?></span>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>

            <section id="adopted" class="dashboard-section">
              <div class="panel">
                <div class="panel-header"><h2>🏠 Pets I Adopted</h2></div>
                <?php if (!$my_adopted): ?>
                  <div class="empty-state"><div class="empty-icon">🐾</div><p>You haven't adopted any pets yet.</p></div>
                <?php else: ?>
                  <?php foreach ($my_adopted as $adopt): ?>
                    <div class="my-request-row">
                      <div class="req-thumb">🐾</div>
                      <div class="req-info">
                        <strong><?= htmlspecialchars($adopt['pet_name']) ?></strong>
                        <span class="req-badge req-approved">Adopted</span>
                        <p style="margin-top:6px;font-size:13px;color:var(--gray-4)">Adopted on <?= date('M j, Y', strtotime($adopt['adopted_at'])) ?></p>
                      </div>
                      <a href="../pets/show.php?id=<?= encode_id($adopt['pet_id']) ?>" class="btn btn-gray btn-sm">👁️ View</a>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <?php if ($adopted_hist): ?>
              <div class="panel">
                <div class="panel-header"><h2>🏠 Pets Rehomed</h2></div>
                <?php foreach ($adopted_hist as $a): ?>
                <div class="my-request-row">
                  <div class="req-thumb">🐾</div>
                  <div class="req-info">
                    <strong><?= htmlspecialchars($a['pet_name']) ?></strong>
                    <span style="font-size:13px;color:var(--gray-3)">Adopted by <?= htmlspecialchars($a['adopter_name']) ?></span>
                  </div>
                  <div style="display:flex;gap:8px;align-items:center">
                    <span style="font-size:12px;color:var(--gray-4)"><?= date('M j, Y', strtotime($a['adopted_at'])) ?></span>
                    <a href="../pets/show.php?id=<?= encode_id($a['pet_id']) ?>" class="btn btn-gray btn-sm">👁️ View</a>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </section>

            <section id="notifications" class="dashboard-section">
              <div class="panel">
                <div class="panel-header">
                  <h2>🔔 Notifications</h2>
                  <?php if ($unread_notifications > 0): ?>
                    <span style="background:var(--orange);color:white;font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px"><?= $unread_notifications ?> new</span>
                  <?php endif; ?>
                </div>
                <?php if (!$notifications): ?>
                  <div class="empty-state"><div class="empty-icon">🔔</div><p>No notifications yet. You will see updates here when things happen.</p></div>
                <?php else: ?>
                  <?php foreach ($notifications as $note): ?>
                  <div class="notification-card <?= $note['is_read'] ? '' : 'notification-unread' ?>">
                    <div class="notification-text"><?= htmlspecialchars($note['message']) ?></div>
                    <div class="notification-meta">
                      <span><?= date('M j, Y H:i', strtotime($note['created_at'])) ?></span>
                      <?php if ($note['link']): ?>
                        <a href="<?= htmlspecialchars($note['link']) ?>" class="notification-link">View</a>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </section>
            
          </main>
        </div>
      </div>
      
    </div>
  </div>
</div>

<script src="/assets/js/dashboard.js"></script>

<?php footer_bar(); ?>
</body>
</html>