<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/layout.php';
require_moderator();

$userObj = new User($pdo);
$q       = trim($_GET['q'] ?? '');
$users   = $q ? $userObj->search($q) : $userObj->getAll();
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management — PawConnect</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php navbar($pdo); ?>

<div class="admin-wrap">
  <div class="admin-header">
    <div>
      <h1>👥 User Management</h1>
      <p style="color:var(--gray-4);font-size:14px">Search, manage roles, and moderate accounts</p>
    </div>
    <a href="dashboard.php" class="btn btn-gray btn-sm">← Back to Panel</a>
  </div>

  <?php if ($success): ?><div class="alert alert-success auto-dismiss">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- Search -->
  <form method="GET" class="search-admin">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name, username, or email…">
    <button type="submit" class="btn btn-primary">🔍 Search</button>
    <?php if ($q): ?><a href="users.php" class="btn btn-gray">Clear</a><?php endif; ?>
  </form>

  <!-- Users Table -->
  <div class="panel" style="padding:0;overflow:hidden">
    <div class="panel-header"><h2>All Users (<?= count($users) ?>)</h2></div>
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead style="background:var(--gray-6);font-size:12px;text-transform:uppercase;letter-spacing:1px;color:var(--gray-3)">
        <tr>
          <th style="padding:10px 16px;text-align:left">User</th>
          <th style="padding:10px 16px;text-align:left">Email</th>
          <th style="padding:10px 16px;text-align:center">Role</th>
          <th style="padding:10px 16px;text-align:center">Points</th>
          <th style="padding:10px 16px;text-align:center">Status</th>
          <?php if (is_admin()): ?>
          <th style="padding:10px 16px;text-align:center">Actions</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <?php
          $is_banned_now = $u['is_banned'] && ($u['ban_until'] === null || new DateTime() < new DateTime($u['ban_until']));
        ?>
        <tr style="border-top:1px solid var(--gray-5);<?= $is_banned_now ? 'background:#fef2f2' : '' ?>">
          <td style="padding:12px 16px">
            <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($u['full_name']) ?></div>
            <div style="font-size:12px;color:var(--gray-4)">@<?= htmlspecialchars($u['username']) ?></div>
          </td>
          <td style="padding:12px 16px;font-size:13px;color:var(--gray-3)"><?= htmlspecialchars($u['email']) ?></td>
          <td style="padding:12px 16px;text-align:center">
            <span class="user-row-badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span>
          </td>
          <td style="padding:12px 16px;text-align:center;font-weight:700;color:var(--orange)"><?= number_format($u['points']) ?></td>
          <td style="padding:12px 16px;text-align:center">
            <?php if ($is_banned_now): ?>
              <span class="user-row-badge badge-banned">
                <?= $u['ban_until'] ? 'Temp Ban' : 'Permanent Ban' ?>
              </span>
            <?php else: ?>
              <span style="color:var(--green);font-size:12px;font-weight:700"><i class="fas fa-check"></i> Active</span>
            <?php endif; ?>
          </td>
          <?php if (is_admin()): ?>
          <td style="padding:12px 16px;text-align:center">
            <?php if ($u['id'] != $_SESSION['user_id'] && $u['role'] !== 'admin'): ?>
            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
              <!-- Role toggle -->
              <?php if ($u['role'] === 'user'): ?>
                <form method="POST" action="../../controllers/admin/assign_role.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="role" value="moderator">
                  <button class="btn btn-sm" style="background:#ede9fe;color:#5b21b6;border:none">🛡️ Make Mod</button>
                </form>
              <?php elseif ($u['role'] === 'moderator'): ?>
                <form method="POST" action="../../controllers/admin/assign_role.php">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="role" value="user">
                  <button class="btn btn-gray btn-sm">↓ Demote</button>
                </form>
              <?php endif; ?>

              <!-- Ban/Unban -->
              <?php if ($is_banned_now): ?>
                <form method="POST" action="/controllers/admin/ban.php" class="unban-form" data-name="<?= htmlspecialchars($u['full_name']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="unban">
                  <button class="btn btn-green btn-sm"><i class="fas fa-check"></i> Unban</button>
                </form>
              <?php else: ?>
                <button class="btn btn-red btn-sm open-ban-modal"
                  data-id="<?= $u['id'] ?>"
                  data-name="<?= htmlspecialchars($u['full_name']) ?>"
                  data-role="<?= $u['role'] ?>">
                  🚫 Ban
                </button>
              <?php endif; ?>
            </div>
            <?php else: ?>
              <span style="font-size:12px;color:var(--gray-4)"><?= $u['id'] == $_SESSION['user_id'] ? 'You' : 'Protected' ?></span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Ban Modal -->
<div id="banModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:white;border-radius:16px;padding:32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3)">
    <h3 style="margin-bottom:8px">🚫 Ban <span id="banUserName"></span></h3>
    <p style="font-size:14px;color:var(--gray-3);margin-bottom:20px">Choose ban type and provide a reason.</p>
    <form method="POST" action="/controllers/admin/ban.php" id="banForm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="user_id" id="banUserId">
      <input type="hidden" name="action" value="ban">
      <div class="form-group">
        <label>Ban Duration</label>
        <select name="duration" id="banDuration" onchange="toggleCustom()">
          <option value="24h">24 Hours (Temporary)</option>
          <option value="7d">7 Days</option>
          <option value="30d">30 Days</option>
          <option value="permanent">Permanent</option>
        </select>
      </div>
      <div class="form-group" id="modBanNote" style="display:none">
        <div class="alert alert-error" style="font-size:12px">⚠️ As a moderator, you can only issue 24-hour bans.</div>
      </div>
      <div class="form-group">
        <label>Reason <span class="req">*</span></label>
        <textarea name="reason" rows="3" placeholder="Why is this user being banned?" required></textarea>
      </div>
      <div style="display:flex;gap:8px;margin-top:16px">
        <button type="button" onclick="closeBanModal()" class="btn btn-gray">Cancel</button>
        <button type="submit" class="btn btn-red">Confirm Ban</button>
      </div>
    </form>
  </div>
</div>

<script>
const isMod = <?= is_admin() ? 'false' : 'true' ?>;
</script>

<script src="/assets/js/admin.js"></script>

<?php footer_bar(); ?>
</body>
</html>
