<?php
require_once 'config.php';
require_once 'functions.php';

/**
 * Register a new user
 * 
 * @param string $name Full name of the user
 * @param string $email Email address
 * @param string $password Password
 * @param string $role User role (student or tutor)
 * @return array Result with status and message
 */
function registerUser($name, $email, $password, $role) {
    global $conn;
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        return ['status' => false, 'message' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => false, 'message' => 'Invalid email format'];
    }
    
    if (strlen($password) < 6) {
        return ['status' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    if ($role !== 'student' && $role !== 'tutor') {
        return ['status' => false, 'message' => 'Invalid role'];
    }
    
    // Check if email already exists
    $checkSql = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        return ['status' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        
        // If user is a tutor, create a tutor profile
        if ($role === 'tutor') {
            $tutorSql = "INSERT INTO tutors (user_id, bio, subject, hourly_rate) VALUES (?, ?, ?, ?)";
            $tutorStmt = $conn->prepare($tutorSql);
            $defaultBio = "No bio yet.";
            $defaultSubject = "General";
            $defaultRate = 20.00;
            $tutorStmt->bind_param("issd", $userId, $defaultBio, $defaultSubject, $defaultRate);
            $tutorStmt->execute();
        }
        
        return ['status' => true, 'message' => 'Registration successful! Please login.'];
    } else {
        return ['status' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
}

/**
 * Login a user
 * 
 * @param string $email Email address
 * @param string $password Password
 * @return array Result with status and message
 */
function loginUser($email, $password) {
    global $conn;
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        return ['status' => false, 'message' => 'Email and password are required'];
    }
    
    // Check if user exists
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // If user is a tutor, get tutor ID
            if ($user['role'] === 'tutor') {
                $tutorSql = "SELECT id FROM tutors WHERE user_id = ?";
                $tutorStmt = $conn->prepare($tutorSql);
                $tutorStmt->bind_param("i", $user['id']);
                $tutorStmt->execute();
                $tutorResult = $tutorStmt->get_result();
                
                if ($tutorResult->num_rows === 1) {
                    $tutor = $tutorResult->fetch_assoc();
                    $_SESSION['tutor_id'] = $tutor['id'];
                }
            }
            
            return ['status' => true, 'message' => 'Login successful!'];
        } else {
            return ['status' => false, 'message' => 'Invalid password'];
        }
    } else {
        return ['status' => false, 'message' => 'Email not found'];
    }
}

/**
 * Logout the current user
 * 
 * @return void
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Protect a page from unauthorized access
 * 
 * @param string|null $role Optional role required to access the page
 * @return void
 */
function requireLogin($role = null) {
    if (!isLoggedIn()) {
        setMessage('You must be logged in to access this page', 'error');
        redirect('login.php');
    }
    
    if ($role !== null && !hasRole($role)) {
        setMessage('You do not have permission to access this page', 'error');
        redirect('dashboard.php');
    }
}
?>