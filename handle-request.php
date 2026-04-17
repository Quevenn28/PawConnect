<?php
// handle-request.php
require_once 'db.php';
require_login();
verify_csrf();

$request_id = (int)($_POST['request_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if (!in_array($action, ['approve','reject'])) {
    header("Location: dashboard.php"); exit;
}

// Verify this request is for one of MY pets
$req = $pdo->prepare("
    SELECT ar.*, p.name as pet_name
    FROM adoption_requests ar
    JOIN pets p ON p.id = ar.pet_id
    WHERE ar.id = ? AND p.user_id = ?
");
$req->execute([$request_id, $_SESSION['user_id']]);
$req = $req->fetch();

if (!$req) { header("Location: dashboard.php"); exit; }

$status = $action === 'approve' ? 'approved' : 'rejected';
$pdo->prepare("UPDATE adoption_requests SET status=? WHERE id=?")->execute([$status, $request_id]);

header("Location: dashboard.php");
exit;
