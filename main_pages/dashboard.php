<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'models/booking.model.php';
require_once ROOT_PATH . 'models/tutor.model.php';
require_once ROOT_PATH . 'models/schedule.model.php';

// Require login
requireLogin();

// Initialize models
$bookingModel = new Booking($conn);
$tutorModel = new Tutor($conn);
$scheduleModel = new Schedule($conn);

// Load the appropriate dashboard based on user role
if (isTutor()) {
    // Get tutor details
    $tutorData = $tutorModel->getByUserId($_SESSION['user_id']);
    $tutorId = $tutorData['id'];
    
    // Get tutor's bookings
    $bookings = $bookingModel->getByTutor($tutorId);
    
    // Get tutor's schedules (future dates only)
    $currentDate = date('Y-m-d');
    $schedules = $scheduleModel->getByTutor($tutorId, $currentDate);
    
    // Count bookings by status
    $pendingBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'pending';
    });
    
    $confirmedBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'confirmed';
    });
    
    $cancelledBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'cancelled';
    });
    
    // Only show the most recent bookings on dashboard
    $recentBookings = array_slice($bookings, 0, 5);
    
    // Dashboard type
    $dashboardType = 'tutor';
} else {
    // Student dashboard
    $studentId = $_SESSION['user_id'];
    
    // Get student's bookings
    $bookings = $bookingModel->getByStudent($studentId);
    
    // Only show the most recent bookings on dashboard
    $recentBookings = array_slice($bookings, 0, 5);
    
    // Count bookings by status
    $pendingBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'pending';
    });
    
    $confirmedBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'confirmed';
    });
    
    $cancelledBookings = array_filter($bookings, function($booking) {
        return $booking['status'] === 'cancelled';
    });
    
    // Dashboard type
    $dashboardType = 'student';
}

