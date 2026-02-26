<?php
require_once 'config/database.php';

try {
    $db = getDB();
    
    // Check if institution_id column exists in teachers table
    $columns = $db->query("SHOW COLUMNS FROM teachers LIKE 'institution_id'")->fetchAll();
    
    if (empty($columns)) {
        // Add institution_id column
        $db->exec("ALTER TABLE teachers ADD COLUMN institution_id INT NOT NULL DEFAULT 1 AFTER id");
        echo "Added institution_id column to teachers table.<br>";
        
        // Add foreign key
        try {
            $db->exec("ALTER TABLE teachers ADD FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE");
            echo "Added foreign key constraint.<br>";
        } catch (Exception $e) {
            echo "Foreign key may already exist or institutions table issue: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "institution_id column already exists in teachers table.<br>";
    }
    
    // Also check other tables
    $tables = ['classes', 'subjects', 'timetables'];
    foreach ($tables as $table) {
        $cols = $db->query("SHOW COLUMNS FROM $table LIKE 'institution_id'")->fetchAll();
        if (empty($cols)) {
            $db->exec("ALTER TABLE $table ADD COLUMN institution_id INT NOT NULL DEFAULT 1");
            echo "Added institution_id to $table.<br>";
        }
    }
    
    echo "<br><strong>Done!</strong> <a href='modules/subadmin/login.php'>Go to Sub-Admin Login</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
