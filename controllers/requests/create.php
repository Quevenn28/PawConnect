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
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $requester = $stmt->fetch();

    $notif = new Notification($pdo);
    $notif->create(
        $pet['user_id'],
        'New adoption request from ' . $requester['full_name'] . ' for "' . $pet['name'] . '".',
        '/views/pets/show.php?id=' . encode_id($pet['id'])
    );

    flash('success', 'Your adoption request has been sent! The owner can contact you soon.');
    header("Location: ../../views/pets/show.php?id=" . encode_id($pet_id));
} else {
    flash('error', 'Could not send request. You may have already requested this pet.');
    header("Location: ../../views/pets/show.php?id=" . encode_id($pet_id));
}
exit;
