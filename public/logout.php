<?php
/**
 * Care4Mom - Logout
 * User session termination
 * Author: Care4Mom Development Team
 * Version: 1.0
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/errorlog.php';

// Log the logout activity
if (isLoggedIn()) {
    logActivity('logout', 'User logged out', $_SESSION['user_id']);
}

// Clear all session data
session_unset();
session_destroy();

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Redirect to landing page
header('Location: ../index.php?logged_out=1');
exit();
?>