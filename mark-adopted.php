<?php
// mark-adopted.php
require_once 'db.php';
require_login();
verify_csrf();

$pet_id = (int)($_POST['pet_id'] ?? 0);

$pet = $pdo->prepare("SELECT * FROM pets WHERE id=? AND user_id=? AND status='available'");
$pet->execute([$pet_id, $_SESSION['user_id']]);
$pet = $pet->fetch();

if ($pet) {
    // Get user info
    $me = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $me->execute([$_SESSION['user_id']]);
    $me = $me->fetch();

    // Find approved adopter if any
    try {
        $adopter_row = $pdo->prepare("
            SELECT ar.*, u.full_name, u.email, u.phone
            FROM adoption_requests ar
            JOIN users u ON u.id = ar.requester_id
            WHERE ar.pet_id = ? AND ar.status = 'approved'
            LIMIT 1
        ");
        $adopter_row->execute([$pet_id]);
        $adopter = $adopter_row->fetch();
    } catch (Exception $e) {
        $adopter = null;
    }

    // Save to adoptions log
    $ins = $pdo->prepare("INSERT INTO adoptions (pet_id, pet_name, adopter_name, adopter_email, adopter_phone, owner_name, owner_email, owner_phone)
                          VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([
        $pet['id'],
        $pet['name'],
        $adopter ? $adopter['full_name'] : $me['full_name'],
        $adopter ? $adopter['email']     : $me['email'],
        $adopter ? $adopter['phone']     : $me['phone'],
        $me['full_name'],
        $me['email'],
        $me['phone'],
    ]);

    // Mark pet as adopted
    $pdo->prepare("UPDATE pets SET status='adopted' WHERE id=?")->execute([$pet_id]);
}

header("Location: dashboard.php");
exit;
