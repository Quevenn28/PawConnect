<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';
require_admin();

define('BACKUP_DIR', dirname(__DIR__, 2) . '/backups/');

// Get all backup files
$backups = [];
if (file_exists(BACKUP_DIR)) {
    $files = scandir(BACKUP_DIR);
    foreach ($files as $file) {
        if (preg_match('/\.sql$/', $file)) {
            $filepath = BACKUP_DIR . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($filepath),
                'modified' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    usort($backups, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
}

// Get backup logs
$logs = $pdo->prepare("
    SELECT bl.*, u.username 
    FROM backup_logs bl
    JOIN users u ON u.id = bl.created_by
    ORDER BY bl.created_at DESC
    LIMIT 20
");
$logs->execute();
$logs = $logs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - PawConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/admin.js"></script>
</head>
<body class="has-fixed-sidebar">
<?php navbar($pdo); ?>

<div class="admin-wrap page-with-fixed-sidebar">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 sidebar-wrapper">
                <aside class="admin-sidebar">
                    <button class="sidebar-close-btn" id="closeSidebarBtn" aria-label="Close sidebar">✕</button>
                    <div class="admin-sidebar-title">Admin Tools</div>
                    <div class="admin-tabs">
                        <a href="dashboard.php?tab=reports" class="admin-tab">🚩 Reports</a>
                        <a href="dashboard.php?tab=pets" class="admin-tab">🐾 All Pets</a>
                        <a href="dashboard.php?tab=mylogs" class="admin-tab">📋 My Activity</a>
                        <a href="dashboard.php?tab=logs" class="admin-tab">🔍 All Mod Logs</a>
                        <a href="dashboard.php?tab=users" class="admin-tab">👥 Users</a>
                        <a href="backup.php" class="admin-tab active">💾 Backup & Restore</a>
                    </div>
                </aside>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9 main-content-wrapper">
                <div class="content-header-wrapper">
                    <button class="content-open-btn" id="openSidebarBtn" aria-label="Open sidebar">☰</button>
                    <main class="admin-main" style="width: 100%;">
                        
                        <div class="panel">
                            <div class="panel-header">
                                <h2>💾 Database Backup</h2>
                            </div>
                            
                            <form method="POST" action="../../controllers/admin/backup.php?action=create" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Create a new database backup? This may take a few seconds.')">
                                    🔄 Create Backup Now
                                </button>
                            </form>
                            
                            <p class="text-muted" style="margin-top: 12px;">
                                Creates a full SQL backup of your entire database.
                            </p>
                        </div>
                        
                        <div class="panel">
                            <div class="panel-header">
                                <h2>📁 Available Backups</h2>
                            </div>
                            
                            <?php if (empty($backups)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">💾</div>
                                    <p>No backups found. Click "Create Backup Now" to get started.</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Filename</th>
                                            <th>Size</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($backup['filename']) ?></td>
                                            <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                            <td><?= htmlspecialchars($backup['modified']) ?></td>
                                            <td>
                                                <div style="display: flex; gap: 8px;">
                                                    <a href="../../controllers/admin/backup.php?action=download&file=<?= urlencode($backup['filename']) ?>" class="btn btn-blue btn-sm">⬇️ Download</a>
                                                    <form method="POST" action="../../controllers/admin/backup.php?action=restore&file=<?= urlencode($backup['filename']) ?>" style="display: inline;" class="restore-form" data-file="<?= htmlspecialchars($backup['filename']) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <button type="submit" class="btn btn-green btn-sm">♻️ Restore</button>
                                                    </form>
                                                    <form method="POST" action="../../controllers/admin/backup.php?action=delete&file=<?= urlencode($backup['filename']) ?>" style="display: inline;" class="delete-backup-form" data-file="<?= htmlspecialchars($backup['filename']) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <button type="submit" class="btn btn-red btn-sm">🗑️ Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <div class="panel">
                            <div class="panel-header">
                                <h2>📂 Restore from Upload</h2>
                            </div>
                            
                            <form method="POST" action="../../controllers/admin/backup.php" enctype="multipart/form-data" class="upload-restore-form">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <div class="form-group">
                                    <label>Upload SQL Backup File</label>
                                    <input type="file" name="restore_file" accept=".sql" required>
                                </div>
                                <button type="submit" class="btn btn-primary">📂 Restore from Upload</button>
                            </form>
                            <p class="text-muted" style="margin-top: 12px;">
                                <strong>Warning:</strong> Restoring will overwrite your current database.
                            </p>
                        </div>
                        
                        <div class="panel">
                            <div class="panel-header">
                                <h2>📋 Backup History Log</h2>
                            </div>
                            
                            <?php if (empty($logs)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📋</div>
                                    <p>No backup logs yet.</p>
                                </div>
                            <?php else: ?>
                                <table class="data-table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Filename</th>
                                            <th>Performed By</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <?php if ($log['action_type'] === 'backup'): ?>
                                                    <span style="color: var(--green);">✅ Backup</span>
                                                <?php else: ?>
                                                    <span style="color: var(--orange);">♻️ Restore</span>
                                                <?php endif; ?>
                                             </td>
                                            <td><?= htmlspecialchars($log['filename']) ?></td>
                                            <td><?= htmlspecialchars($log['username']) ?></td>
                                            <td><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                    </main>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/backup.js"></script>

<?php footer_bar(); ?>
</body>
</html>