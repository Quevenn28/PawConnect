<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $petObj = new Pet($pdo);

    $name        = trim($_POST['name']        ?? '');
    $species     = trim($_POST['species']     ?? '');
    $breed       = trim($_POST['breed']       ?? '');
    $age         = trim($_POST['age']         ?? '');
    $gender      = trim($_POST['gender']      ?? 'Unknown');
    $description = trim($_POST['description'] ?? '');

    if (!$name || !$species) {
        $error = 'Pet name and species are required.';
    } else {
        $photo = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = $petObj->uploadPhoto($_FILES['photo'], '../../uploads/pets/');
            if (!$photo) $error = 'Invalid photo. Use JPG/PNG/GIF/WebP under 5MB.';
        }

        if (!$error) {
            $pet_id = $petObj->create(
                $_SESSION['user_id'], $name, $species,
                $breed, $age, $gender, $description, $photo
            );
            award_points($pdo, $_SESSION['user_id'], PTS_POST_PET, 'Posted pet for adoption: '.$name, 'general');
            header("Location: ../users/index.php?added=1");
            exit;
        }
    }
}