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

if (is_logged_in()) {
    $already_requested = $reqObj->hasRequested($id, $_SESSION['user_id']);
    $reportObj         = new Report($pdo);
    $already_reported  = $reportObj->hasReported($id, $_SESSION['user_id']);
}
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
    <div class="alert alert-success">✓ Your adoption request has been sent! The owner will contact you soon. 🐾</div>
  <?php endif; ?>
  <?php if (isset($_GET['reported'])): ?>
    <div class="alert alert-success">✓ Report submitted. Our moderators will review it shortly.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <?php if ($pet['status'] === 'removed' && is_moderator()): ?>
    <div class="alert" style="background:#1f2937;color:#f9fafb;border:2px solid var(--red)">
      🚫 This listing has been removed and is only visible to moderators/admins.
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
            <span>Pet Owner<?= $pet['address'] ? ' · '.$pet['address'] : '' ?></span>
          </div>
        </div>
        <div class="contact-chips">
          <?php if ($pet['phone']): ?><a href="tel:<?= htmlspecialchars($pet['phone']) ?>" class="chip">📞 <?= htmlspecialchars($pet['phone']) ?></a><?php endif; ?>
          <?php if ($pet['email']): ?><a href="mailto:<?= htmlspecialchars($pet['email']) ?>" class="chip">✉️ Email</a><?php endif; ?>
          <?php if ($pet['facebook']): ?><a href="<?= htmlspecialchars($pet['facebook']) ?>" target="_blank" class="chip">📘 Facebook</a><?php endif; ?>
        </div>
      </div>

      <!-- Adopt section -->
      <?php if (is_logged_in() && $_SESSION['user_id'] != $pet['user_id'] && $pet['status'] === 'available'): ?>
        <?php if ($already_requested): ?>
          <div class="alert alert-info">You already sent an adoption request for <?= htmlspecialchars($pet['name']) ?>.</div>
        <?php else: ?>
        <div class="adopt-form">
          <h3>🐾 Request Adoption</h3>
          <form method="POST" action="../../controllers/requests/create.php">
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
            <a href="../../login.php" class="btn btn-primary">Sign In</a>
            <a href="../../register.php" class="btn btn-outline">Create Account</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Report button -->
      <?php if (is_logged_in() && $_SESSION['user_id'] != $pet['user_id'] && $pet['status'] === 'available'): ?>
        <div style="margin-top:16px;text-align:right">
          <?php if ($already_reported): ?>
            <span style="font-size:12px;color:var(--gray-4)">✓ You've already reported this listing.</span>
          <?php else: ?>
            <button onclick="showReportForm()" class="btn btn-gray btn-sm" style="font-size:12px">🚩 Report this listing</button>
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

<script>
function showReportForm() { document.getElementById('reportForm').style.display = 'block'; }
function hideReportForm() { document.getElementById('reportForm').style.display = 'none'; }
</script>

<?php footer_bar(); ?>
</body>
</html>
