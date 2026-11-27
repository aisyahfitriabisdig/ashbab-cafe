<?php
require_once __DIR__ . '/includes/auth.php';
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Super Admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

try {
    $stats = getDashboardStats();
    print_r($stats);
    echo "Dashboard stats fetched successfully!\n";
} catch (Exception $e) {
    echo "DASHBOARD CRASH: " . $e->getMessage();
}
