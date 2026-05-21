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
    $age_value   = trim($_POST['age_value']   ?? '');
    $breed              = trim($_POST['breed']              ?? '');
    $age_value          = trim($_POST['age_value']          ?? '');
    $age_unit           = trim($_POST['age_unit']           ?? '');
    $age                = trim($_POST['age']                ?? '');
    $gender             = trim($_POST['gender']             ?? '');
    $description        = trim($_POST['description']        ?? '');
    $health_info        = trim($_POST['health_info']        ?? '');
    $vaccinated         = isset($_POST['vaccinated']) ? 'Yes' : 'No';
    $spayed_neutered    = isset($_POST['spayed_neutered']) ? 'Yes' : 'No';
    $good_with_children = trim($_POST['good_with_children'] ?? 'Unknown');

    if (!$age && $age_value && $age_unit) {
        $unit = strtolower($age_unit);
        $age  = $age_value . ' ' . ($age_value === '1' ? $unit : $unit . 's');
    }

    if (!$age && $age_value && $age_unit) {
        $unit = strtolower($age_unit);
        $age  = $age_value . ' ' . ($age_value === '1' ? $unit : $unit . 's');
    }

    if (!in_array($spayed_neutered, ['Yes', 'No', 'Unknown'])) {
        $spayed_neutered = 'Unknown';
    }
    if (!in_array($good_with_children, ['Yes', 'No', 'Unknown'])) {
        $good_with_children = 'Unknown';
    }

    // --- VALIDATION ---
    if (!$name || !$species) {
        $error = 'Pet name and species are required.';

    } elseif (!$breed) {
        $error = 'Please enter the breed of your pet.';

    } elseif (!$age) {
        $error = 'Please enter the age of your pet.';

    } elseif (!$gender) {
        $error = 'Please select the sex of your pet.';

    } elseif (!$description) {
        $error = 'Please provide a description for your pet.';

    } elseif (!$spayed_neutered) {
        $error = 'Please choose whether your pet is spayed/neutered.';

    } elseif (!$good_with_children) {
        $error = 'Please choose whether your pet is good with children.';

    } else {
        $photo = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = $petObj->uploadPhoto($_FILES['photo'], '../../uploads/pets/');
            if (!$photo) $error = 'Invalid photo. Use JPG/PNG/GIF/WebP under 5MB.';
        } else {
            $error = 'A pet photo is required.';
        }

        if (!$error) {
            $pet_id = $petObj->create(
                $_SESSION['user_id'], $name, $species,
                $breed, $age, $gender, $description,
                $health_info, $vaccinated, $spayed_neutered, $good_with_children,
                $photo
            );
            award_points($pdo, $_SESSION['user_id'], PTS_POST_PET, 'Posted pet for adoption: '.$name, 'general');
            flash('success', 'Your pet listing is live!');
            header("Location: ../pets/index.php");
            exit;
        }
    }
}
