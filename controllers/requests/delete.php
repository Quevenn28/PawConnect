<?php
// controllers/requests/delete.php — soft delete adoption request
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$reqObj = new AdoptionRequest($pdo);
$reqObj->softDelete((int)($_POST['request_id'] ?? 0), $_SESSION['user_id']);

header("Location: ../../views/users/index.php");
exit;
