<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';

$id = decode_id($_GET['id'] ?? '');
if (!$id) { header("Location: index.php"); exit; }

$petObj = new Pet($pdo);
$pet    = $petObj->findById($id);

if (!$pet || ($pet['status'] === 'removed' && !is_moderator())) {
    header("Location: index.php"); exit;
}

$reqObj           = new AdoptionRequest($pdo);
$already_requested = false;
$already_reported  = false;

// Get adopter info if pet is adopted
$adopter_info = null;
if ($pet['status'] === 'adopted') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name, u.username, u.profile_photo
        FROM adoptions a
        JOIN users u ON u.id = a.adopter_id
        WHERE a.pet_id = ?
        ORDER BY a.adopted_at DESC LIMIT 1
    ");
    $stmt->execute([$id]);
    $adopter_info = $stmt->fetch();
}

if (is_logged_in()) {
    $already_requested = $reqObj->hasRequested($id, $_SESSION['user_id']);
    $reportObj         = new Report($pdo);
    $already_reported  = $reportObj->hasReported($id, $_SESSION['user_id']);
}

// Determine back URL based on referrer
$back_url = 'javascript:history.back()';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    
    if (strpos($referer, '/views/users/index.php') !== false) {
        if (strpos($referer, 'section=') !== false) {
            $back_url = $referer;
        } else {
            $back_url = '/views/users/index.php?section=pets';
        }
    }
    elseif (strpos($referer, '/views/pets/index.php') !== false) {
        $back_url = $referer;
    }
    elseif (strpos($referer, '/views/admin/dashboard.php') !== false) {
        $back_url = $referer;
    }
}

