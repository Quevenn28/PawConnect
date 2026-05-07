<?php
// controllers/requests/handle.php — approve or reject adoption request
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();
verify_csrf();

$request_id = (int)($_POST['request_id'] ?? 0);
$action     = $_POST['action'] ?? '';

$reqObj = new AdoptionRequest($pdo);
$result = $reqObj->handle($request_id, $_SESSION['user_id'], $action);

// If approved, automatically mark pet as adopted and log everything
if ($result && $action === 'approved') {

    // Get full request + pet + adopter info
    $stmt = $pdo->prepare("
        SELECT ar.*,
               u.full_name AS adopter_name, u.email AS adopter_email, u.phone AS adopter_phone,
               p.name AS pet_name, p.id AS pet_id,
               o.full_name AS owner_name, o.email AS owner_email, o.phone AS owner_phone,
               o.id AS owner_id
        FROM adoption_requests ar
        JOIN users u ON u.id = ar.requester_id
        JOIN pets  p ON p.id = ar.pet_id
        JOIN users o ON o.id = p.user_id
        WHERE ar.id = ?
    ");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();

    if ($req) {
        $pet_id     = $req['pet_id'];
        $adopter_id = $req['requester_id'];

        // 1. Mark pet as adopted
        $pdo->prepare("UPDATE pets SET status='adopted' WHERE id=?")
            ->execute([$pet_id]);

        // 2. Reject all other pending requests for this pet
        $pdo->prepare("
            UPDATE adoption_requests
            SET status='rejected'
            WHERE pet_id=? AND id!=? AND status='pending'
        ")->execute([$pet_id, $request_id]);

        // 3. Log adoption in adoptions table
        $pdo->prepare("
            INSERT INTO adoptions
                (pet_id, pet_name, adopter_id, adopter_name, adopter_email, adopter_phone,
                 owner_id, owner_name, owner_email, owner_phone)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $pet_id,          $req['pet_name'],
            $adopter_id,      $req['adopter_name'],
            $req['adopter_email'], $req['adopter_phone'],
            $_SESSION['user_id'], $req['owner_name'],
            $req['owner_email'],  $req['owner_phone']
        ]);

        // 4. Award points to owner (rehomer)
        award_points($pdo, $_SESSION['user_id'], PTS_PET_ADOPTED, 'Pet adopted: '.$req['pet_name'], 'general');

        // 5. Award points to adopter
        award_points($pdo, $adopter_id, PTS_ADOPT_PET, 'Adopted a pet: '.$req['pet_name'], 'general');

        $notif = new Notification($pdo);
        $notif->create(
            $adopter_id,
            'Your request to adopt "' . $req['pet_name'] . '" has been approved.',
            '/views/pets/show.php?id=' . encode_id($pet_id)
        );

        $other_rejects = $pdo->prepare("
            SELECT requester_id FROM adoption_requests
            WHERE pet_id=? AND id!=? AND status='rejected'
        ");
        $other_rejects->execute([$pet_id, $request_id]);
        while ($other = $other_rejects->fetch()) {
            $notif->create(
                $other['requester_id'],
                'Your request to adopt "' . $req['pet_name'] . '" was not selected.',
                '/views/pets/show.php?id=' . encode_id($pet_id)
            );
        }
    }
} elseif ($result && $action === 'rejected') {
    $stmt = $pdo->prepare("
        SELECT ar.*, p.name AS pet_name, p.id AS pet_id
        FROM adoption_requests ar
        JOIN pets p ON p.id = ar.pet_id
        WHERE ar.id = ?
    ");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch();

    if ($req) {
        $notif = new Notification($pdo);
        $notif->create(
            $req['requester_id'],
            'Your adoption request for "' . $req['pet_name'] . '" was rejected.',
            '/views/pets/show.php?id=' . encode_id($req['pet_id'])
        );
    }
}

header("Location: ../../views/users/index.php");
exit;
