<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'configs/validation.php';
require_once ROOT_PATH . 'models/user.model.php';
require_once ROOT_PATH . 'models/tutor.model.php';

// Require login
requireLogin();

// Initialize models
$userModel = new User($conn);
$tutorModel = new Tutor($conn);

// Get user data
$userData = $userModel->getById($_SESSION['user_id']);

// Get tutor data if user is a tutor
$tutorData = null;
if (isTutor()) {
    $tutorData = $tutorModel->getByUserId($_SESSION['user_id']);
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = sanitize($_POST['name']);
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        // If user is a tutor, validate tutor fields
        if (isTutor()) {
            $bio = sanitize($_POST['bio']);
            $subject = sanitize($_POST['subject']);
            $hourlyRate = sanitize($_POST['hourly_rate']);
            
            if (empty($bio)) {
                $errors[] = 'Bio is required';
            }
            
            if (empty($subject)) {
                $errors[] = 'Subject is required';
            }
            
            if (empty($hourlyRate)) {
                $errors[] = 'Hourly rate is required';
            } elseif (!is_numeric($hourlyRate) || $hourlyRate <= 0) {
                $errors[] = 'Hourly rate must be a positive number';
            }
        }
        
        // If no errors, update profiles
        if (empty($errors)) {
            // Update user profile
            $userUpdateData = [
                'name' => $name
            ];
            
            $userUpdated = $userModel->update($_SESSION['user_id'], $userUpdateData);
            
            // Update tutor profile if user is a tutor
            $tutorUpdated = true;
            if (isTutor()) {
                $tutorUpdateData = [
                    'bio' => $bio,
                    'subject' => $subject,
                    'hourly_rate' => $hourlyRate
                ];
                
                $tutorUpdated = $tutorModel->update($tutorData['id'], $tutorUpdateData);
            }
            
            if ($userUpdated && $tutorUpdated) {
                // Update session data
                $_SESSION['name'] = $name;
                
                setMessage('Profile updated successfully', 'success');
                redirect('main_pages/profile.php');
            } else {
                setMessage('Failed to update profile', 'error');
            }
        } else {
            // Display all errors
            foreach ($errors as $error) {
                setMessage($error, 'error');
            }
        }
    }
    
    // Change password
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate inputs
        $errors = [];
        
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        } elseif (!$userModel->verifyPassword($_SESSION['user_id'], $currentPassword)) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If no errors, update password
        if (empty($errors)) {
            if ($userModel->updatePassword($_SESSION['user_id'], $newPassword)) {
                setMessage('Password changed successfully', 'success');
                redirect('main_pages/profile.php');
            } else {
                setMessage('Failed to change password', 'error');
            }
        } else {
            // Display all errors
            foreach ($errors as $error) {
                setMessage($error, 'error');
            }
        }
    }
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<!-- Rest of your profile.php HTML content remains the same -->

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>