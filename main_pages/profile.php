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
        $email = sanitize($_POST['email']);
        
        // Validate inputs
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!validateEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        // Check if email is already in use by another user
        if ($email !== $userData['email']) {
            $existingUser = $userModel->getByEmail($email);
            if ($existingUser && $existingUser['id'] != $_SESSION['user_id']) {
                $errors[] = 'Email is already in use by another account';
            }
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
        
        // Process profile photo upload if provided
        $photoUpdated = false;
        $photoPath = null;
        
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['size'] > 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            // Validate file
            if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
                $errors[] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
            } elseif ($_FILES['profile_photo']['size'] > $maxSize) {
                $errors[] = 'File size is too large. Maximum 2MB is allowed.';
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = ROOT_PATH . 'uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $uploadPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
                    $photoPath = 'uploads/profiles/' . $filename;
                    $photoUpdated = true;
                } else {
                    $errors[] = 'Failed to upload profile photo.';
                }
            }
        }
        
        // If no errors, update profiles
        if (empty($errors)) {
            // Update user profile
            $userUpdateData = [
                'name' => $name,
                'email' => $email
            ];
            
            // Add photo path if uploaded
            if ($photoUpdated) {
                $userUpdateData['photo'] = $photoPath;
            }
            
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
                $_SESSION['email'] = $email;
                
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

// Get updated user data after form submission
$userData = $userModel->getById($_SESSION['user_id']);
if (isTutor()) {
    $tutorData = $tutorModel->getByUserId($_SESSION['user_id']);
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Profile Content -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/main_pages/profile.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="text-center mb-3">
                                    <?php if (!empty($userData['photo'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . $userData['photo']; ?>" alt="Profile Photo" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($userData['name']) ?>&background=random" alt="Profile Photo" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="profile_photo" class="form-label">Profile Photo</label>
                                        <input class="form-control form-control-sm" id="profile_photo" name="profile_photo" type="file" accept="image/*">
                                        <small class="text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Type</label>
                                    <p class="form-control-static"><?= ucfirst($userData['role']) ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <p class="form-control-static"><?= formatDate($userData['created_at']) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isTutor() && $tutorData): ?>
                            <hr>
                            <h5 class="mb-3">Tutor Information</h5>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject Expertise</label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars($tutorData['subject']) ?>" required>
                                <small class="text-muted">Specify your main area of expertise (e.g., Mathematics, Physics, English)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Hourly Rate ($)</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" min="1" step="0.01" value="<?= htmlspecialchars($tutorData['hourly_rate']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="5" required><?= htmlspecialchars($tutorData['bio']) ?></textarea>
                                <small class="text-muted">Describe your teaching experience, qualifications, and approach (300-500 characters recommended)</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Password Change -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/main_pages/profile.php" method="post">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="text-muted">Must be at least 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (isTutor()): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tutor Management</h6>
                </div>
                <div class="card-body">
                    <p>As a tutor, you can manage your:</p>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>/main_pages/schedule.php">Time slots and schedule</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/main_pages/dashboard.php">Booking requests</a></li>
                    </ul>
                    <p class="mb-0 text-muted small">Please keep your profile information up to date to attract more students.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>