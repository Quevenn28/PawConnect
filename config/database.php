
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// rest of your file...
// ============================================================
//  config/database.php
//  Database connection + all global helper functions
// ============================================================

session_start();

$host = "it208.2025.ccsit.info";
$db   = "paw_connect_db";
$user = "paw_connect_user";
$pass = "61YydlfKlNWOhVTs";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,         PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Automatically lift any expired temporary bans on each request.
    try {
        $pdo->exec("UPDATE users SET is_banned=0, ban_reason=NULL, ban_until=NULL WHERE is_banned=1 AND ban_until IS NOT NULL AND ban_until <= NOW()");
    } catch (PDOException $e) {
        // Ignore if users table does not exist yet.
    }

    // Ensure pet metadata columns exist for content quality enhancements.
    try {
        $columnsToAdd = [
            'health_info'        => 'TEXT NULL',
            'vaccinated'         => "ENUM('Yes','No','Unknown') DEFAULT 'Unknown'",
            'spayed_neutered'    => "ENUM('Yes','No','Unknown') DEFAULT 'Unknown'",
            'good_with_children' => "ENUM('Yes','No','Unknown') DEFAULT 'Unknown'"
        ];

        foreach ($columnsToAdd as $column => $definition) {
            $exists = $pdo->query("SHOW COLUMNS FROM pets LIKE '$column'")->fetch();
            if (!$exists) {
                $pdo->exec("ALTER TABLE pets ADD COLUMN $column $definition");
            }
        }
    } catch (PDOException $e) {
        // Ignore if the pets table does not exist yet or schema cannot be altered.
    }

    // Ensure password reset columns exist
    try {
        $exists = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_reset_token'")->fetch();
        if (!$exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL, ADD COLUMN password_reset_expires DATETIME NULL");
        }
    } catch (PDOException $e) {
        // Ignore if the users table does not exist yet or schema cannot be altered.
    }

    // Create backup_logs table if it doesn't exist
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'backup_logs'")->fetch();
        if (!$tableExists) {
            $pdo->exec("
                CREATE TABLE backup_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    filepath VARCHAR(500),
                    filesize INT,
                    action_type ENUM('backup', 'restore') DEFAULT 'backup',
                    created_by INT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME NULL,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        }
    } catch (PDOException $e) {
        // Ignore if the table cannot be created.
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ============================================================
//  FLASH NOTICE HELPERS
// ============================================================
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
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

    if ($user['ban_until'] !== null) {
        if (new DateTime() > new DateTime($user['ban_until'])) {
            $pdo->prepare("
                UPDATE users
                SET is_banned=0, ban_reason=NULL, ban_until=NULL
                WHERE id=?
            ")->execute([$user['id']]);
            return false;
        }
    }

    return true;
}

// ============================================================
//  POINTS HELPERS
// ============================================================

/**
 * Award points to a user and log the reason.
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
//  POINTS EARNED HELPERS
//  Calculate points earned from specific action categories
//  by reading point_logs — used for title calculations.
// ============================================================

/**
 * Get total rehomer points earned by a user.
 * Counts points from posting pets and having them adopted.
 */
function get_rehomer_points($pdo, int $user_id): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(points), 0)
        FROM point_logs
        WHERE user_id = ?
          AND type = 'general'
          AND (reason LIKE 'Posted pet%' OR reason LIKE 'Pet adopted%')
    ");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get total adopter points earned by a user.
 * Counts points from adopting pets.
 */
function get_adopter_points($pdo, int $user_id): int {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(points), 0)
        FROM point_logs
        WHERE user_id = ?
          AND type = 'general'
          AND reason LIKE 'Adopted a pet%'
    ");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

// ============================================================
//  TITLE HELPERS
// ============================================================

/**
 * Returns rehomer title based on rehomer points earned.
 */
function get_rehomer_title(int $rehomer_points): string {
    if ($rehomer_points >= 500) return 'Pawsome Hero';
    if ($rehomer_points >= 200) return 'Rescue Advocate';
    if ($rehomer_points >= 100) return 'Shelter Helper';
    if ($rehomer_points >= 25)  return 'Pet Friend';
    return 'Newcomer';
}

/**
 * Returns adopter title based on adopter points earned.
 */
function get_adopter_title(int $adopter_points): string {
    if ($adopter_points >= 600) return 'Adoption Champion';
    if ($adopter_points >= 300) return 'Forever Family';
    if ($adopter_points >= 150) return 'Loving Home';
    if ($adopter_points >= 30)  return 'New Adopter';
    return 'Curious Soul';
}

/**
 * Returns moderator title based on mod_points.
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
        'Pawsome Hero'    => '🏆',
        'Rescue Advocate' => '🌟',
        'Shelter Helper'  => '💛',
        'Pet Friend'      => '🐾',
        default           => '🌱',
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
        'New Adopter'       => '🌟',
        default             => '🔍',
    };
}

/**
 * Returns emoji badge for moderator title.
 */
function get_mod_badge(string $title): string {
    return match($title) {
        'Senior Moderator' => '🛡️',
        'Junior Moderator' => '⚔️',
        'Rookie Moderator' => '🔰',
        default            => '📋',
    };
}

// ============================================================
//  POINTS CONSTANTS
// ============================================================
define('PTS_REGISTER',         5);
define('PTS_POST_PET',        10);
define('PTS_PET_ADOPTED',     15);   // awarded to owner
define('PTS_ADOPT_PET',       30);   // awarded to adopter
define('PTS_MOD_REMOVE_POST', 15);   // mod_points
define('PTS_MOD_DISMISS',      5);   // mod_points
define('PTS_ADMIN_ACTION',    15);   // mod_points

// ============================================================
//  ID ENCODE/DECODE HELPERS
// ============================================================
function encode_id(int $id): string {
    return base64_encode($id * 7919 + 12345);
}

function decode_id(string $hash): int {
    $decoded = base64_decode($hash);
    if (!$decoded) return 0;
    return (int)(($decoded - 12345) / 7919);
}
