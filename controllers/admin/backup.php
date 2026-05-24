<?php
// controllers/admin/backup.php
try {
    ob_start();

    require_once '../../autoload.php';
    require_once __DIR__ . '/../../config/database.php';

    require_admin();

    define('BACKUP_DIR', dirname(__DIR__, 2) . '/backups/');

    if (!file_exists(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0755, true);
    }

    $action = $_GET['action'] ?? '';

    // ─── CREATE BACKUP ────────────────────────────────────────────────────────
    if ($action === 'create') {
        verify_csrf();

        $sql = "SET FOREIGN_KEY_CHECKS = 0;\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $tbl) {
            $sql .= "DROP TABLE IF EXISTS `$tbl`;\n";

            $ctResult = $pdo->query("SHOW CREATE TABLE `$tbl`");
            if ($ctResult) {
                $ctRow = $ctResult->fetch();
                $sql .= $ctRow[1] . ";\n";
            }

            $data = $pdo->query("SELECT * FROM `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($data as $row) {
                $cols = implode("`,`", array_keys($row));
                $vals = array_map(function ($v) use ($pdo) {
                    // FIX: use proper SQL escaping instead of manual str_replace
                    // str_replace only escaped single quotes but not backslashes,
                    // causing syntax errors on restore if any value had a backslash.
                    if ($v === null) return "NULL";
                    return $pdo->quote($v);
                }, $row);
                $sql .= "INSERT INTO `$tbl` (`$cols`) VALUES (" . implode(",", $vals) . ");\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        $fn = "pawconnect_" . date('Y_m_d_H_i_s') . ".sql";
        $fp = BACKUP_DIR . $fn;
        file_put_contents($fp, $sql);

        try {
            $pdo->prepare("INSERT INTO backup_logs (filename, filepath, filesize, created_by, created_at) VALUES (?,?,?,?,NOW())")
                ->execute([$fn, $fp, filesize($fp), $_SESSION['user_id']]);
        } catch (Exception $e) {}

        flash('success', "Backup created: $fn");
        ob_end_clean();
        header('Location: ../../views/admin/backup.php');
        exit;
    }

    // ─── DOWNLOAD BACKUP ──────────────────────────────────────────────────────
    if ($action === 'download' && isset($_GET['file'])) {
        $filename = basename($_GET['file']);
        $filepath = BACKUP_DIR . $filename;

        if (file_exists($filepath)) {
            ob_end_clean();
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

        flash('error', 'Backup file not found.');
        ob_end_clean();
        header('Location: ../../views/admin/backup.php');
        exit;
    }

    // ─── DELETE BACKUP ────────────────────────────────────────────────────────
    if ($action === 'delete' && isset($_GET['file'])) {
        verify_csrf();

        $filename = basename($_GET['file']);
        $filepath = BACKUP_DIR . $filename;

        if (file_exists($filepath)) {
            @unlink($filepath);
            try {
                $pdo->prepare("UPDATE backup_logs SET deleted_at = NOW() WHERE filename = ?")
                    ->execute([$filename]);
            } catch (Exception $e) {}
            flash('success', "Backup deleted: {$filename}");
        } else {
            flash('error', 'Backup file not found.');
        }

        ob_end_clean();
        header('Location: ../../views/admin/backup.php');
        exit;
    }

    // ─── RESTORE FROM UPLOADED FILE ───────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['restore_file'])) {
        verify_csrf();

        $uploadedFile = $_FILES['restore_file'];

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'File upload failed.');
            ob_end_clean();
            header('Location: ../../views/admin/backup.php');
            exit;
        }

        if (strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION)) !== 'sql') {
            flash('error', 'Only .sql files are allowed.');
            ob_end_clean();
            header('Location: ../../views/admin/backup.php');
            exit;
        }

        $sqlContent = file_get_contents($uploadedFile['tmp_name']);
        [$successCount, $errorCount] = run_restore($pdo, $sqlContent);

        try {
            $pdo->prepare("INSERT INTO backup_logs (filename, action_type, created_by, created_at) VALUES (?, 'restore', ?, NOW())")
                ->execute([$uploadedFile['name'], $_SESSION['user_id']]);
        } catch (Exception $e) {}

        flash('success', "Database restored! {$successCount} statements executed. ({$errorCount} skipped)");
        ob_end_clean();
        header('Location: ../../views/admin/backup.php');
        exit;
    }

    // ─── RESTORE FROM EXISTING BACKUP ─────────────────────────────────────────
    if ($action === 'restore' && isset($_GET['file'])) {
        verify_csrf();

        $filename = basename($_GET['file']);
        $filepath = BACKUP_DIR . $filename;

        if (!file_exists($filepath)) {
            flash('error', 'Backup file not found.');
            ob_end_clean();
            header('Location: ../../views/admin/backup.php');
            exit;
        }

        $sqlContent = file_get_contents($filepath);
        [$successCount, $errorCount] = run_restore($pdo, $sqlContent);

        try {
            $pdo->prepare("INSERT INTO backup_logs (filename, action_type, created_by, created_at) VALUES (?, 'restore', ?, NOW())")
                ->execute([$filename, $_SESSION['user_id']]);
        } catch (Exception $e) {}

        flash('success', "Restored from: {$filename}. {$successCount} statements executed. ({$errorCount} skipped)");
        ob_end_clean();
        header('Location: ../../views/admin/backup.php');
        exit;
    }

    // ─── NO VALID ACTION ──────────────────────────────────────────────────────
    ob_end_clean();
    header('Location: ../../views/admin/backup.php');
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    error_log("CRITICAL BACKUP ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    flash('error', "Backup error: " . $e->getMessage());
    header('Location: ../../views/admin/backup.php');
    exit;
}

/**
 * Run a SQL restore safely.
 * Splits on ";\n", skips empty lines, re-enables FK checks even on failure.
 * Returns [successCount, errorCount].
 */
function run_restore(PDO $pdo, string $sqlContent): array {
    $successCount = 0;
    $errorCount   = 0;

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Split on statement boundaries — handles both ";\n" and ";\r\n"
        $statements = preg_split('/;\r?\n/', $sqlContent);

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '' || $statement === '--') continue;
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                $errorCount++;
                error_log("Restore stmt error: " . $e->getMessage());
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    } catch (Exception $e) {
        @$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        throw $e; // re-throw so outer catch can flash the error
    }

    return [$successCount, $errorCount];
}