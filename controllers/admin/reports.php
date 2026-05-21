<?php
// controllers/admin/reports.php — handle report actions (mod/admin)
require_once '../../autoload.php';
require_once '../../config/database.php';
require_moderator();
verify_csrf();

$action    = $_POST['action']    ?? '';
$report_id = (int)($_POST['report_id'] ?? 0);
$pet_id    = (int)($_POST['pet_id']    ?? 0);

// Preserve sort state across the redirect
$allowed_sorts = ['recent', 'oldest', 'most_reported'];
$sort = in_array($_POST['sort'] ?? '', $allowed_sorts) ? $_POST['sort'] : 'recent';
$sort_param = '&sort=' . urlencode($sort);

$reportObj = new Report($pdo);
$petObj    = new Pet($pdo);
$logObj    = new ModLog($pdo);

// Safety: moderators cannot act on their own posts
$report = $reportObj->findById($report_id);
if ($report) {
    $pet = $petObj->findById($report['pet_id']);
    if ($pet && $pet['user_id'] == $_SESSION['user_id']) {
        header("Location: ../../views/admin/reports.php?error=own_post" . $sort_param);
        exit;
    }
}

header('Content-Type: application/json');

if ($action === 'remove') {
    $reportObj->markRemoved($report_id, $_SESSION['user_id']);
    $petObj->softDelete($pet_id, $_SESSION['user_id'], 'admin');

    $notif = new Notification($pdo);
    $notif->create(
        $pet['user_id'],
        'Your listing "' . $pet['name'] . '" was removed by ' . ucfirst(get_role()) . ' due to report reason: ' . $report['reason'] . '. ' . ($report['details'] ? 'Details: ' . $report['details'] : ''),
        '/views/users/index.php'
    );

    $log_id = $logObj->log(
        $_SESSION['user_id'],
        ModLog::ACTION_REMOVED_POST,
        'pet', $pet_id,
        'Removed via report #'.$report_id
    );

    // Award mod points
    award_points($pdo, $_SESSION['user_id'], PTS_MOD_REMOVE_POST, 'Removed reported post', 'mod');

    echo json_encode(['success' => true, 'message' => 'Post removed successfully']);
    exit;

} elseif ($action === 'dismiss') {
    $reportObj->dismiss($report_id, $_SESSION['user_id']);

    $logObj->log(
        $_SESSION['user_id'],
        ModLog::ACTION_DISMISSED,
        'report', $report_id,
        'Dismissed report #'.$report_id
    );

    award_points($pdo, $_SESSION['user_id'], PTS_MOD_DISMISS, 'Dismissed report', 'mod');

    echo json_encode(['success' => true, 'message' => 'Report dismissed']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;