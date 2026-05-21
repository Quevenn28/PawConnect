<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_moderator();

$logObj  = new ModLog($pdo);
$petObj  = new Pet($pdo);

// Mods see own logs; admins see all
$logs    = is_admin() ? $logObj->getAll() : $logObj->getByMod($_SESSION['user_id']);
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mod Activity Logs — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="admin-wrap">
  <div class="admin-header">
    <div>
      <h1>📋 <?= is_admin() ? 'All Moderator Logs' : 'My Activity Log' ?></h1>
      <p style="color:var(--gray-4);font-size:14px">
        <?= is_admin() ? 'Full audit trail of all moderator actions' : 'Your moderation activity history' ?>
      </p>
    </div>
    <a href="dashboard.php" class="btn btn-gray btn-sm">← Back to Panel</a>
  </div>

  <?php if ($success): ?><div class="alert alert-success auto-dismiss">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

  <?php if (!$logs): ?>
    <div class="empty-state"><div class="empty-icon">📋</div><p>No moderation activity yet.</p></div>
  <?php else: ?>
  <div class="panel" style="padding:0;overflow:hidden">
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead style="background:var(--gray-6);font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--gray-3)">
        <tr>
          <?php if (is_admin()): ?>
          <th style="padding:10px 16px;text-align:left">Moderator</th>
          <?php endif; ?>
          <th style="padding:10px 16px;text-align:left">Action</th>
          <th style="padding:10px 16px;text-align:left">Target</th>
          <th style="padding:10px 16px;text-align:left">Reason / Notes</th>
          <th style="padding:10px 16px;text-align:center">Status</th>
          <th style="padding:10px 16px;text-align:center">Date</th>
          <?php if (is_admin()): ?>
          <th style="padding:10px 16px;text-align:center">Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr class="log-row <?= $log['undone'] ? 'undone' : '' ?>" style="border-top:1px solid var(--gray-5)">

          <?php if (is_admin()): ?>
          <td style="padding:12px 16px">
            <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($log['mod_name']) ?></div>
            <div style="font-size:11px;color:var(--gray-4)">@<?= htmlspecialchars($log['mod_username'] ?? '') ?></div>
          </td>
          <?php endif; ?>

          <!-- Action -->
          <td style="padding:12px 16px">
            <span class="log-action-label"><?= ModLog::actionLabel($log['action']) ?></span>
          </td>

          <!-- Target -->
          <td style="padding:12px 16px">
            <?php if ($log['target_type'] === 'pet'): ?>
              <?php $pet = $petObj->findById($log['target_id']); ?>
              <?php if ($pet): ?>
                <div style="font-size:13px;font-weight:700"><?= htmlspecialchars($pet['name']) ?></div>
                <div style="font-size:11px;color:var(--gray-4);margin-bottom:6px"><?= ModLog::targetLabel($log['target_type']) ?></div>
                <a href="../pets/show.php?id=<?= encode_id($log['target_id']) ?>" class="btn btn-sm btn-outline" style="font-size:11px">👁️ View Post</a>
              <?php else: ?>
                <span style="font-size:13px;color:var(--gray-4)">[Deleted] <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
              <?php endif; ?>
            <?php else: ?>
              <span style="font-size:13px"><?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
            <?php endif; ?>
          </td>

          <!-- Reason / Notes -->
          <td style="padding:12px 16px;font-size:13px;color:var(--gray-3);max-width:220px">
            <?php if ($log['action'] === 'removed_post'): ?>
              <?php
                $report_stmt = $pdo->prepare("
                    SELECT reason, details, created_at
                    FROM reports
                    WHERE pet_id = ? AND status = 'removed'
                    ORDER BY reviewed_at DESC LIMIT 1
                ");
                $report_stmt->execute([$log['target_id']]);
                $report_data = $report_stmt->fetch();
              ?>
              <?php if ($report_data): ?>
                <span style="color:var(--red);font-weight:700">Reason: </span><?= htmlspecialchars($report_data['reason']) ?>
                <?php if ($report_data['details']): ?>
                  <div style="font-size:11px;color:var(--gray-4);margin-top:3px"><?= htmlspecialchars($report_data['details']) ?></div>
                <?php endif; ?>
                <div style="font-size:11px;color:var(--gray-4);margin-top:3px">
                  📅 Reported: <?= date('M j, Y', strtotime($report_data['created_at'])) ?>
                </div>
              <?php else: ?>
                <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
              <?php endif; ?>
            <?php else: ?>
              <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
            <?php endif; ?>
          </td>

          <!-- Status -->
          <td style="padding:12px 16px;text-align:center">
            <?php if ($log['undone']): ?>
              <span class="user-row-badge" style="background:#f0fdf4;color:#16a34a">
                ↩️ Undone by <?= htmlspecialchars($log['undone_by_name'] ?? 'Admin') ?>
              </span>
            <?php else: ?>
              <span class="user-row-badge badge-user">Active</span>
            <?php endif; ?>
          </td>

          <!-- Date -->
          <td style="padding:12px 16px;text-align:center;font-size:12px;color:var(--gray-4)">
            <?= date('M j, Y', strtotime($log['created_at'])) ?>
            <div style="font-size:11px;margin-top:2px"><?= date('g:i A', strtotime($log['created_at'])) ?></div>
          </td>

          <!-- Undo (admin only) -->
          <?php if (is_admin()): ?>
          <td style="padding:12px 16px;text-align:center">
            <?php if (!$log['undone'] && $log['target_type'] === 'pet'): ?>
              <form method="POST" action="../../controllers/admin/undo.php" class="undo-form" data-label="<?= htmlspecialchars(ModLog::actionLabel($log['action'])) ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="type" value="undo_log">
                <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                <input type="hidden" name="target_type" value="<?= $log['target_type'] ?>">
                <input type="hidden" name="pet_id" value="<?= $log['target_id'] ?>">
                <button class="btn btn-sm" style="background:#fef3c7;color:#92400e;border:none">↩️ Undo</button>
              </form>
            <?php else: ?>
              <span style="color:var(--gray-5);font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.undo-form').forEach(f => {
  f.addEventListener('submit', e => {
    e.preventDefault();
    Swal.fire({
      title: 'Undo this action?',
      html: 'This will reverse: <strong>'+f.dataset.label+'</strong><br>The pet listing will be restored to available.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '↩️ Yes, Undo It',
      confirmButtonColor: '#f97316',
      cancelButtonText: 'Cancel'
    }).then(r => { if (r.isConfirmed) f.submit(); });
  });
});
</script>

<?php footer_bar(); ?>
</body>
</html>