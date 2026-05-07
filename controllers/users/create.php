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

    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';

    } elseif (!$phone && !$facebook) {
        $error = 'Please provide at least a phone number or Facebook link so adopters can contact you.';

    } elseif (!$address) {
        $error = 'Please provide your address or location.';

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
                $password, $phone, $facebook, $address
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