// Check if current user is the pet owner (rehomer)
$is_owner = is_logged_in() && $_SESSION['user_id'] == $pet['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pet['name']) ?> — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="pet-detail-wrap">

  <?php if (isset($_GET['requested'])): ?>
    <div class="alert alert-success">✓ Your adoption request has been sent! The rehomer will contact you soon. 🐾</div>
  <?php endif; ?>
  <?php if (isset($_GET['reported'])): ?>
    <div class="alert alert-success">✓ Report submitted. Our moderators will review it shortly.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <div style="margin-bottom:20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <a href="<?= $back_url ?>" class="btn btn-outline btn-sm" id="backButton">← Back</a>
      <a href="index.php" class="btn btn-gray btn-sm" style="margin-left:10px;">Browse Pets</a>
    </div>
    <?php if ($is_owner && $pet['status'] === 'available'): ?>
      <a href="edit.php?id=<?= encode_id($pet['id']) ?>" class="btn btn-primary btn-sm">✏️ Edit Pet Listing</a>
    <?php endif; ?>
  </div>

  <?php if ($pet['status'] === 'removed' && is_moderator()): ?>
    <div class="alert" style="background:#1f2937;color:#f9fafb;border:2px solid var(--red)">
      🚫 This listing has been removed.
    </div>
  <?php endif; ?>

  <div class="pet-detail-grid">
    <div>
      <?php if ($pet['photo']): ?>
        <div class="pet-detail-img"><img src="../../uploads/pets/<?= htmlspecialchars($pet['photo']) ?>" alt=""></div>
      <?php else: ?>
        <div class="pet-detail-no-img"><?= Pet::emoji($pet['species']) ?></div>
      <?php endif; ?>
    </div>

    <div>
      <div style="display:inline-block;background:#fff7ed;border:1px solid #fed7aa;border-radius:99px;padding:3px 14px;font-size:12px;font-weight:700;color:#f97316;margin-bottom:10px;text-transform:uppercase;letter-spacing:1px">
        <?= htmlspecialchars($pet['species']) ?>
      </div>
      <h1 style="font-family:'Lora',serif;font-size:36px;margin-bottom:4px"><?= htmlspecialchars($pet['name']) ?></h1>
      <p style="font-size:16px;color:var(--gray-3);margin-bottom:18px"><?= htmlspecialchars($pet['breed']?:'Mixed breed') ?></p>

      <div class="attrs-grid">
        <div class="attr-item"><span>Age</span><strong><?= htmlspecialchars($pet['age']?:'Unknown') ?></strong></div>
        <div class="attr-item"><span>Gender</span><strong><?= htmlspecialchars($pet['gender']) ?></strong></div>
        <div class="attr-item"><span>Spayed / Neutered</span><strong><?= htmlspecialchars($pet['spayed_neutered'] ?? 'Unknown') ?></strong></div>
        <div class="attr-item"><span>Good with Children</span><strong><?= htmlspecialchars($pet['good_with_children'] ?? 'Unknown') ?></strong></div>
      </div>

      <?php if ($pet['health_info']): ?>
      <div class="about-section">
        <h3>Health Notes</h3>
        <p><?= nl2br(htmlspecialchars($pet['health_info'])) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($pet['description']): ?>
      <div class="about-section">
        <h3>About <?= htmlspecialchars($pet['name']) ?></h3>
        <p><?= nl2br(htmlspecialchars($pet['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Rehomer Card - Address and contact info only if pet is available -->
      <div class="owner-card">
        <div class="owner-card-top">
          <div class="owner-av"><?= strtoupper(substr($pet['full_name'],0,1)) ?></div>
          <div class="owner-av-name">
            <strong><?= htmlspecialchars($pet['full_name']) ?></strong>
            <span>🐾 Rehomer<?= ($pet['status'] === 'available' && $pet['address']) ? ' · '.$pet['address'] : '' ?></span>
          </div>
        </div>
        
        <?php if ($pet['status'] === 'available'): ?>
          <div class="contact-chips">
            <?php if ($pet['phone']): ?><a href="tel:<?= htmlspecialchars($pet['phone']) ?>" class="chip">📞 <?= htmlspecialchars($pet['phone']) ?></a><?php endif; ?>
            <?php if ($pet['email']): ?><a href="mailto:<?= htmlspecialchars($pet['email']) ?>" class="chip">✉️ Email</a><?php endif; ?>
            <?php if ($pet['facebook']): ?><a href="<?= htmlspecialchars($pet['facebook']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Adopter Card - Only show if pet is adopted -->
      <?php if ($pet['status'] === 'adopted' && $adopter_info): ?>
      <div class="owner-card" style="background:var(--green-lt);border-color:var(--green);">
        <div class="owner-card-top">
          <div class="owner-av" style="background:var(--green);">
            <?php if (!empty($adopter_info['profile_photo'])): ?>
              <img src="../../uploads/users/<?= htmlspecialchars($adopter_info['profile_photo']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
            <?php else: ?>
              <?= strtoupper(substr($adopter_info['full_name'],0,1)) ?>
            <?php endif; ?>
          </div>
          <div class="owner-av-name">
            <strong><?= htmlspecialchars($adopter_info['full_name']) ?></strong>
            <span>🏠 Adopter</span>
          </div>
        </div>
        <div style="font-size:13px;color:var(--gray-3);margin-top:4px">
          ✓ Adopted on <?= date('F j, Y', strtotime($adopter_info['adopted_at'])) ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Adopt section - Only show if pet is available -->
      <?php if ($pet['status'] === 'available'): ?>
        <?php if (is_logged_in() && $_SESSION['user_id'] != $pet['user_id']): ?>
          <?php if ($already_requested): ?>
            <div class="alert alert-info">You already sent an adoption request for <?= htmlspecialchars($pet['name']) ?>.</div>
          <?php else: ?>
          <div class="adopt-form">
            <h3>🐾 Request Adoption</h3>
            <form method="POST" action="../../controllers/requests/create.php">
              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
              <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
              <div class="form-group">
                <label>Message to Rehomer (optional)</label>
                <textarea name="message" rows="3" placeholder="Tell the rehomer why you'd be a great match…"></textarea>
              </div>
              <button type="submit" class="btn btn-primary w-full">Send Adoption Request 🐾</button>
            </form>
          </div>
          <?php endif; ?>
        <?php elseif (!is_logged_in()): ?>
          <div class="login-prompt">
            <p>Want to adopt <?= htmlspecialchars($pet['name']) ?>?</p>
            <div style="display:flex;gap:10px;justify-content:center">
              <a href="../../login.php" class="btn btn-primary">Sign In</a>
              <a href="../../register.php" class="btn btn-outline">Create Account</a>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-info" style="text-align:center;background:var(--gray-6);border-color:var(--gray-5)">
          <?php if ($pet['status'] === 'adopted'): ?>
            🏠 This pet has been adopted.
          <?php else: ?>
            ⚠️ This pet listing is no longer available.
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Report button - Only show if pet is available and user is not the rehomer -->
      <?php if ($pet['status'] === 'available' && is_logged_in() && $_SESSION['user_id'] != $pet['user_id']): ?>
        <div style="margin-top:16px;text-align:right">
          <?php if ($already_reported): ?>
            <span style="font-size:12px;color:var(--gray-4)">✓ You've already reported this listing.</span>
          <?php else: ?>
            <button onclick="showReportForm()" class="btn btn-gray btn-sm" style="font-size:12px">🚩 Report this listing or rehomer</button>
          <?php endif; ?>
        </div>

        <!-- Report form (hidden by default) -->
        <div id="reportForm" style="display:none;margin-top:12px;background:var(--gray-6);border:1px solid var(--gray-5);border-radius:var(--radius-lg);padding:16px">
          <h4 style="margin-bottom:10px;font-size:14px">Report Listing</h4>
          <form method="POST" action="../../controllers/reports/create.php">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
            <div class="form-group">
              <label style="font-size:13px">Reason <span class="req">*</span></label>
              <select name="reason" required style="font-size:13px">
                <option value="">-- Select a reason --</option>
                <?php foreach (Report::REASONS as $reason): ?>
                  <option value="<?= htmlspecialchars($reason) ?>"><?= htmlspecialchars($reason) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label style="font-size:13px">Additional Details (optional)</label>
              <textarea name="details" rows="2" style="font-size:13px" placeholder="Describe the issue…"></textarea>
            </div>
            <div style="display:flex;gap:8px">
              <button type="button" onclick="hideReportForm()" class="btn btn-gray btn-sm">Cancel</button>
              <button type="submit" class="btn btn-red btn-sm">Submit Report</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="/assets/js/pets.js"></script>

<?php footer_bar(); ?>
</body>
</html>