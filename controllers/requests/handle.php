<?php
// controllers/requests/handle.php — approve or reject adoption request
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$request_id = (int)($_POST['request_id'] ?? 0);
$action     = $_POST['action'] ?? '';

$reqObj = new AdoptionRequest($pdo);
$reqObj->handle($request_id, $_SESSION['user_id'], $action);

header("Location: ../../views/users/index.php");
exit;
