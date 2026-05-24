<?php
// ============================================================
//  controllers/users/create.php
//  Handles registration form submission.
//  Sets $error (string) or $success (bool) for the view.
// ============================================================

require_once __DIR__ . '/../../config/validation.php';

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $username  = strtolower(trim($_POST['username'] ?? ''));
    $email     = strtolower(trim($_POST['email']    ?? ''));
    $phone     = trim($_POST['phone']               ?? '');
    $facebook  = trim($_POST['facebook']            ?? '');
    $address   = trim($_POST['address']             ?? '');
    $birthdate = trim($_POST['birthdate']           ?? '');
    $sex       = trim($_POST['sex']                 ?? 'Prefer not to say');
    $password  = $_POST['password']                 ?? '';
    $confirm   = $_POST['confirm']                  ?? '';

    // --- VALIDATION ---
    
    // Validate full name
    $name_validation = validate_full_name($full_name);
    if (!$name_validation['valid']) {
        $error = $name_validation['error'];
    }
    
    if (!$error && (!$username || !$email || !$password)) {
        $error = 'Please fill in all required fields.';
    }

    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }

    if (!$error && !preg_match('/^[a-z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    }

    if (!$error && (strlen($username) < 3 || strlen($username) > 60)) {
        $error = 'Username must be between 3 and 60 characters.';
    }

    if (!$error && $password !== $confirm) {
        $error = 'Passwords do not match.';
    }

    if (!$error && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    }

    // Validate phone
    if (!$error) {
        $phone_validation = validate_phone($phone);
        if (!$phone_validation['valid']) {
            $error = $phone_validation['error'];
        } else {
            $phone = $phone_validation['value'];
        }
    }

    // Validate Facebook
    if (!$error) {
        $facebook_validation = validate_facebook($facebook);
        if (!$facebook_validation['valid']) {
            $error = $facebook_validation['error'];
        } else {
            $facebook = $facebook_validation['value'];
        }
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

    // Validate birthdate
    if (!$error) {
        if (!$birthdate) {
            $error = 'Please provide your birthdate.';
        } else {
            $birthdate_validation = validate_birthdate($birthdate);
            if (!$birthdate_validation['valid']) {
                $error = $birthdate_validation['error'];
            } else {
                $birthdate = $birthdate_validation['value'];
            }
        }
    }

    if (!$error) {
        if (!isset($_POST['agree_terms'])) {
            $error = 'You must agree to the Terms & Conditions to continue.';

        } else {
            $userObj = new User($pdo);

            // Check if email or username belongs to a permanently banned account
            if ($userObj->isBannedCredential($email, $username)) {
                $error = 'This email or username is not available for registration.';

            // Check if email or username already exists
            } elseif ($userObj->emailOrUsernameExists($email, $username)) {
                $error = 'That email or username is already taken. Please choose another.';

            } else {
                // --- CREATE ACCOUNT ---
                $user_id = $userObj->create(
                    $full_name, $username, $email,
                    $password, $phone, $facebook, $address,
                    $birthdate, $sex
                );

                // Log the welcome points
                award_points(
                    $pdo,
                    $user_id,
                    PTS_REGISTER,
                    'Welcome bonus — account created',
                    'general'
                );

                // --- AUTO LOGIN ---
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['username']  = $username;
                $_SESSION['role']      = 'user';

                $success = true;
            }
        }
    }
}
