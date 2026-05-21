<?php
// controllers/admin/undo.php — admin undo actions
try {
    require_once '../../autoload.php';
    require_once '../../config/database.php';

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

    } elseif ($type === 'hard_delete') {
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($pet_id <= 0) throw new Exception('Invalid pet ID.');

        // Log BEFORE deleting so the record still exists when log is written
        $logObj->log($_SESSION['user_id'], ModLog::ACTION_DELETED_POST, 'pet', $pet_id, 'Admin permanently deleted');
        $petObj->hardDelete($pet_id);
        award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Permanently deleted a post', 'mod');

    } elseif ($type === 'undo_log') {
        $log_id = (int)($_POST['log_id'] ?? 0);
        $pet_id = (int)($_POST['pet_id'] ?? 0);
        if ($log_id <= 0 || $pet_id <= 0) throw new Exception('Invalid log or pet ID.');

        $petObj->restore($pet_id);
        $logObj->markUndone($log_id, $_SESSION['user_id']);
        award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Undid a moderator action', 'mod');

    } else {
        throw new Exception('Unknown action type: ' . htmlspecialchars($type));
    }

    $redirect_tab = is_admin() ? 'logs' : 'mylogs';
    header("Location: ../../views/admin/dashboard.php?tab={$redirect_tab}");
    exit;

} catch (Exception $e) {
    error_log("UNDO ERROR: " . $e->getMessage());
    // Flash and redirect instead of a raw 500
    flash('error', 'Action failed: ' . htmlspecialchars($e->getMessage()));
    header("Location: ../../views/admin/dashboard.php");
    exit;
}