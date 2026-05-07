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
    flash('success', 'Report submitted. Our moderators will review it shortly.');
    header("Location: ../../views/pets/show.php?id=$pet_id");
} else {
    flash('error', 'Could not submit report. You may have already reported this listing.');
    header("Location: ../../views/pets/show.php?id=$pet_id");
}
exit;