// Process booking status update (for tutors)
if (isTutor() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['status'];
    
    if ($bookingModel->updateStatus($bookingId, $newStatus)) {
        setMessage('Booking status updated successfully', 'success');
    } else {
        setMessage('Failed to update booking status', 'error');
    }
    
    // Redirect to refresh the page
    redirect('main_pages/dashboard.php');
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <?php if (!empty($_SESSION['photo'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $_SESSION['photo']; ?>" alt="Profile" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <span class="text-white fs-1"><?= substr($_SESSION['name'], 0, 1) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ms-4">
                            <h2 class="mb-1">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
                            <p class="text-muted mb-0">
                                <?php if (isTutor()): ?>
                                    You're logged in as a tutor. Manage your sessions and help students succeed.
                                <?php else: ?>
                                    You're logged in as a student. Find tutors and book sessions to achieve your academic goals.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- About Tutring Section -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">About Tutring - Your Private Tutoring Platform</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-7">
                            <h4>What is Tutring?</h4>
                            <p>Tutring is an innovative online platform designed to connect students with qualified private tutors. Our mission is to make quality education accessible to all students by providing a seamless connection between learners and educators.</p>
                            
                            <h5 class="mt-4">Key Features</h5>
                            <ul>
                                <li><strong>Find Expert Tutors:</strong> Browse our collection of qualified tutors specialized in various subjects.</li>
                                <li><strong>Easy Booking:</strong> Book sessions with your preferred tutor at times that work for you.</li>
                                <li><strong>Secure Platform:</strong> All communications and bookings happen within our secure environment.</li>
                                <li><strong>Rating System:</strong> Read and leave reviews to help others find the best tutors.</li>
                                <li><strong>Flexible Schedule:</strong> Tutors can set their availability, and students can choose convenient time slots.</li>
                                <li><strong>Dashboard Management:</strong> Manage all your sessions, schedules, and payments in one place.</li>
                            </ul>
                            
                            <h5 class="mt-4">How It Works</h5>
                            <ol>
                                <li><strong>For Students:</strong> Sign up, search for tutors by subject, book sessions, and improve your academic performance.</li>
                                <li><strong>For Tutors:</strong> Create your profile, set your schedule and rates, accept bookings, and help students achieve their goals.</li>
                            </ol>
                        </div>
                        <div class="col-lg-5">
                            <div class="card bg-light mt-3">
                                <div class="card-body">
                                    <h5 class="card-title">Why Choose Tutring?</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary rounded-circle p-2 me-3" style="width: 45px; height: 45px;">
                                            <i class="fas fa-user-graduate text-white fs-4 d-flex justify-content-center"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Qualified Tutors</h6>
                                            <p class="mb-0 small">Learn from experts in their fields</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success rounded-circle p-2 me-3" style="width: 45px; height: 45px;">
                                            <i class="fas fa-calendar-check text-white fs-4 d-flex justify-content-center"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Flexible Scheduling</h6>
                                            <p class="mb-0 small">Learn when it suits you best</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info rounded-circle p-2 me-3" style="width: 45px; height: 45px;">
                                            <i class="fas fa-laptop text-white fs-4 d-flex justify-content-center"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Easy-to-Use Platform</h6>
                                            <p class="mb-0 small">Book and manage sessions seamlessly</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning rounded-circle p-2 me-3" style="width: 45px; height: 45px;">
                                            <i class="fas fa-star text-white fs-4 d-flex justify-content-center"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">Verified Reviews</h6>
                                            <p class="mb-0 small">Choose tutors based on real feedback</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-primary btn-lg">Find a Tutor Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <?php if ($dashboardType === 'tutor'): ?>
            <!-- Tutor Stats -->
            <div class="col-md-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Bookings</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($pendingBookings) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Confirmed Sessions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($confirmedBookings) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Available Time Slots</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($schedules) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Student Stats -->
            <div class="col-md-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Bookings</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($pendingBookings) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Confirmed Sessions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($confirmedBookings) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Sessions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($bookings) ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Bookings/Sessions Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                    <?php if (isTutor()): ?>
                        <a href="<?php echo BASE_URL; ?>/main_pages/schedule.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-calendar-plus fa-sm"></i> Manage Schedule
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-search fa-sm"></i> Find Tutors
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar-day fa-3x text-muted"></i>
                            </div>
                            <p class="lead text-muted">No bookings found</p>
                            <?php if (isTutor()): ?>
                                <p class="text-muted mb-4">Your bookings will appear here once students book sessions with you.</p>
                                <a href="<?php echo BASE_URL; ?>/main_pages/schedule.php" class="btn btn-primary">
                                    Set Your Availability
                                </a>
                            <?php else: ?>
                                <p class="text-muted mb-4">Start by finding a tutor and booking your first session.</p>
                                <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-primary">
                                    Find a Tutor
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <?php if (isTutor()): ?>
                                            <th>Student</th>
                                        <?php else: ?>
                                            <th>Tutor</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td><?= formatDate($booking['date']) ?></td>
                                            <td><?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?></td>
                                            <?php if (isTutor()): ?>
                                                <td><?= htmlspecialchars($booking['student_name']) ?></td>
                                            <?php else: ?>
                                                <td><?= htmlspecialchars($booking['tutor_name']) ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span>
                                                <?php elseif ($booking['status'] === 'cancelled'): ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/main_pages/booking.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <?php if (isTutor() && $booking['status'] === 'pending'): ?>
                                                    <form action="<?php echo BASE_URL; ?>/main_pages/dashboard.php" method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                                            <i class="fas fa-check"></i> Confirm
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Getting Started Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Getting Started with Tutring</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (isTutor()): ?>
                            <!-- For Tutors -->
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Complete Your Profile</h5>
                                        <p class="card-text">Add your qualifications, set your hourly rate, and write a compelling bio to attract students.</p>
                                        <a href="<?php echo BASE_URL; ?>/main_pages/profile.php" class="btn btn-primary btn-sm">Update Profile</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-calendar-alt fa-3x text-success"></i>
                                        </div>
                                        <h5 class="card-title">Set Your Schedule</h5>
                                        <p class="card-text">Define when you're available to teach so students can book sessions during those times.</p>
                                        <a href="<?php echo BASE_URL; ?>/main_pages/schedule.php" class="btn btn-success btn-sm">Manage Schedule</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-chalkboard-teacher fa-3x text-info"></i>
                                        </div>
                                        <h5 class="card-title">Accept Bookings</h5>
                                        <p class="card-text">Respond to booking requests and deliver high-quality tutoring sessions to earn great reviews.</p>
                                        <a href="#" class="btn btn-info btn-sm">View Bookings</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- For Students -->
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-search fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Find a Tutor</h5>
                                        <p class="card-text">Browse our extensive list of qualified tutors and filter by subject, price, and availability.</p>
                                        <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-primary btn-sm">Search Tutors</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-calendar-check fa-3x text-success"></i>
                                        </div>
                                        <h5 class="card-title">Book a Session</h5>
                                        <p class="card-text">Select a convenient time slot from your chosen tutor's schedule and confirm your booking.</p>
                                        <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-success btn-sm">Book Now</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-star fa-3x text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Leave Reviews</h5>
                                        <p class="card-text">After your session, share your experience by rating and reviewing your tutor to help others.</p>
                                        <a href="#" class="btn btn-warning btn-sm text-white">Rate Tutors</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add some CSS for styling -->
<style>
    /* Card border left utility classes */
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    
    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }
</style>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>