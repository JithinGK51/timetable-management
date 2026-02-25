<?php
/**
 * Database Configuration
 * Teacher Timetable Management System
 */

// Database configuration array
$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 3307,          // XAMPP MySQL port
    'dbname'   => 'ttc_system',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4'
];

// Define constants for backward compatibility
define('DB_HOST', $dbConfig['host']);
define('DB_PORT', $dbConfig['port']);
define('DB_NAME', $dbConfig['dbname']);
define('DB_USER', $dbConfig['username']);
define('DB_PASS', $dbConfig['password']);
define('DB_CHARSET', $dbConfig['charset']);

// PDO Options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Database connection function
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $pdo;
}

// Helper function to execute queries
function dbQuery($sql, $params = []) {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Helper function to fetch single row
function dbFetch($sql, $params = []) {
    return dbQuery($sql, $params)->fetch();
}

// Helper function to fetch all rows
function dbFetchAll($sql, $params = []) {
    return dbQuery($sql, $params)->fetchAll();
}

// Helper function to insert and get last ID
function dbInsert($sql, $params = []) {
    dbQuery($sql, $params);
    return getDB()->lastInsertId();
}

// Helper function to get row count
function dbCount($sql, $params = []) {
    return dbQuery($sql, $params)->rowCount();
}
