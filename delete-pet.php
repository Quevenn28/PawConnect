<?php
// delete-pet.php
require_once 'db.php';
require_login();
verify_csrf();

$pet_id = (int)($_POST['pet_id'] ?? 0);
$pdo->prepare("UPDATE pets SET status='removed' WHERE id=? AND user_id=?")->execute([$pet_id, $_SESSION['user_id']]);
header("Location: dashboard.php");
exit;
