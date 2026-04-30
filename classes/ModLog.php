<?php
// ============================================================
//  classes/ModLog.php
// ============================================================

class ModLog {

    private PDO $pdo;

    public const ACTION_REMOVED_POST    = 'removed_post';
    public const ACTION_DISMISSED       = 'dismissed_report';
    public const ACTION_BANNED_USER     = 'banned_user';
    public const ACTION_UNBANNED_USER   = 'unbanned_user';
    public const ACTION_RESTORED_POST   = 'restored_post';
    public const ACTION_DELETED_POST    = 'deleted_post';     // admin hard delete

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ----------------------------------------------------------
    //  READ
    // ----------------------------------------------------------

    /**
     * Get logs for a specific moderator (their own activity).
     */
    public function getByMod(int $mod_id): array {
        $stmt = $this->pdo->prepare("
            SELECT ml.*,
                   u.full_name AS mod_name,
                   a.full_name AS undone_by_name
            FROM mod_logs ml
            JOIN users u ON u.id = ml.mod_id
            LEFT JOIN users a ON a.id = ml.undone_by
            WHERE ml.mod_id = ?
            ORDER BY ml.created_at DESC
        ");
        $stmt->execute([$mod_id]);
        return $stmt->fetchAll();
    }

    /**
     * Admin: Get ALL mod logs across all moderators.
     */
    public function getAll(): array {
        return $this->pdo->query("
            SELECT ml.*,
                   u.full_name AS mod_name, u.username AS mod_username,
                   a.full_name AS undone_by_name
            FROM mod_logs ml
            JOIN users u ON u.id = ml.mod_id
            LEFT JOIN users a ON a.id = ml.undone_by
            ORDER BY ml.created_at DESC
        ")->fetchAll();
    }

    /**
     * Get a specific log entry.
     */
    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM mod_logs WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get logs for a specific target (e.g., a specific pet or user).
     */
    public function getForTarget(string $target_type, int $target_id): array {
        $stmt = $this->pdo->prepare("
            SELECT ml.*, u.full_name AS mod_name
            FROM mod_logs ml
            JOIN users u ON u.id = ml.mod_id
            WHERE ml.target_type=? AND ml.target_id=?
            ORDER BY ml.created_at DESC
        ");
        $stmt->execute([$target_type, $target_id]);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------
    //  CREATE
    // ----------------------------------------------------------

    public function log(
        int    $mod_id,
        string $action,
        string $target_type,
        int    $target_id,
        string $notes = ''
    ): int {
        $this->pdo->prepare("
            INSERT INTO mod_logs (mod_id, action, target_type, target_id, notes)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$mod_id, $action, $target_type, $target_id, $notes ?: null]);

        return (int) $this->pdo->lastInsertId();
    }

    // ----------------------------------------------------------
    //  UNDO (Admin only)
    // ----------------------------------------------------------

    /**
     * Mark a log entry as undone.
     */
    public function markUndone(int $log_id, int $admin_id): void {
        $this->pdo->prepare("
            UPDATE mod_logs
            SET undone=1, undone_by=?, undone_at=NOW()
            WHERE id=?
        ")->execute([$admin_id, $log_id]);
    }

    // ----------------------------------------------------------
    //  HELPERS
    // ----------------------------------------------------------

    /**
     * Human-readable action label.
     */
    public static function actionLabel(string $action): string {
        return match($action) {
            self::ACTION_REMOVED_POST  => '🗑️ Removed Post',
            self::ACTION_DISMISSED     => '✅ Dismissed Report',
            self::ACTION_BANNED_USER   => '🚫 Banned User',
            self::ACTION_UNBANNED_USER => '✅ Unbanned User',
            self::ACTION_RESTORED_POST => '♻️ Restored Post',
            self::ACTION_DELETED_POST  => '❌ Permanently Deleted Post',
            default                    => ucfirst(str_replace('_', ' ', $action)),
        };
    }

    /**
     * Human-readable target type label.
     */
    public static function targetLabel(string $type): string {
        return match($type) {
            'pet'    => 'Pet Listing',
            'user'   => 'User Account',
            'report' => 'Report',
            default  => ucfirst($type),
        };
    }
}
