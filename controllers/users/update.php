<?php
// ============================================================
//  controllers/users/update.php
//  Handles user profile updates.
//  Sets $error and $success for the view.
// ============================================================

require_once __DIR__ . '/../../config/validation.php';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name      = trim($_POST['full_name'] ?? '');
    $phone          = trim($_POST['phone']     ?? '');
    $facebook       = trim($_POST['facebook']  ?? '');
    $address        = trim($_POST['address']   ?? '');
    $sex            = trim($_POST['sex']       ?? $user['sex'] ?? 'Prefer not to say');
    $current_pw     = trim($_POST['current_password'] ?? '');
    $new_password   = trim($_POST['new_password'] ?? '');
    $confirm_pw     = trim($_POST['confirm_password'] ?? '');
    $profile_photo  = null;

    // Validate full name
    $name_validation = validate_full_name($full_name);
    if (!$name_validation['valid']) {
        $error = $name_validation['error'];
    } else {
        $full_name = $name_validation['value'];
    }

    // Validate phone (optional but if provided, must be valid)
    if (!$error && $phone) {
        $phone_validation = validate_phone($phone);
        if (!$phone_validation['valid']) {
            $error = $phone_validation['error'];
        } else {
            $phone = $phone_validation['value'];
        }
    }

    // Validate Facebook (optional but if provided, must be valid)
    if (!$error && $facebook) {
        $facebook_validation = validate_facebook($facebook);
        if (!$facebook_validation['valid']) {
            $error = $facebook_validation['error'];
        } else {
            $facebook = $facebook_validation['value'];
        }
    }

    if (!$error && !$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link.';
    }

    // Validate address
    if (!$error) {
        $address_validation = validate_address($address);
        if (!$address_validation['valid']) {
            $error = $address_validation['error'];
        } else {
            $address = $address_validation['value'];
        }
    }

    // Handle password change
    if (!$error && ($current_pw || $new_password || $confirm_pw)) {
        // If any password field is filled, all must be valid
        if (!$current_pw) {
            $error = 'Please enter your current password to change it.';
        } elseif (!$new_password) {
            $error = 'Please enter a new password.';
        } elseif ($new_password !== $confirm_pw) {
            $error = 'New password and confirmation do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif (!password_verify($current_pw, $user['password'])) {
            $error = 'Current password is incorrect.';
        }
    }

    if (!$error) {
        // Handle photo upload
        if (!empty($_FILES['profile_photo']['tmp_name'])) {
            $file    = $_FILES['profile_photo'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Photo upload failed. Please try again.';
            } elseif (!in_array($ext, $allowed)) {
                $error = 'Invalid format. Use JPG, PNG, or GIF.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'Photo must be under 5MB.';
            } else {
                if (!is_dir('../../uploads/users')) mkdir('../../uploads/users', 0755, true);
                $profile_photo = uniqid('user_') . '.' . $ext;
                move_uploaded_file($file['tmp_name'], '../../uploads/users/' . $profile_photo);
            }
        }

        if (!$error) {
            $userObj->update(
                $_SESSION['user_id'],
                $full_name, $phone, $facebook, $address,
                $sex,
                $profile_photo,
                $new_password ?: null
            );
            $_SESSION['user_name'] = $full_name;
            $success = true;
            $user    = $userObj->findById($_SESSION['user_id']);
            flash('success', 'Profile updated successfully!');
        }
    }
}
