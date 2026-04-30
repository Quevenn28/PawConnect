<?php
// ============================================================
//  register.php  — entry point, loads controller then view
// ============================================================
require_once 'autoload.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

// Run the create controller which sets $error / $success
require_once 'controllers/users/create.php';

// Load the register view
require_once 'views/users/create.php';
