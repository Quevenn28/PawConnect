<?php
// controllers/pets/mark_adopted.php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$pet_id = (int)($_POST['pet_id'] ?? 0);
$petObj = new Pet($pdo);
$pet    = $petObj->findById($pet_id);

if ($pet && $pet['user_id'] == $_SESSION['user_id'] && $pet['status'] === 'available') {
    $me = $pdo->prepare("SELECT * FROM users WHERE id=?")->execute([$_SESSION['user_id']]);
    $me = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $me->execute([$_SESSION['user_id']]);
    $me = $me->fetch();

    // Find approved adopter if any
    $adopter_stmt = $pdo->prepare("
        SELECT ar.*, u.full_name, u.email, u.phone, u.id as uid
        FROM adoption_requests ar
        JOIN users u ON u.id = ar.requester_id
        WHERE ar.pet_id=? AND ar.status='approved' LIMIT 1
    ");
    $adopter_stmt->execute([$pet_id]);
    $adopter = $adopter_stmt->fetch();

    // Log adoption
    $pdo->prepare("
        INSERT INTO adoptions
            (pet_id, pet_name, adopter_id, adopter_name, adopter_email, adopter_phone,
             owner_id, owner_name, owner_email, owner_phone)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $pet['id'], $pet['name'],
        $adopter ? $adopter['uid']        : null,
        $adopter ? $adopter['full_name']  : $me['full_name'],
        $adopter ? $adopter['email']      : $me['email'],
        $adopter ? $adopter['phone']      : $me['phone'],
        $me['id'], $me['full_name'], $me['email'], $me['phone']
    ]);

    $petObj->markAdopted($pet_id);

    // Award points to owner
    award_points($pdo, $_SESSION['user_id'], PTS_PET_ADOPTED, 'Pet adopted: '.$pet['name'], 'general');

    // Award points to adopter if known
    if ($adopter) {
        award_points($pdo, $adopter['uid'], PTS_ADOPT_PET, 'Adopted a pet: '.$pet['name'], 'general');
    }

    flash('success', 'Great news! Your pet has been marked as adopted.');
}

header("Location: ../../views/users/index.php");
exit;
