<?php
// controllers/requests/delete.php — soft delete adoption request
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_login();
verify_csrf();

$reqObj = new AdoptionRequest($pdo);
$reqObj->softDelete((int)($_POST['request_id'] ?? 0), $_SESSION['user_id']);
flash('success', 'Your adoption request has been removed.');

header("Location: ../../views/users/index.php");
exit;
