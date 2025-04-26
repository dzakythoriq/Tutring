<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set session timeout to 30 minutes
$session_timeout = 1800; // 30 minutes in seconds

// Check if session has timed out
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    
    // Redirect to login page with timeout message
    header("Location: " . BASE_URL . "/login.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>