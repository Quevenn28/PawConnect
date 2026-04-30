<?php
// controllers/reports/create.php — submit a report
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$pet_id  = (int)($_POST['pet_id'] ?? 0);
$reason  = trim($_POST['reason']  ?? '');
$details = trim($_POST['details'] ?? '');

$reportObj = new Report($pdo);
$ok        = $reportObj->create($pet_id, $_SESSION['user_id'], $reason, $details);

if ($ok) {
    header("Location: ../../views/pets/show.php?id=$pet_id&reported=1");
} else {
    header("Location: ../../views/pets/show.php?id=$pet_id&error=".urlencode("Could not submit report. You may have already reported this listing."));
}
exit;
