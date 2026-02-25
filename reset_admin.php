<?php
/**
 * Reset Admin Password
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>Reset Admin Password</h1>";

// Generate new hash for 'admin123'
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<p>New password hash: <code>$hash</code></p>";

// Update admin password
try {
    $stmt = getDB()->prepare("UPDATE admins SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>Admin password reset successfully!</p>";
    } else {
        echo "<p style='color: orange;'>No rows updated. Checking if admin exists...</p>";
        
        // Check if admin exists
        $admin = dbFetch("SELECT id FROM admins WHERE username = 'admin'");
        if (!$admin) {
            echo "<p style='color: red;'>Admin user not found! Creating...</p>";
            
            // Create admin
            dbQuery("INSERT INTO admins (username, password, name, email, role_id, is_super_admin, status) 
                    VALUES ('admin', ?, 'System Administrator', 'admin@ttc.com', 1, 1, 'active')", 
                    [$hash]);
            echo "<p style='color: green;'>Admin created!</p>";
        }
    }
    
    // Verify
    $verify = dbFetch("SELECT password FROM admins WHERE username = 'admin'");
    if ($verify && password_verify($password, $verify['password'])) {
        echo "<p style='color: green;'>Verification: SUCCESS - Password 'admin123' works!</p>";
    } else {
        echo "<p style='color: red;'>Verification: FAILED</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='modules/auth/login.php'>Go to Login</a></p>";
