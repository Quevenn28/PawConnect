<?php
// controllers/admin/undo.php — admin undo actions
require_once '../../autoload.php';
require_once '../../config/database.php';
require_admin();
verify_csrf();

$type   = $_POST['type']   ?? '';
$petObj = new Pet($pdo);
$logObj = new ModLog($pdo);

if ($type === 'restore_pet') {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $petObj->restore($pet_id);
    $logObj->log($_SESSION['user_id'], ModLog::ACTION_RESTORED_POST, 'pet', $pet_id, 'Admin restored post');
    award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Restored a removed post', 'mod');

} elseif ($type === 'hard_delete') {
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    // Log before deleting
    $logObj->log($_SESSION['user_id'], ModLog::ACTION_DELETED_POST, 'pet', $pet_id, 'Admin permanently deleted');
    $petObj->hardDelete($pet_id);
    award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Permanently deleted a post', 'mod');

} elseif ($type === 'undo_log') {
    $log_id = (int)($_POST['log_id'] ?? 0);
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    // Restore the pet
    $petObj->restore($pet_id);
    // Mark the log as undone
    $logObj->markUndone($log_id, $_SESSION['user_id']);
    award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Undid a moderator action', 'mod');
}

header("Location: ../../views/admin/dashboard.php?tab=logs");
exit;
