<?php
/**
 * Entry Point - Redirect to login or dashboard
 */

require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/ttc/modules/dashboard/index.php');
} else {
    redirect('/ttc/modules/auth/login.php');
}
