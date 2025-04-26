<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Logout the user
logoutUser();

// Redirect to login page
setMessage('You have been successfully logged out', 'success');
redirect('login.php');
?>