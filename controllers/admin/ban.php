<?php
// controllers/admin/ban.php — ban or unban a user
require_once '../../autoload.php';
require_once '../../config/database.php';
require_moderator();
verify_csrf();

$user_id  = (int)($_POST['user_id']  ?? 0);
$action   = $_POST['action']         ?? 'ban';
$duration = (int)($_POST['duration'] ?? 24); // hours; 0 = permanent
$reason   = trim($_POST['reason']    ?? 'Violation of community guidelines');

$userObj = new User($pdo);
$logObj  = new ModLog($pdo);
$target  = $userObj->findById($user_id);

// Safety checks
if (!$target || $target['id'] == $_SESSION['user_id'] || $target['role'] === 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Action not allowed']);
    exit;
}

// Moderators can only ban regular users (not other mods) and only for 24h
if (!is_admin()) {
    if ($target['role'] !== 'user') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Moderators can only ban regular users']);
        exit;
    }
    $duration = 24; // force 24h for mods
}

if ($action === 'unban') {
    $userObj->unban($user_id);
    $logObj->log($_SESSION['user_id'], ModLog::ACTION_UNBANNED_USER, 'user', $user_id, 'Unbanned user');
    award_points($pdo, $_SESSION['user_id'], PTS_ADMIN_ACTION, 'Unbanned a user', 'mod');
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => '✓ '.$target['full_name'].' has been unbanned']);
    exit;

} else {
    // Calculate ban_until
    $ban_until = null;
    $duration_text = '';
    if ($duration > 0) {
        $until     = new DateTime();
        $until->modify("+{$duration} hours");
        $ban_until = $until->format('Y-m-d H:i:s');
        if ($duration >= 24) {
            $days = ceil($duration / 24);
            $duration_text = $days . ' day' . ($days > 1 ? 's' : '');
        } else {
            $duration_text = $duration . ' hour' . ($duration > 1 ? 's' : '');
        }
    } else {
        $duration_text = 'permanently';
    }

    $userObj->ban($user_id, $reason, $ban_until);
    $logObj->log($_SESSION['user_id'], ModLog::ACTION_BANNED_USER, 'user', $user_id,
        'Banned '.($ban_until ? 'until '.$ban_until : 'permanently').'. Reason: '.$reason);

    award_points($pdo, $_SESSION['user_id'], PTS_MOD_REMOVE_POST, 'Banned a user', 'mod');
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => '🚫 '.$target['full_name'].' banned for '.$duration_text]);
    exit;
}
