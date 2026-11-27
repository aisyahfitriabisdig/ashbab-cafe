<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

try {
    $db = db();
    echo "DB connected.\n";
    $users = $db->fetchAll("SELECT id, username, password, role FROM users");
    print_r($users);
    
    echo "Login admin/password: " . (login('admin', 'password') ? "Success" : "Failed") . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
