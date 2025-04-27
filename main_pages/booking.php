<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'models/booking.model.php';
require_once ROOT_PATH . 'models/schedule.model.php';
require_once ROOT_PATH . 'models/tutor.model.php';

// Initialize models
$bookingModel = new Booking($conn);
$scheduleModel = new Schedule($conn);
$tutorModel = new Tutor($conn);

// Check if viewing an existing booking or creating a new one
$bookingId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$scheduleId = isset($_GET['schedule']) && is_numeric($_GET['schedule']) ? (int)$_GET['schedule'] : null;

// Require login
requireLogin();

if ($bookingId) {
    // Viewing existing booking
    $booking = $bookingModel->getById($bookingId);
    
    if (!$booking) {
        setMessage('Booking not found', 'error');
        redirect('dashboard.php');
    }
    
    // Check if user has permission to view this booking
    $hasPermission = false;
    
    if (isStudent() && $booking['student_id'] == $_SESSION['user_id']) {
        $hasPermission = true;
    } elseif (isTutor() && isset($_SESSION['tutor_id']) && $booking['tutor_id'] == $_SESSION['tutor_id']) {
        $hasPermission = true;
    }
    
    if (!$hasPermission) {
        setMessage('You do not have permission to view this booking', 'error');
        redirect('dashboard.php');
    }
    
    // Get review if exists
    $review = $bookingModel->getReview($bookingId);
    
    // View mode
    $mode = 'view';
} elseif ($scheduleId) {
    // Creating a new booking
    
    // Require student role
    if (!isStudent()) {
        setMessage('Only students can make bookings', 'error');
        redirect('dashboard.php');
    }
    
    // Get schedule details
    $schedule = $scheduleModel->getById($scheduleId);
    
    if (!$schedule) {
        setMessage('Schedule not found', 'error');
        redirect('search.php');
    }
    
    // Check if schedule is available
    if (!$scheduleModel->isAvailable($scheduleId)) {
        setMessage('This time slot is no longer available', 'error');
        redirect('search.php?tutor=' . $schedule['tutor_id']);
    }
    
    // Get tutor details
    $tutor = $tutorModel->getById($schedule['tutor_id']);
    
    if (!$tutor) {
        setMessage('Tutor not found', 'error');
        redirect('search.php');
    }
    
    // Process booking form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
        // Create booking
        $bookingData = [
            'student_id' => $_SESSION['user_id'],
            'schedule_id' => $scheduleId
        ];
        
        $newBookingId = $bookingModel->create($bookingData);
        
        if ($newBookingId) {
            setMessage('Booking created successfully! The tutor will review your request.', 'success');
            redirect('booking.php?id=' . $newBookingId);
        } else {
            setMessage('Failed to create booking', 'error');
        }
    }
    
    // Create mode
    $mode = 'create';
} else {
    // Invalid request
    setMessage('Invalid request', 'error');
    redirect('dashboard.php');
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $newStatus = $_POST['status'];
    
    if ($bookingModel->updateStatus($bookingId, $newStatus)) {
        setMessage('Booking status updated successfully', 'success');
        redirect('booking.php?id=' . $bookingId);
    } else {
        setMessage('Failed to update booking status', 'error');
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $rating = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment']);
    
    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        setMessage('Rating must be between 1 and 5', 'error');
    } else {
        $reviewData = [
            'booking_id' => $bookingId,
            'rating' => $rating,
            'comment' => $comment
        ];
        
        if ($bookingModel->addReview($reviewData)) {
            setMessage('Review submitted successfully', 'success');
            redirect('booking.php?id=' . $bookingId);
        } else {
            setMessage('Failed to submit review', 'error');
        }
    }
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php if ($mode === 'view'): ?>
                            Booking Details
                        <?php else: ?>
                            New Booking
                        <?php endif; ?>
                    </h6>
                    <a href="<?= $mode === 'view' ? 'dashboard.php' : 'search.php?tutor=' . $tutor['id'] ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($mode === 'view'): ?>
                        <!-- View Booking Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Session Information</h5>
                                <p>
                                    <strong>Date:</strong> <?= formatDate($booking['date']) ?><br>
                                    <strong>Time:</strong> <?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?><br>
                                    <strong>Subject:</strong> <?= htmlspecialchars($booking['subject']) ?><br>
                                    <strong>Status:</strong> 
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                        <span class="badge bg-success">Confirmed</span>
                                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="col-md-6">
                                <?php if (isStudent()): ?>
                                    <h5>Tutor Information</h5>
                                    <p>
                                        <strong>Name:</strong> <?= htmlspecialchars($booking['tutor_name']) ?><br>
                                        <strong>Email:</strong> <?= htmlspecialchars($booking['tutor_email']) ?><br>
                                        <strong>Rate:</strong> <?= formatCurrency($booking['hourly_rate']) ?> per hour
                                    </p>
                                <?php else: ?>
                                    <h5>Student Information</h5>
                                    <p>
                                        <strong>Name:</strong> <?= htmlspecialchars($booking['student_name']) ?><br>
                                        <strong>Email:</strong> <?= htmlspecialchars($booking['student_email']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php
                        // Calculate price
                        $start = new DateTime($booking['start_time']);
                        $end = new DateTime($booking['end_time']);
                        $interval = $start->diff($end);
                        $hours = $interval->h;
                        $minutes = $interval->i;
                        $totalHours = $hours + ($minutes / 60);
                        $totalPrice = $totalHours * $booking['hourly_rate'];
                        ?>
                        
                        <div class="card mb-4">
                            <div class="card-body bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Session Duration</h6>
                                        <p class="mb-0">
                                            <?php
                                            $durationText = '';
                                            if ($hours > 0) {
                                                $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                            }
                                            if ($minutes > 0) {
                                                if ($hours > 0) {
                                                    $durationText .= ' and ';
                                                }
                                                $durationText .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                            }
                                            echo $durationText;
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h6>Total Price</h6>
                                        <p class="mb-0 h5"><?= formatCurrency($totalPrice) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <?php if ($booking['status'] === 'pending'): ?>
                            <div class="alert alert-warning">
                                <p class="mb-0">This booking is waiting for confirmation from the tutor.</p>
                            </div>
                            
                            <?php if (isTutor()): ?>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center mb-4">
                                    <form action="booking.php?id=<?= $bookingId ?>" method="post">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this booking?')">
                                            <i class="fas fa-check me-1"></i> Confirm Booking
                                        </button>
                                    </form>
                                    
                                    <form action="booking.php?id=<?= $bookingId ?>" method="post" class="ms-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times me-1"></i> Cancel Booking
                                        </button>
                                    </form>
                                </div>
                            <?php elseif (isStudent()): ?>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-center mb-4">
                                    <form action="booking.php?id=<?= $bookingId ?>" method="post">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times me-1"></i> Cancel Booking
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <div class="alert alert-success">
                                <p class="mb-0">This booking has been confirmed. Please attend the session at the scheduled time.</p>
                            </div>
                            
                            <?php if (isStudent() && !$review): ?>
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Leave a Review</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="booking.php?id=<?= $bookingId ?>" method="post">
                                            <input type="hidden" name="action" value="submit_review">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <div class="rating-input">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="rating" id="rating1" value="1" required>
                                                        <label class="form-check-label" for="rating1">1</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                                        <label class="form-check-label" for="rating2">2</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                                        <label class="form-check-label" for="rating3">3</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                                        <label class="form-check-label" for="rating4">4</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="rating" id="rating5" value="5">
                                                        <label class="form-check-label" for="rating5">5</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="comment" class="form-label">Comments</label>
                                                <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Share your experience with this tutor"></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary">Submit Review</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($review): ?>
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Your Review</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <span class="ms-1">(<?= $review['rating'] ?>/5)</span>
                                        </div>
                                        
                                        <?php if (!empty($review['comment'])): ?>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                        <?php else: ?>
                                            <p class="text-muted mb-0">No comment provided.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($booking['status'] === 'cancelled'): ?>
                            <div class="alert alert-danger">
                                <p class="mb-0">This booking has been cancelled.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Create New Booking -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Session Information</h5>
                                <p>
                                    <strong>Date:</strong> <?= formatDate($schedule['date']) ?><br>
                                    <strong>Time:</strong> <?= formatTime($schedule['start_time']) ?> - <?= formatTime($schedule['end_time']) ?><br>
                                    <strong>Subject:</strong> <?= htmlspecialchars($tutor['subject']) ?>
                                </p>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Tutor Information</h5>
                                <p>
                                    <strong>Name:</strong> <?= htmlspecialchars($tutor['name']) ?><br>
                                    <strong>Email:</strong> <?= htmlspecialchars($tutor['email']) ?><br>
                                    <strong>Rate:</strong> <?= formatCurrency($tutor['hourly_rate']) ?> per hour
                                </p>
                            </div>
                        </div>
                        
                        <?php
                        // Calculate price
                        $start = new DateTime($schedule['start_time']);
                        $end = new DateTime($schedule['end_time']);
                        $interval = $start->diff($end);
                        $hours = $interval->h;
                        $minutes = $interval->i;
                        $totalHours = $hours + ($minutes / 60);
                        $totalPrice = $totalHours * $tutor['hourly_rate'];
                        ?>
                        
                        <div class="card mb-4">
                            <div class="card-body bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Session Duration</h6>
                                        <p class="mb-0">
                                            <?php
                                            $durationText = '';
                                            if ($hours > 0) {
                                                $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                            }
                                            if ($minutes > 0) {
                                                if ($hours > 0) {
                                                    $durationText .= ' and ';
                                                }
                                                $durationText .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                            }
                                            echo $durationText;
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h6>Total Price</h6>
                                        <p class="mb-0 h5"><?= formatCurrency($totalPrice) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading">Booking Information</h6>
                            <p>By confirming this booking, you agree to:</p>
                            <ul class="mb-0">
                                <li>Attend the session at the scheduled time</li>
                                <li>Pay the tutor the agreed amount</li>
                                <li>Follow the platform's cancellation policy</li>
                            </ul>
                        </div>
                        
                        <form action="booking.php?schedule=<?= $scheduleId ?>" method="post">
                            <input type="hidden" name="action" value="create_booking">
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to book this session?')">
                                    <i class="fas fa-check me-1"></i> Confirm Booking
                                </button>
                                <a href="search.php?tutor=<?= $tutor['id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>