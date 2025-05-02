<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

/**
 * Sanitize user input
 * 
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

/**
 * Redirect to a specific page
 * 
 * @param string $location The location to redirect to
 * @return void
 */
function redirect($location) {
    // If the location doesn't start with http or https (not an absolute URL)
    if (!preg_match('/^https?:\/\//', $location)) {
        header("Location: " . BASE_URL . "/" . $location);
    } else {
        header("Location: " . $location);
    }
    exit;
}

/**
 * Check if user is logged in
 * 
 * @return boolean True if the user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the user has a specific role
 * 
 * @param string $role The role to check
 * @return boolean True if the user has the role, false otherwise
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['role'] === $role;
}

/**
 * Check if the current user is a tutor
 * 
 * @return boolean True if the user is a tutor, false otherwise
 */
function isTutor() {
    return hasRole('tutor');
}

/**
 * Check if the current user is a student
 * 
 * @return boolean True if the user is a student, false otherwise
 */
function isStudent() {
    return hasRole('student');
}

/**
 * Get current logged in user data
 * 
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $userId = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Display a message to the user
 * 
 * @param string $message The message to display
 * @param string $type The type of message (success, error, info)
 * @return void
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Display the message if it exists and clear it
 * 
 * @return string|null The message HTML if exists, null otherwise
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        $alertClass = 'alert-info';
        if ($type === 'error') {
            $alertClass = 'alert-danger';
        } else if ($type === 'success') {
            $alertClass = 'alert-success';
        }
        
        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
    }
    
    return null;
}

/**
 * Format a date to a user-friendly string
 * 
 * @param string $date The date to format
 * @param string $format The format to use
 * @return string The formatted date
 */
function formatDate($date, $format = 'd M Y') {
    // Fix: Add null check to prevent deprecated warning
    if ($date === null) {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format a time to a user-friendly string
 * 
 * @param string $time The time to format
 * @param string $format The format to use
 * @return string The formatted time
 */
function formatTime($time, $format = 'H:i') {
    // Fix: Add null check to prevent deprecated warning
    if ($time === null) {
        return 'N/A';
    }
    return date($format, strtotime($time));
}

/**
 * Format currency (price)
 * 
 * @param float $amount The amount to format
 * @return string The formatted amount
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>