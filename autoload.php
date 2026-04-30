<?php
// ============================================================
//  autoload.php
//  Automatically loads class files from /classes/
//  Include this at the top of any file that needs classes
// ============================================================

spl_autoload_register(function (string $class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
