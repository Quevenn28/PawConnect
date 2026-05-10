<?php
// ============================================================
//  controllers/users/create.php
//  Handles registration form submission.
//  Sets $error (string) or $success (bool) for the view.
// ============================================================

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
    $password  = $_POST['password']                 ?? '';
    $confirm   = $_POST['confirm']                  ?? '';

    // --- VALIDATION ---
    if (!$full_name || !$username || !$email || !$password) {
        $error = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';

    } elseif (strlen($username) < 3 || strlen($username) > 60) {
        $error = 'Username must be between 3 and 60 characters.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';

    } elseif (!$phone) {
        $error = 'Please provide your phone number so adopters can contact you.';

    } elseif (!$facebook) {
        $error = 'Please provide your Facebook profile link so adopters can contact you.';

    } elseif (!$address) {
        $error = 'Please provide your address or location.';

    } elseif (!$birthdate) {
        $error = 'Please provide your birthdate.';

    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
        $error = 'Please enter a valid birthdate in YYYY-MM-DD format.';

    } else {
        try {
            $dob = new DateTime($birthdate);
            $today = new DateTime('today');
        } catch (Exception $e) {
            $dob = null;
        }

        if (!$dob) {
            $error = 'Please enter a valid birthdate.';

        } elseif ($dob > new DateTime('today')) {
            $error = 'Birthdate cannot be in the future.';

        } elseif ($dob->diff($today)->y < 18) {
            $error = 'You must be 18 years or older to register.';

        } elseif (!isset($_POST['agree_terms'])) {
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
                    $birthdate
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
