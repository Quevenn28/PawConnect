<?php
// controllers/requests/create.php — send adoption request
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$pet_id  = (int)($_POST['pet_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

$petObj = new Pet($pdo);
$pet    = $petObj->findById($pet_id);

if (!$pet || $pet['status'] !== 'available' || $pet['user_id'] == $_SESSION['user_id']) {
    header("Location: ../../views/pets/index.php"); exit;
}

$reqObj = new AdoptionRequest($pdo);
$ok     = $reqObj->create($pet_id, $_SESSION['user_id'], $message);

if ($ok) {
    header("Location: ../../views/pets/show.php?id=$pet_id&requested=1");
} else {
    header("Location: ../../views/pets/show.php?id=$pet_id&error=".urlencode("Could not send request."));
}
exit;
