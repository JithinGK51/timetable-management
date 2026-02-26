<?php
require_once 'config/database.php';

try {
    $db = getDB();
    $admins = $db->query('SELECT username, plain_password, name, is_super_admin, status FROM admins')->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h2>All Users in Database</h2>';
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; font-family: Arial;">';
    echo '<tr style="background: #667eea; color: white;">
            <th>Username</th>
            <th>Password</th>
            <th>Name</th>
            <th>Type</th>
            <th>Status</th>
          </tr>';
    
    foreach ($admins as $a) {
        $type = $a['is_super_admin'] ? '<b>Super Admin</b>' : 'Sub-Admin';
        $password = $a['plain_password'] ?? 'admin123';
        $statusColor = $a['status'] === 'active' ? 'green' : 'red';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($a['username']) . '</td>';
        echo '<td>' . htmlspecialchars($password) . '</td>';
        echo '<td>' . htmlspecialchars($a['name']) . '</td>';
        echo '<td>' . $type . '</td>';
        echo '<td style="color: ' . $statusColor . ';">' . ucfirst($a['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '<br><a href="/ttc/modules/subadmin/login.php">Go to Sub-Admin Login</a>';
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
