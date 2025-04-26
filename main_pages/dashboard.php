<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/booking.model.php';
require_once 'models/tutor.model.php';
require_once 'models/schedule.model.php';

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
    redirect('dashboard.php');
}

// Include header
include_once 'views/header.php';
?>

<div class="container py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        
        <?php if ($dashboardType === 'tutor'): ?>
            <a href="schedule.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Schedule
            </a>
        <?php else: ?>
            <a href="search.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                <i class="fas fa-search fa-sm text-white-50"></i> Find Tutors
            </a>
        <?php endif; ?>
    </div>

    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 stat-card stat-card-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Bookings
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($bookings) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 stat-card stat-card-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Confirmed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($confirmedBookings) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 stat-card stat-card-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($pendingBookings) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 stat-card stat-card-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Cancelled
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($cancelledBookings) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                    <?php if (count($bookings) > 5): ?>
                        <a href="<?= $dashboardType === 'tutor' ? 'bookings_tutor.php' : 'bookings_student.php' ?>" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                        <div class="text-center">
                            <p class="lead text-muted">No bookings found</p>
                            <?php if ($dashboardType === 'student'): ?>
                                <a href="search.php" class="btn btn-primary">Find a Tutor</a>
                            <?php elseif ($dashboardType === 'tutor'): ?>
                                <p class="text-muted">Create schedules to receive bookings</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <?php if ($dashboardType === 'student'): ?>
                                            <th>Tutor</th>
                                            <th>Subject</th>
                                        <?php else: ?>
                                            <th>Student</th>
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
                                            <?php if ($dashboardType === 'student'): ?>
                                                <td><?= htmlspecialchars($booking['tutor_name']) ?></td>
                                                <td><?= htmlspecialchars($booking['subject']) ?></td>
                                            <?php else: ?>
                                                <td><?= htmlspecialchars($booking['student_name']) ?></td>
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
                                                <a href="booking.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($dashboardType === 'tutor' && $booking['status'] === 'pending'): ?>
                                                    <form action="dashboard.php" method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <form action="dashboard.php" method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($dashboardType === 'student' && $booking['status'] === 'confirmed' && !$bookingModel->hasReview($booking['id'])): ?>
                                                    <a href="review.php?booking_id=<?= $booking['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-star"></i> Review
                                                    </a>
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

        <!-- Right Column -->
        <div class="col-lg-4">
            <?php if ($dashboardType === 'tutor'): ?>
                <!-- Upcoming Schedule -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Upcoming Schedule</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($schedules)): ?>
                            <div class="text-center">
                                <p>No upcoming schedules</p>
                                <a href="schedule.php" class="btn btn-primary btn-sm">Add Schedule</a>
                            </div>
                        <?php else: ?>
                            <?php 
                            // Group schedules by date
                            $schedulesByDate = [];
                            foreach ($schedules as $schedule) {
                                $date = $schedule['date'];
                                if (!isset($schedulesByDate[$date])) {
                                    $schedulesByDate[$date] = [];
                                }
                                $schedulesByDate[$date][] = $schedule;
                            }
                            
                            // Only show next 5 days
                            $count = 0;
                            foreach ($schedulesByDate as $date => $daySchedules):
                                if ($count >= 5) break;
                                $count++;
                            ?>
                                <div class="mb-3">
                                    <h6 class="font-weight-bold"><?= formatDate($date, 'l, d M Y') ?></h6>
                                    
                                    <?php foreach ($daySchedules as $schedule): ?>
                                        <div class="card mb-2 schedule-card <?= $schedule['is_booked'] ? 'booked' : '' ?>">
                                            <div class="card-body py-2 px-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="far fa-clock me-1"></i>
                                                        <?= formatTime($schedule['start_time']) ?> - <?= formatTime($schedule['end_time']) ?>
                                                    </span>
                                                    <?php if ($schedule['is_booked']): ?>
                                                        <span class="badge bg-success">Booked</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Available</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <a href="schedule.php" class="btn btn-primary btn-sm">Manage Schedules</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Profile Summary -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tutor Profile</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=random" alt="<?= htmlspecialchars($_SESSION['name']) ?>" class="img-profile rounded-circle" style="width: 100px; height: 100px;">
                            <h5 class="mt-2"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                            <p class="mb-1"><?= htmlspecialchars($tutorData['subject']) ?></p>
                            <p class="text-muted"><?= formatCurrency($tutorData['hourly_rate']) ?> / hour</p>
                        </div>
                        
                        <?php 
                        $rating = $tutorModel->getAverageRating($tutorId);
                        ?>
                        <div class="text-center mb-3">
                            <div class="tutor-rating">
                                <?php if ($rating): ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $rating): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="ms-1">(<?= $rating ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted">No ratings yet</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6>About</h6>
                            <p><?= nl2br(htmlspecialchars($tutorData['bio'])) ?></p>
                        </div>
                        
                        <div class="text-center">
                            <a href="profile.php" class="btn btn-primary btn-sm">Edit Profile</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Student Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Your Profile</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=random" alt="<?= htmlspecialchars($_SESSION['name']) ?>" class="img-profile rounded-circle" style="width: 100px; height: 100px;">
                            <h5 class="mt-2"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($_SESSION['email']) ?></p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-primary btn-sm">Edit Profile</a>
                            <a href="search.php" class="btn btn-info btn-sm">Find Tutors</a>
                        </div>
                    </div>
                </div>
                
                <!-- Tips for Students -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Tips for Success</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Be prepared with specific questions
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Set clear learning goals
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Attend sessions on time
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Take notes during sessions
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Provide feedback to improve tutoring
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'views/footer.php';
?>