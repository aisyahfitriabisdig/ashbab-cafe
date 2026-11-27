<?php
require_once __DIR__ . '/includes/auth.php';
try {
    if (login('admin', 'admin')) {
        echo "LOGIN SUCCESS!\n";
        print_r($_SESSION);
    } else {
        echo "LOGIN FAILED!\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
