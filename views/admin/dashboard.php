<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/layout.php';
require_moderator();

$userObj   = new User($pdo);
$petObj    = new Pet($pdo);
$reportObj = new Report($pdo);
$logObj    = new ModLog($pdo);

$tab = $_GET['tab'] ?? 'reports';

// Sort option for reports: 'recent' | 'oldest' | 'most_reported'
$allowed_sorts = ['recent', 'oldest', 'most_reported'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'recent';

// Filter by reason
$reason = isset($_GET['reason']) ? trim($_GET['reason']) : '';
$reason = in_array($reason, Report::REASONS, true) ? $reason : '';

$sort_labels = [
    'recent'       => '🕐 Most Recent',
    'oldest'       => '📅 Oldest First',
    'most_reported'=> '🔥 Most Reported',
];

// Stats
$total_users    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$total_mods     = $pdo->query("SELECT COUNT(*) FROM users WHERE role='moderator'")->fetchColumn();
$total_pets     = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='available'")->fetchColumn();
$total_removed  = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='removed'")->fetchColumn();
$total_adopted  = $pdo->query("SELECT COUNT(*) FROM pets WHERE status='adopted'")->fetchColumn();
$pending_reports= $reportObj->getPendingCount();
$total_banned   = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned=1 AND (ban_until IS NULL OR ban_until > NOW())")->fetchColumn();

// Pets tab sort/filter params
$allowed_pet_sorts  = ['pet_az', 'pet_za', 'owner_az', 'owner_za', 'recent', 'oldest'];
$_pet_sort_raw      = isset($_GET['pet_sort']) ? $_GET['pet_sort'] : '';
$pet_sort           = in_array($_pet_sort_raw, $allowed_pet_sorts, true) ? $_pet_sort_raw : 'recent';
$allowed_pet_status = ['', 'available', 'adopted', 'removed'];
$_pet_status_raw    = isset($_GET['pet_status']) ? $_GET['pet_status'] : '';
$pet_status         = in_array($_pet_status_raw, $allowed_pet_status, true) ? $_pet_status_raw : '';

// Users tab sort/filter params
$allowed_user_sorts  = ['name_az', 'name_za', 'newest', 'oldest', 'points_desc', 'points_asc'];
$user_sort           = in_array($_GET['user_sort'] ?? '', $allowed_user_sorts, true) ? ($_GET['user_sort'] ?? 'newest') : 'newest';
$allowed_user_roles  = ['', 'user', 'moderator'];
$user_role           = in_array($_GET['user_role'] ?? '', $allowed_user_roles, true) ? ($_GET['user_role'] ?? '') : '';
$allowed_user_status = ['', 'active', 'banned'];
$user_status_filter  = in_array($_GET['user_status'] ?? '', $allowed_user_status, true) ? ($_GET['user_status'] ?? '') : '';

// Tab data
$reports   = $tab === 'reports' ? $reportObj->getPending($sort, $reason) : [];
$all_pets  = $tab === 'pets'    ? $petObj->getAllAdmin($_GET['q'] ?? '', $pet_sort, $pet_status) : [];
$mod_logs  = ($tab === 'logs' && is_admin()) ? $logObj->getAll() : [];
$my_logs   = $tab === 'mylogs'  ? $logObj->getByMod($_SESSION['user_id']) : [];
$all_users = ($tab === 'users' && is_admin()) ? $userObj->search($_GET['q'] ?? '', $user_sort, $user_role, $user_status_filter) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= is_admin() ? 'Admin Panel' : 'Moderator Panel' ?> — PawConnect</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="/assets/js/main.js"></script>
  <script>
    const isMod = <?= is_admin() ? 'false' : 'true' ?>;
  </script>
  <script src="/assets/js/admin.js"></script>
</head>
<body class="has-fixed-sidebar">
<?php navbar($pdo); ?>

<div class="admin-wrap page-with-fixed-sidebar">
  <div class="container-fluid">
    <div class="row">
      <!-- SIDEBAR WRAPPER -->
      <div class="col-lg-3 sidebar-wrapper">
        <aside class="admin-sidebar">
          <!-- Close button (✕) inside sidebar - top right corner -->
          <button class="sidebar-close-btn" id="closeSidebarBtn" aria-label="Close sidebar">✕</button>
          
          <div class="admin-sidebar-title"><?= is_admin() ? 'Admin Tools' : 'Moderator Menu' ?></div>
          <div class="admin-tabs">
            <a href="?tab=reports" class="admin-tab <?= $tab==='reports'?'active':'' ?>">
              🚩 Reports <?php if($pending_reports>0): ?><span class="tab-badge"><?= $pending_reports ?></span><?php endif; ?>
            </a>
            <a href="?tab=pets"   class="admin-tab <?= $tab==='pets'?'active':'' ?>">🐾 All Pets</a>
            <a href="?tab=mylogs" class="admin-tab <?= $tab==='mylogs'?'active':'' ?>">📋 My Activity</a>
            <?php if (is_admin()): ?>
            <a href="?tab=logs"  class="admin-tab <?= $tab==='logs'?'active':'' ?>">🔍 Activity Log</a>
            <?php endif; ?>
            <?php if (is_admin()): ?>
              <a href="?tab=users" class="admin-tab <?= $tab==='users'?'active':'' ?>">👥 Users</a>
            <?php endif; ?>
          </div>
        </aside>
      </div>
      
      <!-- MAIN CONTENT WRAPPER -->
      <div class="col-lg-9 main-content-wrapper">
        <!-- Wrapper that contains open button + content side by side -->
        <div class="content-header-wrapper">
          <!-- Open button (☰) - only visible when sidebar is collapsed -->
          <button class="content-open-btn" id="openSidebarBtn" aria-label="Open sidebar">☰</button>
          
          <main class="admin-main">
            <div class="stat-cards">
              <div class="stat-card red">
                <div class="stat-num"><?= $pending_reports ?></div>
                <div class="stat-lbl">Pending Reports</div>
              </div>
              <div class="stat-card">
                <div class="stat-num"><?= $total_pets ?></div>
                <div class="stat-lbl">Active Listings</div>
              </div>
              <div class="stat-card">
                <div class="stat-num"><?= $total_removed ?></div>
                <div class="stat-lbl">Removed Posts</div>
              </div>
              <div class="stat-card green">
                <div class="stat-num"><?= $total_adopted ?></div>
                <div class="stat-lbl">Pets Adopted</div>
              </div>
              <?php if (is_admin()): ?>
              <div class="stat-card blue">
                <div class="stat-num"><?= $total_users ?></div>
                <div class="stat-lbl">Regular Users</div>
              </div>
              <div class="stat-card purple">
                <div class="stat-num"><?= $total_mods ?></div>
                <div class="stat-lbl">Moderators</div>
              </div>
              <div class="stat-card red">
                <div class="stat-num"><?= $total_banned ?></div>
                <div class="stat-lbl">Banned Users</div>
              </div>
              <?php endif; ?>
            </div>

            <!-- TAB: REPORTS -->
            <?php if ($tab === 'reports'): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap">
                <h2 style="font-size:18px;margin:0">Pending Reports</h2>
                <div style="display:flex;gap:10px;align-items:center">
                  <!-- Reason filter dropdown -->
                  <div class="sort-wrap" id="reasonWrap">
                    <button class="sort-btn" id="reasonToggle" type="button">
                      <span>Reason: <?= (!empty($reason) && in_array($reason, Report::REASONS, true)) ? htmlspecialchars(trim($reason)) : 'All' ?></span>
                      <span class="sort-arrow">▼</span>
                    </button>
                    <div class="sort-dropdown">
                      <a href="?tab=reports&sort=<?= $sort ?>"
                         class="sort-option <?= ($reason === '' || $reason === false) ? 'active' : '' ?>">
                        All Categories
                      </a>
                      <?php foreach (Report::REASONS as $r): ?>
                        <a href="?tab=reports&sort=<?= $sort ?>&reason=<?= urlencode($r) ?>"
                           class="sort-option <?= (trim($reason) === trim($r)) ? 'active' : '' ?>">
                          <?= htmlspecialchars($r) ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  
                  <!-- Sort dropdown -->
                  <div class="sort-wrap" id="sortWrap">
                    <button class="sort-btn" id="sortToggle" type="button">
                      <span>Sort: <?= htmlspecialchars($sort_labels[$sort]) ?></span>
                      <span class="sort-arrow">▼</span>
                    </button>
                    <div class="sort-dropdown">
                      <?php foreach ($sort_labels as $val => $label): ?>
                        <a href="?tab=reports&sort=<?= $val ?><?= $reason ? '&reason='.urlencode($reason) : '' ?>"
                           class="sort-option <?= $sort === $val ? 'active' : '' ?>">
                          <?= $label ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php if (!$reports): ?>
                <div class="empty-state"><div class="empty-icon">✅</div><p>No pending reports. All clear!</p></div>
              <?php endif; ?>
              <?php foreach ($reports as $r): ?>
              <div class="report-card urgent">
                <div class="report-pet-thumb">
                  <?php if ($r['pet_photo']): ?>
                    <img src="../../uploads/pets/<?= htmlspecialchars($r['pet_photo']) ?>" alt="">
                  <?php else: ?>
                    <?= Pet::emoji($r['species']) ?>
                  <?php endif; ?>
                </div>
                <div class="report-body">
                  <h4><?= htmlspecialchars($r['pet_name']) ?></h4>
                  <div class="report-meta">
                    Rehomer: <strong><?= htmlspecialchars($r['owner_name']) ?></strong> (@<?= htmlspecialchars($r['owner_username']) ?>)
                    · Reported by: <strong><?= htmlspecialchars($r['reporter_name']) ?></strong>
                    · <?= date('M j, Y g:ia', strtotime($r['created_at'])) ?>
                  </div>
                  <div class="report-reason-tag"><?= htmlspecialchars($r['reason']) ?></div>
                  <?php if ($r['details']): ?>
                    <p style="font-size:13px;color:var(--gray-3);margin-top:4px"><?= htmlspecialchars($r['details']) ?></p>
                  <?php endif; ?>

                  <div class="report-actions">
                    <a href="../../views/pets/show.php?id=<?= encode_id($r['pet_id']) ?>" class="btn btn-gray btn-sm"><i class="fas fa-eye"></i> View Post</a>

                    <?php $is_own_post = ($r['pet_owner_id'] == $_SESSION['user_id']); ?>

                    <?php if ($is_own_post): ?>
                      <span class="mod-restricted">You cannot moderate your own content</span>
                    <?php else: ?>
                      <form class="report-ajax-form" style="display:inline" data-action="remove" data-report-id="<?= $r['id'] ?>" data-pet-id="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button class="btn btn-red btn-sm remove-btn" type="submit" data-name="<?= htmlspecialchars($r['pet_name']) ?>"><i class="fas fa-trash"></i> Remove Post</button>
                      </form>
                      <form class="report-ajax-form" style="display:inline" data-action="dismiss" data-report-id="<?= $r['id'] ?>" data-pet-id="<?= $r['pet_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button class="btn btn-gray btn-sm dismiss-btn" type="submit" data-name="<?= htmlspecialchars($r['pet_name']) ?>">✅ Dismiss</button>
                      </form>
                      <form method="POST" action="/controllers/admin/ban.php" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="user_id" value="<?= $r['pet_owner_id'] ?>">
                        <input type="hidden" name="duration" value="<?= is_admin() ? '0' : '24' ?>">
                        <button type="button" class="btn btn-red btn-sm ban-btn" data-name="<?= htmlspecialchars($r['owner_name']) ?>">🚫 <?= is_admin() ? 'Ban User' : 'Ban 24h' ?></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>

            <!-- TAB: ALL PETS -->
            <?php elseif ($tab === 'pets'): ?>
              <?php
                // Build base URL helper for pets tab links (preserves q and pet_status/pet_sort as needed)
                $pets_q      = htmlspecialchars($_GET['q'] ?? '');
                $pets_base   = '?tab=pets' . ($pets_q ? '&q='.urlencode($_GET['q'] ?? '') : '');

                // Column sort helpers
                $pet_next_az   = ($pet_sort === 'pet_az')   ? 'pet_za'   : 'pet_az';
                $owner_next_az = ($pet_sort === 'owner_az') ? 'owner_za' : 'owner_az';
                $posted_next   = ($pet_sort === 'recent')   ? 'oldest'   : 'recent';

                // Arrow indicator
                function sort_arrow($current, $asc, $desc) {
                  if ($current === $asc)  return ' <span style="color:var(--primary)">▲</span>';
                  if ($current === $desc) return ' <span style="color:var(--primary)">▼</span>';
                  return ' <span style="color:var(--gray-5);font-size:10px">⇅</span>';
                }

                $status_labels = ['' => 'All', 'available' => '🟢 Available', 'adopted' => '💙 Adopted', 'removed' => '🔴 Removed'];
              ?>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:10px;flex-wrap:wrap">
                <form method="GET" class="search-admin" style="margin:0;flex:1;min-width:200px">
                  <input type="hidden" name="tab" value="pets">
                  <input type="hidden" name="pet_sort" value="<?= htmlspecialchars($pet_sort) ?>">
                  <input type="hidden" name="pet_status" value="<?= htmlspecialchars($pet_status) ?>">
                  <input type="text" name="q" value="<?= $pets_q ?>" placeholder="Search by name, breed, or rehomer…" style="flex:1">
                  <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>

                <!-- Status filter dropdown -->
                <div class="sort-wrap">
                  <button class="sort-btn" type="button">
                    <span>Status: <?= $status_labels[$pet_status] ?></span>
                    <span class="sort-arrow">▼</span>
                  </button>
                  <div class="sort-dropdown">
                    <?php foreach ($status_labels as $val => $label): ?>
                      <a href="<?= $pets_base ?>&pet_sort=<?= $pet_sort ?>&pet_status=<?= urlencode($val) ?>"
                         class="sort-option <?= $pet_status === $val ? 'active' : '' ?>">
                        <?= $label ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>

              <div class="panel">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                  <thead>
                    <tr style="background:var(--gray-6);text-align:left">
                      <th style="padding:10px 12px">
                        <a href="<?= $pets_base ?>&pet_sort=<?= $pet_next_az ?>&pet_status=<?= urlencode($pet_status) ?>"
                           style="text-decoration:none;color:inherit;cursor:pointer;white-space:nowrap"
                           title="Sort by pet name">
                          Pet <?= sort_arrow($pet_sort, 'pet_az', 'pet_za') ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">
                        <a href="<?= $pets_base ?>&pet_sort=<?= $owner_next_az ?>&pet_status=<?= urlencode($pet_status) ?>"
                           style="text-decoration:none;color:inherit;cursor:pointer;white-space:nowrap"
                           title="Sort by owner name">
                          Rehomer <?= sort_arrow($pet_sort, 'owner_az', 'owner_za') ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">Status</th>
                      <th style="padding:10px 12px">
                        <a href="<?= $pets_base ?>&pet_sort=<?= $posted_next ?>&pet_status=<?= urlencode($pet_status) ?>"
                           style="text-decoration:none;color:inherit;cursor:pointer;white-space:nowrap"
                           title="Sort by date posted">
                          Posted <?= sort_arrow($pet_sort, 'oldest', 'recent') ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($all_pets as $pet): ?>
                    <tr style="border-bottom:1px solid var(--gray-5)">
                      <td style="padding:10px 12px">
                        <strong><?= htmlspecialchars($pet['name']) ?></strong><br>
                        <span style="color:var(--gray-4)"><?= htmlspecialchars($pet['species']) ?> · <?= htmlspecialchars($pet['breed']?:'—') ?></span>
                      </td>
                      <td style="padding:10px 12px">
                        <?= htmlspecialchars($pet['full_name']) ?><br>
                        <span style="color:var(--gray-4)">@<?= htmlspecialchars($pet['username']) ?></span>
                      </td>
                      <td style="padding:10px 12px">
                        <?php $st = $pet['status']; ?>
                        <span class="my-pet-status <?= $st==='available'?'status-available':($st==='adopted'?'status-adopted':'') ?>"
                              style="<?= $st==='removed'?'background:var(--red-lt);color:var(--red)':'' ?>">
                          <?= ucfirst($st) ?>
                          <?php if ($st === 'removed' && $pet['removed_by']): ?>
                            <small>(by <?= $pet['removed_by'] ?>)</small>
                          <?php endif; ?>
                        </span>
                      </td>
                      <td style="padding:10px 12px;color:var(--gray-4)"><?= date('M j, Y', strtotime($pet['created_at'])) ?></td>
                      <td style="padding:10px 12px">
                        <a href="../../views/pets/show.php?id=<?= encode_id($pet['id']) ?>" class="btn btn-gray btn-sm"><i class="fas fa-eye"></i> View</a>
                        <?php if ($pet['status'] === 'removed' && is_admin()): ?>
                          <form method="POST" action="../../controllers/admin/undo.php" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="type" value="restore_pet">
                            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                            <button class="btn btn-green btn-sm restore-pet-btn" data-name="<?= htmlspecialchars($pet['name']) ?>">♻️ Restore</button>
                          </form>
                          <form method="POST" action="../../controllers/admin/undo.php" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="type" value="hard_delete">
                            <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
                            <button class="btn btn-red btn-sm hard-del-btn" data-name="<?= htmlspecialchars($pet['name']) ?>"><i class="fas fa-times"></i> Delete Permanently</button>
                          </form>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

            <!-- TAB: MY ACTIVITY LOGS -->
            <?php elseif ($tab === 'mylogs'): ?>
              <h2 style="font-size:18px;margin-bottom:16px">My Moderation Activity</h2>
              <?php if (!$my_logs): ?>
                <div class="empty-state"><div class="empty-icon">📋</div><p>No moderation actions yet.</p></div>
              <?php else: ?>
              <div class="panel">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                  <thead>
                    <tr style="background:var(--gray-6);text-align:left">
                      <th style="padding:10px 12px">Action</th>
                      <th style="padding:10px 12px">Target</th>
                      <th style="padding:10px 12px">Reason / Notes</th>
                      <th style="padding:10px 12px">Date</th>
                      <th style="padding:10px 12px">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($my_logs as $log): ?>
                    <tr class="log-row <?= $log['undone']?'undone':'' ?>" style="border-bottom:1px solid var(--gray-5)">
                      <td style="padding:10px 12px">
                        <span class="log-action-label"><?= ModLog::actionLabel($log['action']) ?></span>
                      </td>
                      <td style="padding:10px 12px">
                        <?php if ($log['target_type'] === 'pet'): ?>
                          <?php $target_pet = $petObj->findById($log['target_id']); ?>
                          <?php if ($target_pet): ?>
                            <div style="font-weight:700"><?= htmlspecialchars($target_pet['name']) ?></div>
                            <div style="color:var(--gray-4);font-size:11px;margin-bottom:4px"><?= ModLog::targetLabel($log['target_type']) ?></div>
                            <a href="../../views/pets/show.php?id=<?= encode_id($log['target_id']) ?>" class="btn btn-gray btn-sm" style="font-size:11px"><i class="fas fa-eye"></i> View Post</a>
                          <?php else: ?>
                            <span style="color:var(--gray-4)">[Deleted] <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
                          <?php endif; ?>
                        <?php elseif ($log['target_type'] === 'user'): ?>
                          <?php $target_user = $userObj->findById($log['target_id']); ?>
                          <?php if ($target_user): ?>
                            <div style="font-weight:700">@<?= htmlspecialchars($target_user['username']) ?></div>
                            <div style="color:var(--gray-4);font-size:11px;margin-bottom:4px"><?= htmlspecialchars($target_user['full_name']) ?> (<?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?>)</div>
                          <?php else: ?>
                            <span style="color:var(--gray-4)">[Deleted] <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
                          <?php endif; ?>
                        <?php else: ?>
                          <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?>
                        <?php endif; ?>
                      </td>
                
                      <td style="padding:10px 12px;color:var(--gray-3);max-width:200px">
                        <?php if ($log['action'] === 'removed_post'): ?>
                          <?php
                            $rs = $pdo->prepare("SELECT reason, details, created_at FROM reports WHERE pet_id=? AND status='removed' ORDER BY reviewed_at DESC LIMIT 1");
                            $rs->execute([$log['target_id']]);
                            $rd = $rs->fetch();
                          ?>
                          <?php if ($rd): ?>
                            <span style="color:var(--red);font-weight:700">Reason: </span><?= htmlspecialchars($rd['reason']) ?>
                            <?php if ($rd['details']): ?>
                              <div style="font-size:11px;color:var(--gray-4);margin-top:2px"><?= htmlspecialchars($rd['details']) ?></div>
                            <?php endif; ?>
                            <div style="font-size:11px;color:var(--gray-4);margin-top:2px">📅 <?= date('M j, Y', strtotime($rd['created_at'])) ?></div>
                          <?php else: ?>
                            <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
                          <?php endif; ?>
                        <?php else: ?>
                          <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
                        <?php endif; ?>
                      </td>
                      <td style="padding:10px 12px;color:var(--gray-4)">
                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                        <div style="font-size:11px"><?= date('g:i A', strtotime($log['created_at'])) ?></div>
                      </td>
                      <td style="padding:10px 12px">
                        <?php if ($log['undone']): ?>
                          <span style="color:var(--green);font-size:12px">↩️ Undone by admin</span>
                        <?php else: ?>
                          <span style="color:var(--gray-4);font-size:12px">Active</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>

            <!-- TAB: ACTIVITY LOG -->
            <?php elseif ($tab === 'logs' && is_admin()): ?>
              <h2 style="font-size:18px;margin-bottom:16px">Activity Log</h2>
              <?php if (!$mod_logs): ?>
                <div class="empty-state"><div class="empty-icon">📋</div><p>No moderation actions yet.</p></div>
              <?php else: ?>
              <div class="panel">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                  <thead>
                    <tr style="background:var(--gray-6);text-align:left">
                      <th style="padding:10px 12px">Moderator</th>
                      <th style="padding:10px 12px">Action</th>
                      <th style="padding:10px 12px">Target</th>
                      <th style="padding:10px 12px">Reason / Notes</th>
                      <th style="padding:10px 12px">Date</th>
                      <th style="padding:10px 12px">Undo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($mod_logs as $log): ?>
                    <tr class="log-row <?= $log['undone']?'undone':'' ?>" style="border-bottom:1px solid var(--gray-5)">
                      <td style="padding:10px 12px">
                        <strong><?= htmlspecialchars($log['mod_name']) ?></strong><br>
                        <span style="color:var(--gray-4)">@<?= htmlspecialchars($log['mod_username'] ?? '') ?></span>
                      </td>
                      <td style="padding:10px 12px">
                        <span class="log-action-label"><?= ModLog::actionLabel($log['action']) ?></span>
                      </td>
                      <td style="padding:10px 12px">
                        <?php if ($log['target_type'] === 'pet'): ?>
                          <?php $target_pet = $petObj->findById($log['target_id']); ?>
                          <?php if ($target_pet): ?>
                            <div style="font-weight:700"><?= htmlspecialchars($target_pet['name']) ?></div>
                            <div style="color:var(--gray-4);font-size:11px;margin-bottom:4px"><?= ModLog::targetLabel($log['target_type']) ?></div>
                            <a href="../../views/pets/show.php?id=<?= encode_id($log['target_id']) ?>" class="btn btn-gray btn-sm" style="font-size:11px"><i class="fas fa-eye"></i> View Post</a>
                          <?php else: ?>
                            <span style="color:var(--gray-4)">[Deleted] <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
                          <?php endif; ?>
                        <?php elseif ($log['target_type'] === 'user'): ?>
                          <?php $target_user = $userObj->findById($log['target_id']); ?>
                          <?php if ($target_user): ?>
                            <div style="font-weight:700">@<?= htmlspecialchars($target_user['username']) ?></div>
                            <div style="color:var(--gray-4);font-size:11px;margin-bottom:4px"><?= htmlspecialchars($target_user['full_name']) ?> (<?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?>)</div>
                          <?php else: ?>
                            <span style="color:var(--gray-4)">[Deleted] <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?></span>
                          <?php endif; ?>
                        <?php else: ?>
                          <?= ModLog::targetLabel($log['target_type']) ?> #<?= $log['target_id'] ?>
                        <?php endif; ?>
                      </td>
                      <td style="padding:10px 12px;color:var(--gray-3);max-width:200px">
                        <?php if ($log['action'] === 'removed_post'): ?>
                          <?php
                            $rs = $pdo->prepare("SELECT reason, details, created_at FROM reports WHERE pet_id=? AND status='removed' ORDER BY reviewed_at DESC LIMIT 1");
                            $rs->execute([$log['target_id']]);
                            $rd = $rs->fetch();
                          ?>
                          <?php if ($rd): ?>
                            <span style="color:var(--red);font-weight:700">Reason: </span><?= htmlspecialchars($rd['reason']) ?>
                            <?php if ($rd['details']): ?>
                              <div style="font-size:11px;color:var(--gray-4);margin-top:2px"><?= htmlspecialchars($rd['details']) ?></div>
                            <?php endif; ?>
                            <div style="font-size:11px;color:var(--gray-4);margin-top:2px">📅 <?= date('M j, Y', strtotime($rd['created_at'])) ?></div>
                          <?php else: ?>
                            <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
                          <?php endif; ?>
                        <?php else: ?>
                          <?= $log['notes'] ? htmlspecialchars($log['notes']) : '<span style="color:var(--gray-5)">—</span>' ?>
                        <?php endif; ?>
                      </td>
                      <td style="padding:10px 12px;color:var(--gray-4)">
                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                        <div style="font-size:11px"><?= date('g:i A', strtotime($log['created_at'])) ?></div>
                      </td>
                      <td style="padding:10px 12px">
                        <?php if ($log['undone']): ?>
                          <span style="color:var(--gray-4);font-size:12px">↩️ Undone by <?= htmlspecialchars($log['undone_by_name'] ?? 'admin') ?></span>
                        <?php else: ?>
                          <?php
                            // Get the most recent non-undone action for this pet
                            $most_recent_stmt = $pdo->prepare("
                              SELECT id, action FROM mod_logs 
                              WHERE target_type='pet' 
                                AND target_id=? 
                                AND undone=0
                              ORDER BY id DESC
                              LIMIT 1
                            ");
                            $most_recent_stmt->execute([$log['target_id']]);
                            $most_recent_log = $most_recent_stmt->fetch();
                            
                            // Show undo button ONLY if:
                            // 1. This log is the most recent action for this pet
                            // 2. The most recent action is a removed_post (the only undoable action)
                            $show_undo = $most_recent_log && 
                                        $most_recent_log['id'] == $log['id'] && 
                                        $most_recent_log['action'] === 'removed_post';
                          ?>
                          <?php if ($show_undo): ?>
                            <form method="POST" action="../../controllers/admin/undo.php" class="undo-form" data-label="<?= htmlspecialchars(ModLog::actionLabel($log['action'])) ?>">
                              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                              <input type="hidden" name="type" value="undo_log">
                              <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                              <input type="hidden" name="pet_id" value="<?= $log['target_id'] ?>">
                              <button class="btn btn-sm" style="background:#fef3c7;color:#92400e;border:none">↩️ Undo</button>
                            </form>
                          <?php else: ?>
                            <span style="color:var(--gray-5);font-size:12px">—</span>
                          <?php endif; ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>

            <!-- TAB: USERS (Admin only) -->
            <?php elseif ($tab === 'users' && is_admin()): ?>
              <?php
                // Helper to build user tab URLs preserving all active filters
                function user_url($overrides = []) {
                  $base = array_merge([
                    'tab'         => 'users',
                    'q'           => $_GET['q'] ?? '',
                    'user_sort'   => $_GET['user_sort'] ?? '',
                    'user_role'   => $_GET['user_role'] ?? '',
                    'user_status' => $_GET['user_status'] ?? '',
                  ], $overrides);
                  return '?' . http_build_query(array_filter($base, fn($v) => $v !== ''));
                }
                function user_sort_arrow($col, $current) {
                  if ($current === $col.'_az' || $current === $col) return ' <span style="color:var(--primary)">▲</span>';
                  if ($current === $col.'_za')                       return ' <span style="color:var(--primary)">▼</span>';
                  return ' <span style="color:var(--gray-5)">↕</span>';
                }
                function date_sort_arrow($current) {
                  if ($current === 'newest') return ' <span style="color:var(--primary)">▼</span>';
                  if ($current === 'oldest') return ' <span style="color:var(--primary)">▲</span>';
                  return ' <span style="color:var(--gray-5)">↕</span>';
                }
                function points_sort_arrow($current) {
                  if ($current === 'points_desc') return ' <span style="color:var(--primary)">▼</span>';
                  if ($current === 'points_asc')  return ' <span style="color:var(--primary)">▲</span>';
                  return ' <span style="color:var(--gray-5)">↕</span>';
                }
              ?>

              <!-- Search + Filter row -->
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap">
                <h2 style="font-size:18px;margin:0">All Users</h2>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">

                  <!-- Search -->
                  <form method="GET" style="display:flex;gap:6px;align-items:center">
                    <input type="hidden" name="tab" value="users">
                    <input type="hidden" name="user_sort"   value="<?= htmlspecialchars($user_sort) ?>">
                    <input type="hidden" name="user_role"   value="<?= htmlspecialchars($user_role) ?>">
                    <input type="hidden" name="user_status" value="<?= htmlspecialchars($user_status_filter) ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                           placeholder="Search name, username, email…"
                           style="font-size:13px;padding:6px 10px;border:1px solid var(--gray-5);border-radius:8px;min-width:220px">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                  </form>

                  <!-- Role filter -->
                  <div class="sort-wrap">
                    <button class="sort-btn" type="button">
                      <span>Role: <?= $user_role ? ucfirst($user_role) : 'All' ?></span>
                      <span class="sort-arrow">▼</span>
                    </button>
                    <div class="sort-dropdown">
                      <?php foreach ([''=>'All Roles','user'=>'User','moderator'=>'Moderator'] as $val=>$lbl): ?>
                        <a href="<?= user_url(['user_role'=>$val]) ?>"
                           class="sort-option <?= $user_role===$val?'active':'' ?>"><?= $lbl ?></a>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <!-- Status filter -->
                  <div class="sort-wrap">
                    <button class="sort-btn" type="button">
                      <span>Status: <?= $user_status_filter ? ucfirst($user_status_filter) : 'All' ?></span>
                      <span class="sort-arrow">▼</span>
                    </button>
                    <div class="sort-dropdown">
                      <?php foreach ([''=>'All Statuses','active'=>'Active','banned'=>'Banned'] as $val=>$lbl): ?>
                        <a href="<?= user_url(['user_status'=>$val]) ?>"
                           class="sort-option <?= $user_status_filter===$val?'active':'' ?>"><?= $lbl ?></a>
                      <?php endforeach; ?>
                    </div>
                  </div>

                </div>
              </div>

              <?php if (!$all_users): ?>
                <div class="empty-state"><div class="empty-icon">👥</div><p>No users found.</p></div>
              <?php else: ?>
              <div class="panel">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                  <thead>
                    <tr style="background:var(--gray-6);text-align:left">
                      <?php
                        $name_next   = ($user_sort === 'name_az')     ? 'name_za'     : 'name_az';
                        $date_next   = ($user_sort === 'newest')       ? 'oldest'      : 'newest';
                        $points_next = ($user_sort === 'points_desc')  ? 'points_asc'  : 'points_desc';
                      ?>
                      <th style="padding:10px 12px">
                        <a href="<?= user_url(['user_sort'=>$name_next]) ?>" style="color:inherit;text-decoration:none;white-space:nowrap">
                          User<?= user_sort_arrow('name', $user_sort) ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">Role</th>
                      <th style="padding:10px 12px">
                        <a href="<?= user_url(['user_sort'=>$points_next]) ?>" style="color:inherit;text-decoration:none;white-space:nowrap">
                          Points<?= points_sort_arrow($user_sort) ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">Status</th>
                      <th style="padding:10px 12px">
                        <a href="<?= user_url(['user_sort'=>$date_next]) ?>" style="color:inherit;text-decoration:none;white-space:nowrap">
                          Joined<?= date_sort_arrow($user_sort) ?>
                        </a>
                      </th>
                      <th style="padding:10px 12px">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($all_users as $u): ?>
                    <tr style="border-bottom:1px solid var(--gray-5)">
                      <td style="padding:10px 12px">
                        <strong><?= htmlspecialchars($u['full_name']) ?></strong><br>
                        <span style="color:var(--gray-4)">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['email']) ?></span>
                      </td>
                      <td style="padding:10px 12px"><span class="user-row-badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                      <td style="padding:10px 12px"><?= number_format($u['points']) ?> pts<?= $u['role']==='moderator'?' / '.$u['mod_points'].' mod pts':'' ?></td>
                      <td style="padding:10px 12px">
                        <?php if ($u['is_banned']): ?>
                          <span class="user-row-badge badge-banned">Banned<?= $u['ban_until'] ? ' (temp)' : ' (perm)' ?></span>
                        <?php else: ?>
                          <span style="color:var(--green);font-size:12px;font-weight:700">Active</span>
                        <?php endif; ?>
                      </td>
                      <td style="padding:10px 12px;color:var(--gray-4)"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                      <td style="padding:10px 12px">
                        <?php if ($u['id'] !== $_SESSION['user_id'] && $u['role'] !== 'admin'): ?>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                          <form method="POST" action="../../controllers/admin/assign_role.php" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--gray-5)">
                              <option value="user"      <?= $u['role']==='user'?'selected':'' ?>>User</option>
                              <option value="moderator" <?= $u['role']==='moderator'?'selected':'' ?>>Moderator</option>
                            </select>
                            <button class="btn btn-gray btn-sm" style="font-size:11px;padding:4px 8px">Set</button>
                          </form>
                          <?php if ($u['is_banned']): ?>
                            <form method="POST" action="/controllers/admin/ban.php" class="unban-form" data-name="<?= htmlspecialchars($u['full_name']) ?>">
                              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                              <input type="hidden" name="action" value="unban">
                              <button class="btn btn-green btn-sm" style="font-size:11px"><i class="fas fa-check"></i> Unban</button>
                            </form>
                          <?php else: ?>
                            <form method="POST" action="/controllers/admin/ban.php" style="display:inline">
                              <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                              <input type="hidden" name="action" value="ban">
                              <select name="duration" style="font-size:11px;padding:3px 6px;border-radius:6px;border:1px solid var(--gray-5)">
                                <option value="24">24 hours</option>
                                <option value="72">3 days</option>
                                <option value="168">7 days</option>
                                <option value="0">Permanent</option>
                              </select>
                              <button type="button" class="btn btn-red btn-sm ban-btn" data-name="<?= htmlspecialchars($u['full_name']) ?>" style="font-size:11px">🚫 Ban</button>
                            </form>
                          <?php endif; ?>
                        </div>
                        <?php else: ?>
                          <span style="font-size:11px;color:var(--gray-4)">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php endif; ?>
            <?php endif; ?>
          </main>
        </div>
      </div>
    </div>
  </div>
</div>

<?php footer_bar(); ?>
</body>
</html>