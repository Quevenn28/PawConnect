<?php
// ============================================================
//  classes/Report.php
// ============================================================

class Report {

    private PDO $pdo;

    // Reasons a user can choose when reporting a post
    public const REASONS = [
        'Inappropriate content',
        'Fake or misleading listing',
        'Animal abuse or cruelty',
        'Spam or advertisement',
        'Already adopted but still listed',
        'Suspicious owner or account',
        'Offensive language',
        'Other',
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ----------------------------------------------------------
    //  READ
    // ----------------------------------------------------------

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM reports WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all pending reports for the moderation panel.
     */
    public function getPending(): array {
        return $this->pdo->query("
            SELECT r.*,
                   p.name AS pet_name, p.photo AS pet_photo,
                   p.species, p.status AS pet_status,
                   p.user_id AS pet_owner_id,
                   u.full_name AS reporter_name,
                   o.full_name AS owner_name, o.username AS owner_username
            FROM reports r
            JOIN pets  p ON p.id = r.pet_id
            JOIN users u ON u.id = r.reporter_id
            JOIN users o ON o.id = p.user_id
            WHERE r.status = 'pending'
            ORDER BY r.created_at DESC
        ")->fetchAll();
    }

    /**
     * Get all reports (admin view — includes reviewed/dismissed).
     */
    public function getAll(): array {
        return $this->pdo->query("
            SELECT r.*,
                   p.name AS pet_name, p.photo AS pet_photo,
                   p.species, p.status AS pet_status,
                   p.user_id AS pet_owner_id,
                   u.full_name AS reporter_name,
                   o.full_name AS owner_name,
                   m.full_name AS reviewer_name
            FROM reports r
            JOIN pets  p ON p.id  = r.pet_id
            JOIN users u ON u.id  = r.reporter_id
            JOIN users o ON o.id  = p.user_id
            LEFT JOIN users m ON m.id = r.reviewed_by
            ORDER BY r.created_at DESC
        ")->fetchAll();
    }

    /**
     * Check if a user already reported this pet.
     */
    public function hasReported(int $pet_id, int $user_id): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM reports WHERE pet_id=? AND reporter_id=?
        ");
        $stmt->execute([$pet_id, $user_id]);
        return (bool) $stmt->fetch();
    }

    // ----------------------------------------------------------
    //  CREATE
    // ----------------------------------------------------------

    public function create(
        int    $pet_id,
        int    $reporter_id,
        string $reason,
        string $details = ''
    ): bool {
        if (!in_array($reason, self::REASONS)) return false;

        try {
            $this->pdo->prepare("
                INSERT INTO reports (pet_id, reporter_id, reason, details)
                VALUES (?, ?, ?, ?)
            ")->execute([$pet_id, $reporter_id, $reason, $details ?: null]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // ----------------------------------------------------------
    //  UPDATE
    // ----------------------------------------------------------

    /**
     * Mark report as 'removed' — the pet was taken down.
     */
    public function markRemoved(int $report_id, int $reviewer_id): void {
        $this->pdo->prepare("
            UPDATE reports
            SET status='removed', reviewed_by=?, reviewed_at=NOW()
            WHERE id=?
        ")->execute([$reviewer_id, $report_id]);
    }

    /**
     * Mark report as 'dismissed' — no action taken.
     */
    public function dismiss(int $report_id, int $reviewer_id): void {
        $this->pdo->prepare("
            UPDATE reports
            SET status='dismissed', reviewed_by=?, reviewed_at=NOW()
            WHERE id=?
        ")->execute([$reviewer_id, $report_id]);
    }

    /**
     * Re-open a dismissed/removed report (admin undo).
     */
    public function reopen(int $report_id): void {
        $this->pdo->prepare("
            UPDATE reports
            SET status='pending', reviewed_by=NULL, reviewed_at=NULL
            WHERE id=?
        ")->execute([$report_id]);
    }

    /**
     * Get count of pending reports for navbar badge.
     */
    public function getPendingCount(): int {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM reports WHERE status='pending'"
        )->fetchColumn();
    }
}