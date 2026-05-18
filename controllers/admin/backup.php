<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_once '../layout/layout.php';

// Only admin can access
require_admin();

// Define backup directory (outside web root for security)
define('BACKUP_DIR', dirname(__DIR__, 2) . '/backups/');

// Create backup directory if it doesn't exist
if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

$action = $_GET['action'] ?? '';
$message = '';
$error = '';

// Handle Create Backup
if ($action === 'create') {
    verify_csrf();
    
    $timestamp = date('Y_m_d_H_i_s');
    $filename = "pawconnect_backup_{$timestamp}.sql";
    $filepath = BACKUP_DIR . $filename;
    
    // Get database credentials from config
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = 'root'; // Update with your actual password
    $dbname = 'pawconnectDB';
    
    // Set mysqldump path based on OS
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows path for MySQL Workbench
        $mysqldump_path = 'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe';
    } else {
        // Linux server path
        $mysqldump_path = 'mysqldump';
    }
    
    // Create backup using mysqldump
    $command = sprintf(
        '"%s" --host=%s --user=%s --password=%s %s > %s 2>&1',
        $mysqldump_path,
        escapeshellarg($dbhost),
        escapeshellarg($dbuser),
        escapeshellarg($dbpass),
        escapeshellarg($dbname),
        escapeshellarg($filepath)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
        // Log the backup action
        $logStmt = $pdo->prepare("
            INSERT INTO backup_logs (filename, filepath, filesize, created_by, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([$filename, $filepath, filesize($filepath), $_SESSION['user_id']]);
        
        flash('success', "Backup created successfully: {$filename}");
    } else {
        flash('error', 'Failed to create backup. Make sure mysqldump path is correct.');
    }
    
    header('Location: ../../views/admin/backup.php');
    exit;
}

// Handle Download Backup
if ($action === 'download' && isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filepath = BACKUP_DIR . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        flash('error', 'Backup file not found.');
        header('Location: ../../views/admin/backup.php');
        exit;
    }
}

// Handle Delete Backup
if ($action === 'delete' && isset($_GET['file'])) {
    verify_csrf();
    
    $filename = basename($_GET['file']);
    $filepath = BACKUP_DIR . $filename;
    
    if (file_exists($filepath)) {
        unlink($filepath);
        
        // Update log to mark as deleted
        $logStmt = $pdo->prepare("
            UPDATE backup_logs SET deleted_at = NOW() WHERE filename = ?
        ");
        $logStmt->execute([$filename]);
        
        flash('success', "Backup deleted: {$filename}");
    } else {
        flash('error', 'Backup file not found.');
    }
    
    header('Location: ../../views/admin/backup.php');
    exit;
}

// Handle Restore from uploaded file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['restore_file'])) {
    verify_csrf();
    
    $uploadedFile = $_FILES['restore_file'];
    
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'File upload failed.');
        header('Location: ../../views/admin/backup.php');
        exit;
    }
    
    if ($uploadedFile['type'] !== 'application/sql' && pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'sql') {
        flash('error', 'Only .sql files are allowed.');
        header('Location: ../../views/admin/backup.php');
        exit;
    }
    
    // Read the SQL file
    $sqlContent = file_get_contents($uploadedFile['tmp_name']);
    
    try {
        // Disable foreign key checks for safe restore
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Split SQL into individual statements
        $statements = explode(";\n", $sqlContent);
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    $errorCount++;
                    // Log error but continue
                    error_log("Restore error: " . $e->getMessage());
                }
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Log the restore action
        $logStmt = $pdo->prepare("
            INSERT INTO backup_logs (filename, action_type, created_by, created_at)
            VALUES (?, 'restore', ?, NOW())
        ");
        $logStmt->execute([$uploadedFile['name'], $_SESSION['user_id']]);
        
        flash('success', "Database restored successfully! Executed {$successCount} queries. ({$errorCount} errors skipped)");
        
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        flash('error', 'Restore failed: ' . $e->getMessage());
    }
    
    header('Location: ../../views/admin/backup.php');
    exit;
}

// Handle Restore from existing backup
if ($action === 'restore' && isset($_GET['file'])) {
    verify_csrf();
    
    $filename = basename($_GET['file']);
    $filepath = BACKUP_DIR . $filename;
    
    if (!file_exists($filepath)) {
        flash('error', 'Backup file not found.');
        header('Location: ../../views/admin/backup.php');
        exit;
    }
    
    $sqlContent = file_get_contents($filepath);
    
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $statements = explode(";\n", $sqlContent);
        $successCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    // Skip errors
                }
            }
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $logStmt = $pdo->prepare("
            INSERT INTO backup_logs (filename, action_type, created_by, created_at)
            VALUES (?, 'restore', ?, NOW())
        ");
        $logStmt->execute([$filename, $_SESSION['user_id']]);
        
        flash('success', "Database restored successfully from: {$filename}");
        
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        flash('error', 'Restore failed: ' . $e->getMessage());
    }
    
    header('Location: ../../views/admin/backup.php');
    exit;
}

// If no action, redirect back
header('Location: ../../views/admin/backup.php');
exit;
?>