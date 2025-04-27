<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('main_pages/dashboard.php');
}

// Set default role or get from URL parameter
$role = isset($_GET['role']) && $_GET['role'] === 'tutor' ? 'tutor' : 'student';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // No need to sanitize password as it will be hashed
    $confirmPassword = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if ($role !== 'student' && $role !== 'tutor') {
        $errors[] = 'Invalid role';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $result = registerUser($name, $email, $password, $role);
        
        if ($result['status']) {
            setMessage($result['message'], 'success');
            redirect('main_pages/login.php');
        } else {
            setMessage($result['message'], 'error');
        }
    } else {
        // Display all errors
        foreach ($errors as $error) {
            setMessage($error, 'error');
        }
    }
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card auth-card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create a New Account</h2>
                    
                    <ul class="nav nav-pills mb-4 nav-justified" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $role === 'student' ? 'active' : '' ?>" id="student-tab" data-bs-toggle="pill" data-bs-target="#student" type="button">Student</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $role === 'tutor' ? 'active' : '' ?>" id="tutor-tab" data-bs-toggle="pill" data-bs-target="#tutor" type="button">Tutor</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade <?= $role === 'student' ? 'show active' : '' ?>" id="student" role="tabpanel">
                            <form action="<?php echo BASE_URL; ?>/main_pages/register.php" method="post">
                                <input type="hidden" name="role" value="student">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="text-muted">Must be at least 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Register as Student</button>
                            </form>
                        </div>
                        
                        <div class="tab-pane fade <?= $role === 'tutor' ? 'show active' : '' ?>" id="tutor" role="tabpanel">
                            <form action="<?php echo BASE_URL; ?>/main_pages/register.php" method="post">
                                <input type="hidden" name="role" value="tutor">
                                
                                <div class="mb-3">
                                    <label for="tutor_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="tutor_name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tutor_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="tutor_email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tutor_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="tutor_password" name="password" required>
                                    <small class="text-muted">Must be at least 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tutor_confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="tutor_confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Register as Tutor</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="<?php echo BASE_URL; ?>/main_pages/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Script to update form action based on tab selection
    document.addEventListener('DOMContentLoaded', function() {
        const studentTab = document.getElementById('student-tab');
        const tutorTab = document.getElementById('tutor-tab');
        
        studentTab.addEventListener('click', function() {
            history.replaceState(null, '', '<?php echo BASE_URL; ?>/main_pages/register.php?role=student');
        });
        
        tutorTab.addEventListener('click', function() {
            history.replaceState(null, '', '<?php echo BASE_URL; ?>/main_pages/register.php?role=tutor');
        });
    });
</script>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>