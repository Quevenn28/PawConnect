<?php
// ============================================================
//  config/database.php
//  Database connection + all global helper functions
// ============================================================

session_start();

$host = "localhost";
$db   = "pawconnectDB";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,         PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ============================================================
//  CSRF HELPERS
// ============================================================
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf() {
    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== ($_SESSION['csrf'] ?? '')
    ) {
        die("Invalid request.");
    }
}

// ============================================================
//  AUTH HELPERS
// ============================================================
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /login.php");
        exit;
    }
}

function get_role() {
    return $_SESSION['role'] ?? 'user';
}

function is_admin() {
    return get_role() === 'admin';
}

function is_moderator() {
    return in_array(get_role(), ['moderator', 'admin']);
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: /index.php");
        exit;
    }
}

function require_moderator() {
    require_login();
    if (!is_moderator()) {
        header("Location: /index.php");
        exit;
    }
}

// ============================================================
//  BAN HELPERS
// ============================================================

/**
 * Check if a user is currently banned.
 * Automatically lifts expired temporary bans.
 */
function check_ban(array $user, $pdo): bool {
    if (!$user['is_banned']) return false;

    // Temporary ban — check if it has expired
    if ($user['ban_until'] !== null) {
        if (new DateTime() > new DateTime($user['ban_until'])) {
            // Ban expired — lift it automatically
            $pdo->prepare("
                UPDATE users
                SET is_banned=0, ban_reason=NULL, ban_until=NULL
                WHERE id=?
            ")->execute([$user['id']]);
            return false;
        }
    }

    return true; // Still banned
}

// ============================================================
//  POINTS HELPERS
// ============================================================

/**
 * Award points to a user and log the reason.
 * @param int    $user_id
 * @param int    $points   Amount to add
 * @param string $reason   Human-readable reason
 * @param string $type     'general' or 'mod'
 */
function award_points($pdo, int $user_id, int $points, string $reason, string $type = 'general') {
    $col = $type === 'mod' ? 'mod_points' : 'points';

    $pdo->prepare("UPDATE users SET $col = $col + ? WHERE id=?")
        ->execute([$points, $user_id]);

    $pdo->prepare("
        INSERT INTO point_logs (user_id, points, type, reason)
        VALUES (?, ?, ?, ?)
    ")->execute([$user_id, $points, $type, $reason]);
}

// ============================================================
//  TITLE HELPERS
// ============================================================

/**
 * Returns rehomer title based on number of pets posted.
 */
function get_rehomer_title(int $pets_posted): string {
    if ($pets_posted >= 21) return 'Pawsome Hero';
    if ($pets_posted >= 11) return 'Rescue Advocate';
    if ($pets_posted >= 6)  return 'Shelter Helper';
    if ($pets_posted >= 3)  return 'Pet Friend';
    return 'Newcomer';
}

/**
 * Returns adopter title based on number of pets adopted.
 */
function get_adopter_title(int $pets_adopted): string {
    if ($pets_adopted >= 11) return 'Adoption Champion';
    if ($pets_adopted >= 6)  return 'Forever Family';
    if ($pets_adopted >= 3)  return 'Loving Home';
    if ($pets_adopted >= 1)  return 'First-Time Adopter';
    return 'Curious Soul';
}

/**
 * Returns moderator title based on mod_points.
 * Only shown if user is moderator or admin.
 */
function get_mod_title(int $mod_points): string {
    if ($mod_points >= 1000) return 'Senior Moderator';
    if ($mod_points >= 500)  return 'Junior Moderator';
    if ($mod_points >= 200)  return 'Rookie Moderator';
    return 'Moderator-in-Training';
}

/**
 * Returns emoji badge for rehomer title.
 */
function get_rehomer_badge(string $title): string {
    return match($title) {
        'Pawsome Hero'     => '🏆',
        'Rescue Advocate'  => '🌟',
        'Shelter Helper'   => '💛',
        'Pet Friend'       => '🐾',
        default            => '🌱',
    };
}

/**
 * Returns emoji badge for adopter title.
 */
function get_adopter_badge(string $title): string {
    return match($title) {
        'Adoption Champion' => '🏆',
        'Forever Family'    => '🏠',
        'Loving Home'       => '💙',
        'First-Time Adopter'=> '🌟',
        default             => '🔍',
    };
}

/**
 * Returns emoji badge for moderator title.
 */
function get_mod_badge(string $title): string {
    return match($title) {
        'Senior Moderator'       => '🛡️',
        'Junior Moderator'       => '⚔️',
        'Rookie Moderator'       => '🔰',
        default                  => '📋',
    };
}

// ============================================================
//  POINTS CONSTANTS
//  Centralized so you only change numbers in one place
// ============================================================
define('PTS_REGISTER',          5);
define('PTS_POST_PET',         10);
define('PTS_PET_ADOPTED',      15);   // awarded to owner
define('PTS_ADOPT_PET',        30);   // awarded to adopter
define('PTS_MOD_REMOVE_POST',  15);   // mod_points
define('PTS_MOD_DISMISS',       5);   // mod_points
define('PTS_ADMIN_ACTION',     15);   // mod_points


function encode_id(int $id): string {
    return base64_encode($id * 7919 + 12345);
}

function decode_id(string $hash): int {
    $decoded = base64_decode($hash);
    if (!$decoded) return 0;
    return (int)(($decoded - 12345) / 7919);
}