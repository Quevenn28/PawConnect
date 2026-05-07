<?php
// ============================================================
//  classes/Pet.php
// ============================================================

class Pet {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ----------------------------------------------------------
    //  READ
    // ----------------------------------------------------------

    public function findById(int $id): array|false {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.full_name, u.phone, u.email,
                   u.facebook, u.address, u.profile_photo
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all available pets with optional search/filter.
     * Only shows status='available' to public.
     */
    public function getAvailable(string $species = '', string $query = '', string $breed = '', string $health_info = '', string $vaccinated = '', string $age_value = '', string $age_unit = '', string $sort = 'recent'): array {
        $where  = ["p.status = 'available'"];
        $params = [];

        if ($species) {
            $where[]  = "p.species = ?";
            $params[] = $species;
        }
        if ($query) {
            $where[]  = "(p.name LIKE ? OR p.breed LIKE ? OR p.description LIKE ? OR p.health_info LIKE ? OR u.full_name LIKE ? OR u.address LIKE ? )";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }
        if ($breed) {
            $where[]  = "p.breed LIKE ?";
            $params[] = "%$breed%";
        }
        if ($health_info) {
            $where[]  = "p.health_info LIKE ?";
            $params[] = "%$health_info%";
        }
        if ($vaccinated) {
            $where[]  = "p.vaccinated = ?";
            $params[] = $vaccinated;
        }
        if ($age_value && $age_unit) {
            $age_unit = strtolower($age_unit);
            $where[]  = "(p.age LIKE ? OR p.age LIKE ?)";
            $params[] = "%$age_value $age_unit%";
            $params[] = "%$age_value {$age_unit}s%";
        }

        $order = $sort === 'alpha' ? 'p.name ASC' : 'p.created_at DESC';

        $sql = "
            SELECT p.*, u.full_name, u.phone, u.email, u.facebook
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY $order
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get pets by a specific user for their dashboard.
     * Excludes removed pets (those are hidden from user).
     */
    public function getByUser(int $user_id): array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pets
            WHERE user_id = ? AND status != 'removed'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    /**
     * Admin/Mod: Get ALL pets including removed ones.
     */
    public function getAllAdmin(string $query = ''): array {
        $where  = ["1=1"];
        $params = [];

        if ($query) {
            $where[]  = "(p.name LIKE ? OR p.breed LIKE ? OR p.description LIKE ? OR u.full_name LIKE ? OR u.address LIKE ?)";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
            $params[] = "%$query%";
        }

        $stmt = $this->pdo->prepare("
            SELECT p.*, u.full_name, u.username
            FROM pets p
            JOIN users u ON u.id = p.user_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY p.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ----------------------------------------------------------
    //  CREATE
    // ----------------------------------------------------------

    public function create(
        int    $user_id,
        string $name,
        string $species,
        string $breed,
        string $age,
        string $gender,
        string $description,
        ?string $health_info,
        string $vaccinated,
        string $spayed_neutered,
        string $good_with_children,
        ?string $photo
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO pets
                (user_id, name, species, breed, age, gender, description, health_info, vaccinated, spayed_neutered, good_with_children, photo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, $name, $species, $breed,
            $age, $gender, $description, $health_info,
            $vaccinated, $spayed_neutered, $good_with_children, $photo
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int    $pet_id,
        string $name,
        string $species,
        string $breed,
        string $age,
        string $gender,
        string $description,
        ?string $health_info,
        string $vaccinated,
        string $spayed_neutered,
        string $good_with_children,
        ?string $photo
    ): bool {
        $stmt = $this->pdo->prepare("\n            UPDATE pets SET
                name = ?, species = ?, breed = ?, age = ?, gender = ?,
                description = ?, health_info = ?, vaccinated = ?, spayed_neutered = ?, good_with_children = ?, photo = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $name, $species, $breed, $age, $gender,
            $description, $health_info, $vaccinated, $spayed_neutered, $good_with_children, $photo,
            $pet_id
        ]);
    }

    // ----------------------------------------------------------
    //  UPDATE
    // ----------------------------------------------------------

    public function markAdopted(int $pet_id): void {
        $this->pdo->prepare("
            UPDATE pets SET status='adopted' WHERE id=?
        ")->execute([$pet_id]);
    }

    /**
     * Soft delete by user — sets status to 'removed', records who removed it.
     */
    public function softDelete(int $pet_id, int $user_id, string $removed_by = 'user'): bool {
        // Verify ownership if removed_by is 'user'
        if ($removed_by === 'user') {
            $stmt = $this->pdo->prepare(
                "SELECT id FROM pets WHERE id=? AND user_id=?"
            );
            $stmt->execute([$pet_id, $user_id]);
            if (!$stmt->fetch()) return false;
        }

        $this->pdo->prepare("
            UPDATE pets
            SET status='removed', removed_by=?, removed_at=NOW()
            WHERE id=?
        ")->execute([$removed_by, $pet_id]);

        return true;
    }

    /**
     * Admin: Restore a soft-deleted pet back to available.
     */
    public function restore(int $pet_id): void {
        $this->pdo->prepare("
            UPDATE pets
            SET status='available', removed_by=NULL, removed_at=NULL
            WHERE id=?
        ")->execute([$pet_id]);
    }

    /**
     * Admin only: Hard delete — permanently removes from DB.
     */
    public function hardDelete(int $pet_id): void {
        $this->pdo->prepare("DELETE FROM pets WHERE id=?")
                  ->execute([$pet_id]);
    }

    // ----------------------------------------------------------
    //  HELPERS
    // ----------------------------------------------------------

    /**
     * Upload a pet photo. Returns filename or null on failure.
     */
    public function uploadPhoto(array $file, string $upload_dir = 'uploads/pets/'): ?string {
        if (empty($file['tmp_name'])) return null;

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed) || $file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid('pet_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $upload_dir . $filename);
        return $filename;
    }

    /**
     * Returns the right emoji for a species.
     */
    public static function emoji(string $species): string {
        return match($species) {
            'Dog'     => '🐕',
            'Cat'     => '🐈',
            'Bird'    => '🦜',
            'Rabbit'  => '🐇',
            default   => '🐾',
        };
    }
}
