<?php
// controllers/pets/delete.php — soft delete pet
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$petObj = new Pet($pdo);
$petObj->softDelete((int)($_POST['pet_id'] ?? 0), $_SESSION['user_id'], 'user');
header("Location: ../../views/users/index.php");
exit;
