<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/layout.php';
require_moderator();

$reportObj = new Report($pdo);
$petObj    = new Pet($pdo);

// Sort option: 'recent' | 'oldest' | 'most_reported'
$allowed_sorts = ['recent', 'oldest', 'most_reported'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'recent';

$reports = is_admin() ? $reportObj->getAll($sort) : $reportObj->getPending($sort);
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';

$sort_labels = [
    'recent'       => '🕐 Most Recent',
    'oldest'       => '📅 Oldest First',
    'most_reported'=> '🔥 Most Reported',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reports — PawConnect</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="admin-wrap">
  <div class="admin-header">
    <div>
      <h1>🚩 <?= is_admin() ? 'All Reports' : 'Pending Reports' ?></h1>
      <p style="color:var(--gray-4);font-size:14px">
        <?= is_admin() ? 'Full history of all submitted reports' : 'Reports awaiting your review' ?>
      </p>
    </div>
    <a href="dashboard.php" class="btn btn-gray btn-sm">← Back to Panel</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success auto-dismiss">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($error === 'own_post'): ?>
    <div class="alert alert-error">⚠️ You cannot moderate your own pet listing.</div>
  <?php endif; ?>

  <?php if (!$reports): ?>
    <div class="empty-state">
      <div class="empty-icon">✅</div>
      <p>No reports to review. All clear!</p>
    </div>
  <?php else: ?>
  <div class="panel" style="padding:0;overflow:hidden">
    <div class="panel-header" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
      <h2>Reports (<?= count($reports) ?>)</h2>

      <!-- Sort dropdown -->
      <div class="sort-wrap" id="sortWrap">
        <button class="sort-btn" id="sortToggle" type="button">
          <span>Sort: <?= htmlspecialchars($sort_labels[$sort]) ?></span>
          <span class="sort-arrow">▼</span>
        </button>
        <div class="sort-dropdown">
          <?php foreach ($sort_labels as $val => $label): ?>
            <a href="?sort=<?= $val ?>"
               class="sort-option <?= $sort === $val ? 'active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead style="background:var(--gray-6);font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--gray-3)">
        <tr>
          <th style="padding:10px 16px;text-align:left">Pet</th>
          <th style="padding:10px 16px;text-align:left">Reported By</th>
          <th style="padding:10px 16px;text-align:left">Reason</th>
          <th style="padding:10px 16px;text-align:center">Reports</th>
          <th style="padding:10px 16px;text-align:center">Status</th>
          <th style="padding:10px 16px;text-align:center">Date</th>
          <?php if (is_admin()): ?>
            <th style="padding:10px 16px;text-align:left">Reviewed By</th>
          <?php endif; ?>
          <th style="padding:10px 16px;text-align:center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
        <?php $is_own_post = $r['pet_owner_id'] == $_SESSION['user_id']; ?>
        <tr style="border-top:1px solid var(--gray-5);<?= $r['status'] !== 'pending' ? 'opacity:0.6' : '' ?>">

          <!-- Pet -->
          <td style="padding:12px 16px">
            <div style="display:flex;align-items:center;gap:10px">
              <div style="width:40px;height:40px;border-radius:8px;overflow:hidden;background:var(--gray-6);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
                <?php if ($r['pet_photo']): ?>
                  <img src="../../uploads/pets/<?= htmlspecialchars($r['pet_photo']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <?= Pet::emoji($r['species']) ?>
                <?php endif; ?>
              </div>
              <div>
                <a href="../pets/show.php?id=<?= encode_id($r['pet_id']) ?>" style="font-weight:700;font-size:13px;color:var(--orange);text-decoration:none">
                  <?= htmlspecialchars($r['pet_name']) ?>
                </a>
                <div style="font-size:11px;color:var(--gray-4)">
                  Owner: <?= htmlspecialchars($r['owner_name']) ?>
                  <?php if ($is_own_post): ?>
                    <span style="color:var(--red);font-weight:700"> (You)</span>
                  <?php endif; ?>
                </div>
                <div style="font-size:11px;color:var(--gray-4)">
                  Pet status: <strong><?= ucfirst($r['pet_status']) ?></strong>
                </div>
              </div>
            </div>
          </td>

          <!-- Reporter -->
          <td style="padding:12px 16px;font-size:13px">
            <?= htmlspecialchars($r['reporter_name']) ?>
          </td>

          <!-- Reason + Details -->
          <td style="padding:12px 16px;max-width:200px">
            <div style="font-size:13px;font-weight:700"><?= htmlspecialchars($r['reason']) ?></div>
            <?php if (!empty($r['details'])): ?>
              <div style="font-size:12px;color:var(--gray-3);margin-top:2px"><?= htmlspecialchars($r['details']) ?></div>
            <?php endif; ?>
          </td>

          <!-- Report count badge -->
          <td style="padding:12px 16px;text-align:center">
            <?php if ((int)$r['report_count'] > 1): ?>
              <span class="user-row-badge" style="background:#fef2f2;color:#dc2626;font-size:12px">
                🔥 <?= (int)$r['report_count'] ?>x
              </span>
            <?php else: ?>
              <span style="font-size:12px;color:var(--gray-4)">1</span>
            <?php endif; ?>
          </td>

          <!-- Status -->
          <td style="padding:12px 16px;text-align:center">
            <?php
              $badge_style = match($r['status']) {
                'pending'   => 'background:#fff7ed;color:#f97316',
                'removed'   => 'background:#fef2f2;color:#dc2626',
                'dismissed' => 'background:#f0fdf4;color:#16a34a',
                default     => 'background:var(--gray-6);color:var(--gray-3)',
              };
              $badge_icon = match($r['status']) {
                'pending'   => '⏳',
                'removed'   => '<i class="fas fa-trash"></i>',
                'dismissed' => '✅',
                default     => '',
              };
            ?>
            <span class="user-row-badge" style="<?= $badge_style ?>">
              <?= $badge_icon ?> <?= ucfirst($r['status']) ?>
            </span>
          </td>

          <!-- Date -->
          <td style="padding:12px 16px;text-align:center;font-size:12px;color:var(--gray-4)">
            <?= date('M j, Y', strtotime($r['created_at'])) ?>
          </td>

          <!-- Reviewed By (admin only) -->
          <?php if (is_admin()): ?>
          <td style="padding:12px 16px;font-size:12px;color:var(--gray-3)">
            <?= $r['status'] !== 'pending' && !empty($r['reviewer_name'])
                ? htmlspecialchars($r['reviewer_name'])
                : '<span style="color:var(--gray-5)">—</span>' ?>
          </td>
          <?php endif; ?>

          <!-- Actions -->
          <td style="padding:12px 16px;text-align:center">
            <?php if ($r['status'] === 'pending'): ?>
              <?php if ($is_own_post): ?>
                <span style="font-size:12px;color:var(--gray-4)">Cannot moderate own post</span>
              <?php else: ?>
                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                  <!-- Remove post -->
                  <form method="POST" action="../../controllers/admin/reports.php?sort=<?= urlencode($sort) ?>"
                        class="report-action-form"
                        data-confirm="Remove this pet listing? It will be hidden from the public."
                        data-confirm-btn="<i class='fas fa-trash'></i> Yes, Remove"
                        data-confirm-color="#dc2626">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action"    value="remove">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="pet_id"    value="<?= $r['pet_id'] ?>">
                    <input type="hidden" name="sort"      value="<?= htmlspecialchars($sort) ?>">
                    <button class="btn btn-red btn-sm"><i class="fas fa-trash"></i> Remove Post</button>
                  </form>
                  <!-- Dismiss -->
                  <form method="POST" action="../../controllers/admin/reports.php"
                        class="report-action-form"
                        data-confirm="Dismiss this report? No action will be taken against the listing."
                        data-confirm-btn="✅ Yes, Dismiss"
                        data-confirm-color="#16a34a">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action"    value="dismiss">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="pet_id"    value="<?= $r['pet_id'] ?>">
                    <input type="hidden" name="sort"      value="<?= htmlspecialchars($sort) ?>">
                    <button class="btn btn-sm" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">✅ Dismiss</button>
                  </form>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <span style="font-size:12px;color:var(--gray-5)">—</span>
            <?php endif; ?>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script src="/assets/js/admin.js"></script>

<?php footer_bar(); ?>
</body>
</html>