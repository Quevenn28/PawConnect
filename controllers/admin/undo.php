<?php
// controllers/admin/undo.php — admin undo actions
try {
    require_once '../../autoload.php';
    require_once __DIR__ . '/../../config/database.php';

    require_admin();
    verify_csrf();

    $type = $_POST['type'] ?? '';
    if (!$type) throw new Exception('Missing action type.');

    $petObj = new Pet($pdo);
    $logObj = new ModLog($pdo);

    if ($type === 'restore_pet') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($pet_id <= 0) throw new Exception('Invalid pet ID.');

        $petObj->restore($pet_id);
        $logObj->log($_SESSION['user_id'], ModLog::ACTION_RESTORED_POST, 'pet', $pet_id, 'Admin restored post');
        award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Restored a removed post', 'mod');
        
        // Flash success message
        flash('success', '✅ Post restored successfully and is now visible again.');

    } elseif ($type === 'hard_delete') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($pet_id <= 0) throw new Exception('Invalid pet ID.');

        // Delete first, then log the action. This way if deletion fails, no log is created.
        $petObj->hardDelete($pet_id);
        $logObj->log($_SESSION['user_id'], ModLog::ACTION_DELETED_POST, 'pet', $pet_id, 'Admin permanently deleted');
        award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Permanently deleted a post', 'mod');
        
        // Flash success message
        flash('success', '<i class="fas fa-trash"></i> Post permanently deleted.');

    } elseif ($type === 'undo_log') {
        $log_id = (int)($_POST['log_id'] ?? 0);
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($log_id <= 0 || $pet_id <= 0) throw new Exception('Invalid log or pet ID.');

        // Get the log entry to determine what action was taken
        $log = $logObj->findById($log_id);
        if (!$log) throw new Exception('Log entry not found.');
        if ($log['undone']) throw new Exception('This action has already been undone.');

        // Handle undo based on action type — only removed_post can be undone
        if ($log['action'] === 'removed_post') {
            $petObj->restore($pet_id);
        } else {
            throw new Exception('This action cannot be undone.');
        }

        $logObj->markUndone($log_id, $_SESSION['user_id']);
        award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Undid a moderator action', 'mod');

    } else {
        throw new Exception('Unknown action type: ' . htmlspecialchars($type));
    }

    // Redirect based on action type
    if ($type === 'restore_pet' || $type === 'hard_delete') {
        // Stay in pets tab after restore/delete
        $redirect_tab = 'pets';
    } else {
        // Undo log action — go to activity logs
        $redirect_tab = is_admin() ? 'logs' : 'mylogs';
    }
    header("Location: ../../views/admin/dashboard.php?tab={$redirect_tab}");
    exit;

} catch (Exception $e) {
    error_log("UNDO ERROR: " . $e->getMessage());
    // Flash and redirect instead of a raw 500
    flash('error', 'Action failed: ' . htmlspecialchars($e->getMessage()));
    header("Location: ../../views/admin/dashboard.php");
    exit;
}