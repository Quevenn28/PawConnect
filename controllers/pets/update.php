<?php
require_once '../../autoload.php';
require_once '../../config/database.php';
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

    $name               = trim($_POST['name']               ?? '');
    $species            = trim($_POST['species']            ?? '');
    $breed              = trim($_POST['breed']              ?? '');
    $age_value          = trim($_POST['age_value']          ?? '');
    $age_unit           = trim($_POST['age_unit']           ?? '');
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

    if (!in_array($spayed_neutered, ['Yes', 'No', 'Unknown'])) {
        $spayed_neutered = 'Unknown';
    }
    if (!in_array($good_with_children, ['Yes', 'No', 'Unknown'])) {
        $good_with_children = 'Unknown';
    }

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
