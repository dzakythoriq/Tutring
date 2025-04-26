<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/validation.php';
require_once 'models/user.model.php';
require_once 'models/tutor.model.php';

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
                redirect('profile.php');
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
                redirect('profile.php');
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
include_once 'views/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=random&size=128" alt="<?= htmlspecialchars($_SESSION['name']) ?>" class="img-profile rounded-circle mb-3" style="width: 128px; height: 128px;">
                    
                    <h4><?= htmlspecialchars($_SESSION['name']) ?></h4>
                    <p class="text-muted mb-1"><?= htmlspecialchars($_SESSION['email']) ?></p>
                    <p class="text-muted mb-3">
                        <span class="badge rounded-pill bg-<?= isTutor() ? 'primary' : 'info' ?>">
                            <?= isTutor() ? 'Tutor' : 'Student' ?>
                        </span>
                    </p>
                    
                    <div class="d-grid">
                        <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                    </div>
                </div>
            </div>
            
            <?php if (isTutor() && $tutorData): ?>
                <!-- Tutor Stats -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tutor Statistics</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $rating = $tutorModel->getAverageRating($tutorData['id']);
                        ?>
                        
                        <div class="text-center mb-3">
                            <div class="tutor-rating">
                                <h6>Rating</h6>
                                <?php if ($rating): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $rating): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <div class="mt-1"><?= $rating ?> out of 5</div>
                                <?php else: ?>
                                    <div class="text-muted">No ratings yet</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <h6>Hourly Rate</h6>
                                <div class="h5"><?= formatCurrency($tutorData['hourly_rate']) ?></div>
                            </div>
                            <div class="col-6">
                                <h6>Subject</h6>
                                <div class="h5"><?= htmlspecialchars($tutorData['subject']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-8">
            <!-- Edit Profile -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="post">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($userData['email']) ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        
                        <?php if (isTutor() && $tutorData): ?>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject Expertise</label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars($tutorData['subject']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="hourly_rate" class="form-label">Hourly Rate ($)</label>
                                <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" value="<?= htmlspecialchars($tutorData['hourly_rate']) ?>" min="1" step="0.01" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="5" required><?= htmlspecialchars($tutorData['bio']) ?></textarea>
                                <small class="text-muted">Tell students about your experience, qualifications, and teaching style</small>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="post">
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
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'views/footer.php';
?>