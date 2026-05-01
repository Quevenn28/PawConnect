<?php
// ============================================================
//  classes/AdoptionRequest.php
// ============================================================

class AdoptionRequest {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ----------------------------------------------------------
    //  READ
    // ----------------------------------------------------------

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM adoption_requests WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all PENDING requests for pets owned by a user.
     */
    public function getPendingForOwner(int $owner_id): array {
        $stmt = $this->pdo->prepare("
            SELECT ar.*,
                   u.full_name, u.phone, u.email AS req_email, u.facebook,
                   p.name AS pet_name
            FROM adoption_requests ar
            JOIN users u ON u.id = ar.requester_id
            JOIN pets  p ON p.id = ar.pet_id
            WHERE p.user_id = ?
              AND ar.status = 'pending'
            ORDER BY ar.created_at DESC
        ");
        $stmt->execute([$owner_id]);
        return $stmt->fetchAll();
    }

    /**
     * Get all requests sent by a user (their own dashboard).
     * Excludes soft-deleted requests.
     */
    public function getSentByUser(int $user_id): array {
        $stmt = $this->pdo->prepare("
            SELECT ar.*,
                   p.name AS pet_name, p.photo, p.species,
                   u.full_name AS owner_name, u.phone AS owner_phone,
                   u.email AS owner_email, u.facebook AS owner_fb
            FROM adoption_requests ar
            JOIN pets  p ON p.id = ar.pet_id
            JOIN users u ON u.id = p.user_id
            WHERE ar.requester_id = ?
              AND ar.status != 'deleted'
            ORDER BY ar.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Check if user already requested this pet.
     */
    public function hasRequested(int $pet_id, int $user_id): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM adoption_requests
            WHERE pet_id=? AND requester_id=?
        ");
        $stmt->execute([$pet_id, $user_id]);
        return (bool) $stmt->fetch();
    }

    // ----------------------------------------------------------
    //  CREATE
    // ----------------------------------------------------------

    public function create(int $pet_id, int $requester_id, string $message): bool {
        try {
            $this->pdo->prepare("
                INSERT IGNORE INTO adoption_requests (pet_id, requester_id, message)
                VALUES (?, ?, ?)
            ")->execute([$pet_id, $requester_id, $message ?: null]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // ----------------------------------------------------------
    //  UPDATE
    // ----------------------------------------------------------

    /**
     * Approve or reject a request.
     * Only the pet owner can do this.
     * Returns the request data if approved, false on failure.
     */
    public function handle(int $request_id, int $owner_id, string $action): bool {
        if (!in_array($action, ['approved', 'rejected'])) return false;

        // Verify this request belongs to one of owner's pets
        $stmt = $this->pdo->prepare("
            SELECT ar.id FROM adoption_requests ar
            JOIN pets p ON p.id = ar.pet_id
            WHERE ar.id=? AND p.user_id=?
        ");
        $stmt->execute([$request_id, $owner_id]);
        if (!$stmt->fetch()) return false;

        // Update this request status
        $this->pdo->prepare("
            UPDATE adoption_requests SET status=? WHERE id=?
        ")->execute([$action, $request_id]);

        return true;
    }

    /**
     * Soft delete a request (user hides it from their own list).
     * Only the requester can soft-delete their own request.
     */
    public function softDelete(int $request_id, int $user_id): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM adoption_requests
            WHERE id=? AND requester_id=?
        ");
        $stmt->execute([$request_id, $user_id]);
        if (!$stmt->fetch()) return false;

        $this->pdo->prepare("
            UPDATE adoption_requests SET status='deleted' WHERE id=?
        ")->execute([$request_id]);

        return true;
    }
}
