<?php
// controllers/admin/assign_role.php — change a user's role (admin only)
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_admin();
verify_csrf();

$user_id = (int)($_POST['user_id'] ?? 0);
$role    = $_POST['role'] ?? 'user';

$userObj = new User($pdo);
$userObj->assignRole($user_id, $role);

// Update mod_points session title if needed
header("Location: ../../views/admin/dashboard.php?tab=users");
exit;
