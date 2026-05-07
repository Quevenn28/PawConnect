<?php
// ============================================================
//  classes/Notification.php
// ============================================================

class Notification {

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message VARCHAR(255) NOT NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT DEFAULT 0,
                created_at DATETIME DEFAULT NOW(),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )"
        );
    }

    public function create(int $user_id, string $message, ?string $link = null): bool {
        $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $message, $link]);
    }

    public function getForUser(int $user_id, int $limit = 10): array {
        $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT ?");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnread(int $user_id): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $notification_id, int $user_id): bool {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    }

    public function markAllRead(int $user_id): bool {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$user_id]);
    }
}
