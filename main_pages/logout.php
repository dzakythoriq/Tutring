<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';

// Logout the user
logoutUser();

// Redirect to login page
setMessage('You have been successfully logged out', 'success');
redirect('main_pages/login.php');
?>