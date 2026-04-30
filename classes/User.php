<?php
// ============================================================
//  classes/User.php
// ============================================================

class User {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ----------------------------------------------------------
    //  READ
    // ----------------------------------------------------------

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByEmailOrUsername(string $login): array|false {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1"
        );
        $stmt->execute([$login, $login]);
        return $stmt->fetch();
    }

    public function emailOrUsernameExists(string $email, string $username): bool {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM users WHERE email = ? OR username = ?"
        );
        $stmt->execute([$email, $username]);
        return (bool) $stmt->fetch();
    }

    /**
     * Search users by name, username, or email.
     * Admin-only feature.
     */
    public function search(string $query): array {
        $q = "%$query%";
        $stmt = $this->pdo->prepare("
            SELECT id, full_name, username, email, role, points,
                   mod_points, is_banned, ban_until, created_at
            FROM users
            WHERE full_name LIKE ? OR username LIKE ? OR email LIKE ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$q, $q, $q]);
        return $stmt->fetchAll();
    }

    public function getAll(): array {
        return $this->pdo->query("
            SELECT id, full_name, username, email, role, points,
                   mod_points, is_banned, ban_until, created_at
            FROM users ORDER BY created_at DESC
        ")->fetchAll();
    }

    // ----------------------------------------------------------
    //  STATS (for titles)
    // ----------------------------------------------------------

    public function getPetsPostedCount(int $user_id): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM pets
            WHERE user_id = ? AND status != 'removed'
        ");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getPetsAdoptedCount(int $user_id): int {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM adoptions WHERE adopter_id = ?
        ");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    // ----------------------------------------------------------
    //  CREATE
    // ----------------------------------------------------------

    public function create(
        string $full_name,
        string $username,
        string $email,
        string $password,
        string $phone,
        string $facebook,
        string $address
    ): int {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users
                (full_name, username, email, password, phone, facebook, address, role, points)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'user', ?)
        ");
        $stmt->execute([
            $full_name, $username, $email, $hashed,
            $phone, $facebook, $address, 0
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ----------------------------------------------------------
    //  UPDATE
    // ----------------------------------------------------------

    public function update(
        int    $user_id,
        string $full_name,
        string $phone,
        string $facebook,
        string $address,
        ?string $profile_photo = null
    ): void {
        if ($profile_photo !== null) {
            $this->pdo->prepare("
                UPDATE users
                SET full_name=?, phone=?, facebook=?, address=?, profile_photo=?
                WHERE id=?
            ")->execute([$full_name, $phone, $facebook, $address, $profile_photo, $user_id]);
        } else {
            $this->pdo->prepare("
                UPDATE users
                SET full_name=?, phone=?, facebook=?, address=?
                WHERE id=?
            ")->execute([$full_name, $phone, $facebook, $address, $user_id]);
        }
    }

    // ----------------------------------------------------------
    //  BAN / UNBAN
    // ----------------------------------------------------------

    /**
     * Ban a user.
     * @param int         $user_id
     * @param string      $reason
     * @param string|null $until   DateTime string for temp ban, NULL for permanent
     */
    public function ban(int $user_id, string $reason, ?string $until = null): void {
        $this->pdo->prepare("
            UPDATE users
            SET is_banned=1, ban_reason=?, ban_until=?
            WHERE id=?
        ")->execute([$reason, $until, $user_id]);
    }

    public function unban(int $user_id): void {
        $this->pdo->prepare("
            UPDATE users
            SET is_banned=0, ban_reason=NULL, ban_until=NULL
            WHERE id=?
        ")->execute([$user_id]);
    }

    // ----------------------------------------------------------
    //  ROLE MANAGEMENT
    // ----------------------------------------------------------

    /**
     * Assign a role to a user. Only admin can call this.
     * Cannot change another admin's role.
     */
    public function assignRole(int $user_id, string $role): bool {
        if (!in_array($role, ['user', 'moderator', 'admin'])) return false;

        // Safety: don't allow changing an admin's role via this method
        $target = $this->findById($user_id);
        if (!$target || $target['role'] === 'admin') return false;

        $this->pdo->prepare("UPDATE users SET role=? WHERE id=?")
                  ->execute([$role, $user_id]);
        return true;
    }

    // ----------------------------------------------------------
    //  BAN CHECK — also checks email/username collision for banned
    // ----------------------------------------------------------

    /**
     * Check if an email or username belongs to a banned account.
     * Prevents re-registration with same credentials.
     */
    public function isBannedCredential(string $email, string $username): bool {
        $stmt = $this->pdo->prepare("
            SELECT id FROM users
            WHERE (email=? OR username=?) AND is_banned=1 AND ban_until IS NULL
        ");
        $stmt->execute([$email, $username]);
        return (bool) $stmt->fetch();
    }
}
