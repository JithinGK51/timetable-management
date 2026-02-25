<?php
require_once 'config/database.php';

try {
    $db = getDB();
    
    // Add plain_password column to admins
    try {
        $db->exec('ALTER TABLE admins ADD COLUMN plain_password VARCHAR(255) DEFAULT NULL');
        echo 'Column plain_password added to admins!<br>';
        $db->exec("UPDATE admins SET plain_password = 'admin123' WHERE plain_password IS NULL");
        echo 'Existing admins updated with default password.<br>';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo 'plain_password column already exists.<br>';
        } else {
            echo 'Error adding plain_password: ' . $e->getMessage() . '<br>';
        }
    }
    
    // Add event columns to timetable_entries
    try {
        $db->exec('ALTER TABLE timetable_entries ADD COLUMN is_event TINYINT(1) DEFAULT 0');
        echo 'Column is_event added to timetable_entries!<br>';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo 'is_event column already exists.<br>';
        } else {
            echo 'Error adding is_event: ' . $e->getMessage() . '<br>';
        }
    }
    
    try {
        $db->exec('ALTER TABLE timetable_entries ADD COLUMN event_name VARCHAR(255) DEFAULT NULL');
        echo 'Column event_name added to timetable_entries!<br>';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo 'event_name column already exists.<br>';
        } else {
            echo 'Error adding event_name: ' . $e->getMessage() . '<br>';
        }
    }
    
    try {
        $db->exec('ALTER TABLE timetable_entries ADD COLUMN event_type ENUM("event","holiday","exam","other") DEFAULT NULL');
        echo 'Column event_type added to timetable_entries!<br>';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo 'event_type column already exists.<br>';
        } else {
            echo 'Error adding event_type: ' . $e->getMessage() . '<br>';
        }
    }
    
    echo '<br><strong>All done!</strong> <a href="modules/subadmin/index.php">Go to Sub-Admins</a> | <a href="modules/timetable/index.php">Go to Timetables</a>';
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
