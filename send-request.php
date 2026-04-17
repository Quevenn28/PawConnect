<?php
// send-request.php
require_once 'db.php';
require_login();
verify_csrf();

$pet_id  = (int)($_POST['pet_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

$pet = $pdo->prepare("SELECT * FROM pets WHERE id=? AND status='available'");
$pet->execute([$pet_id]);
$pet = $pet->fetch();

if (!$pet || $pet['user_id'] == $_SESSION['user_id']) {
    header("Location: pets.php"); exit;
}

// Ensure table exists
try { $pdo->query("SELECT 1 FROM adoption_requests LIMIT 1"); }
catch (PDOException $e) {
    $pdo->exec("CREATE TABLE adoption_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pet_id INT NOT NULL,
        requester_id INT NOT NULL,
        message TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at DATETIME DEFAULT NOW(),
        UNIQUE KEY (pet_id, requester_id)
    )");
}

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO adoption_requests (pet_id, requester_id, message) VALUES (?,?,?)");
    $stmt->execute([$pet_id, $_SESSION['user_id'], $message ?: null]);
    header("Location: pet.php?id=$pet_id&requested=1");
} catch (Exception $e) {
    header("Location: pet.php?id=$pet_id&error=" . urlencode("Request failed: " . $e->getMessage()));
}
exit;
