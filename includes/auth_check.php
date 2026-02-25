<?php
/**
 * Authentication Check
 * Include this file at the top of all protected pages
 */

require_once __DIR__ . '/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Store intended URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header('Location: /ttc/modules/auth/login.php');
    exit;
}

// Get current admin info for use in pages
$currentAdmin = getCurrentAdmin();

if (!$currentAdmin) {
    // Invalid session, logout
    logout();
}

// Check if account is still active
if ($currentAdmin['status'] !== 'active') {
    session_destroy();
    header('Location: /ttc/modules/auth/login.php?error=account_inactive');
    exit;
}
