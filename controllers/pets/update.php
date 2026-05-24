<?php
require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/validation.php';
require_login();

$error = '';
$petObj = new Pet($pdo);

$pet_id = decode_id($_GET['id'] ?? '');
$pet = $pet_id ? $petObj->findById($pet_id) : false;

if (!$pet || $pet['user_id'] !== $_SESSION['user_id'] || $pet['status'] === 'removed') {
    flash('error', 'Pet not found or you do not have permission to edit this listing.');
    header('Location: ../users/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name        = trim($_POST['name']        ?? '');
    $species     = trim($_POST['category']    ?? '');
    $breed       = trim($_POST['breed']       ?? '');
    $age_value   = trim($_POST['age_value']   ?? '');
    $age_unit    = trim($_POST['age_unit']    ?? '');
    $age         = trim($_POST['age']         ?? '');
    $gender      = trim($_POST['gender']      ?? '');
    $description = trim($_POST['description'] ?? '');
    $health_info = trim($_POST['health_info'] ?? '');
    $vaccinated  = isset($_POST['vaccinated']) ? 'Yes' : 'No';
    $spayed_neutered    = isset($_POST['spayed_neutered']) ? 'Yes' : 'No';
    $good_with_children = trim($_POST['good_with_children'] ?? 'Unknown');

    // --- VALIDATION ---
    
    // Validate pet name
    $pet_name_validation = validate_pet_name($name);
    if (!$pet_name_validation['valid']) {
        $error = $pet_name_validation['error'];
    } else {
        $name = $pet_name_validation['value'];
    }

    if (!$error && !$species) {
        $error = 'Pet category is required.';
    }

    // Validate breed
    if (!$error) {
        $breed_validation = validate_breed($breed);
        if (!$breed_validation['valid']) {
            $error = $breed_validation['error'];
        } else {
            $breed = $breed_validation['value'];
        }
    }

    // Validate age
    if (!$error && (!$age_value || !$age_unit)) {
        $error = 'Please enter the age of your pet.';
    }

    if (!$error) {
        $age_validation = validate_pet_age($age_value, $age_unit);
        if (!$age_validation['valid']) {
            $error = $age_validation['error'];
        } else {
            $unit = strtolower($age_unit);
            $age  = $age_value . ' ' . ($age_value === '1' ? $unit : $unit . 's');
        }
    }

    if (!$error && !$gender) {
        $error = 'Please select the sex of your pet.';
    }

    // Validate description
    if (!$error) {
        $desc_validation = validate_pet_description($description);
        if (!$desc_validation['valid']) {
            $error = $desc_validation['error'];
        } else {
            $description = $desc_validation['value'];
        }
    }

    // Validate health info (optional)
    if (!$error && $health_info) {
        $health_validation = validate_pet_health_info($health_info);
        if (!$health_validation['valid']) {
            $error = $health_validation['error'];
        } else {
            $health_info = $health_validation['value'];
        }
    }

    if (!$error) {
        if (!in_array($spayed_neutered, ['Yes', 'No', 'Unknown'])) {
            $spayed_neutered = 'Unknown';
        }
        if (!in_array($good_with_children, ['Yes', 'No', 'Unknown'])) {
            $good_with_children = 'Unknown';
        }
    }

    if (!$error) {
        $photo = $pet['photo'];
        if (!empty($_FILES['photo']['tmp_name'])) {
            $uploaded = $petObj->uploadPhoto($_FILES['photo'], '../../uploads/pets/');
            if (!$uploaded) {
                $error = 'Invalid photo. Use JPG/PNG/GIF/WebP under 5MB.';
            } else {
                $photo = $uploaded;
            }
        }

        if (!$error) {
            $petObj->update(
                $pet['id'], $name, $species, $breed, $age, $gender,
                $description, $health_info, $vaccinated, $spayed_neutered, $good_with_children,
                $photo
            );
            flash('success', 'Pet listing updated successfully.');
            header('Location: ../users/index.php');
            exit;
        }
    }
}
