<?php
// controllers/pets/delete.php — soft delete pet
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_login();
verify_csrf();

$petObj = new Pet($pdo);
$petObj->softDelete((int)($_POST['pet_id'] ?? 0), $_SESSION['user_id'], 'user');
flash('success', 'Your listing has been removed from public view.');
header("Location: ../../views/users/index.php?section=pets");
exit;
