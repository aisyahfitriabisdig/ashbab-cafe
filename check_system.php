<?php
require_once __DIR__ . '/includes/config.php';

echo "<h2>System Diagnostic & Auto-Fix</h2>";

// 1. Check MySQL Connection
$dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
    echo "✅ <b>MySQL Service:</b> Running and accessible.<br>";
} catch (PDOException $e) {
    die("❌ <b>MySQL Service:</b> ERROR! MySQL is NOT running or credentials are wrong. Error: " . $e->getMessage() . "<br><br><b>FIX:</b> Open XAMPP and click START on MySQL.");
}

// 2. Check Database Existence
try {
    $pdo->exec("USE " . DB_NAME);
    echo "✅ <b>Database:</b> '" . DB_NAME . "' exists.<br>";
} catch (PDOException $e) {
    echo "⚠️ <b>Database:</b> '" . DB_NAME . "' does not exist. Creating it now...<br>";
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        echo "✅ <b>Database:</b> Created successfully.<br>";
    } catch (PDOException $ex) {
        die("❌ <b>Database:</b> Failed to create. Error: " . $ex->getMessage());
    }
}

// 3. Check Tables and Import SQL if empty
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('users', $tables) || !in_array('settings', $tables)) {
    echo "⚠️ <b>Tables:</b> Missing tables. Importing database schema from ashbab_cafe.sql...<br>";
    $sqlFile = __DIR__ . '/database/ashbab_cafe.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        try {
            $pdo->exec($sql);
            echo "✅ <b>Tables:</b> Imported successfully.<br>";
        } catch (PDOException $ex) {
            echo "❌ <b>Tables:</b> Failed to import SQL. Error: " . $ex->getMessage() . "<br>";
        }
    } else {
        echo "❌ <b>Tables:</b> SQL file not found at $sqlFile<br>";
    }
} else {
    echo "✅ <b>Tables:</b> Required tables exist.<br>";
}

// 4. Force Reset Admin Password
try {
    $newHash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$newHash]);
    if ($stmt->rowCount() > 0) {
        echo "✅ <b>Admin Account:</b> Password forcefully reset to: <b>password</b><br>";
    } else {
        // Try inserting admin if not exists
        $check = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
        if (!$check) {
            $pdo->exec("INSERT INTO users (name, username, password, role) VALUES ('Super Admin', 'admin', '$newHash', 'admin')");
            echo "✅ <b>Admin Account:</b> Admin account was missing. Created new admin with password: <b>password</b><br>";
        } else {
            echo "✅ <b>Admin Account:</b> Password is already up to date.<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ <b>Admin Account:</b> Failed to reset. Error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>All checks completed!</h3>";
echo "<a href='login.php' style='display:inline-block;padding:10px 20px;background:#8B4513;color:#fff;text-decoration:none;border-radius:5px;'>Click Here to Go to Login Page</a>";
echo "<br><br><small><i>Login with Username: <b>admin</b> | Password: <b>password</b></i></small>";
?>
