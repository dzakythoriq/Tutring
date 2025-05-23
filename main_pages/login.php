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

// Check for timeout
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;
if ($timeout) {
    setMessage('Your session has expired. Please login again.', 'info');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password']; // No need to sanitize password as it will be verified using password_verify
    
    $result = loginUser($email, $password);
    
    if ($result['status']) {
        // Redirect based on role
        if (hasRole('tutor')) {
            redirect('main_pages/dashboard.php');
        } else {
            redirect('main_pages/dashboard.php');
        }
    } else {
        setMessage($result['message'], 'error');
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
                    <h2 class="text-center mb-4">Login to Your Account</h2>
                    
                    <form action="<?php echo BASE_URL; ?>/main_pages/login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/main_pages/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>