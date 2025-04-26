<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'models/booking.model.php';
require_once 'models/tutor.model.php';

// Require login and student role
requireLogin('student');

// Initialize models
$bookingModel = new Booking($conn);
$tutorModel = new Tutor($conn);

// Get booking ID from URL
$bookingId = isset($_GET['booking_id']) && is_numeric($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

if (!$bookingId) {
    setMessage('Invalid booking ID', 'error');
    redirect('dashboard.php');
}

// Get booking details
$booking = $bookingModel->getById($bookingId);

if (!$booking) {
    setMessage('Booking not found', 'error');
    redirect('dashboard.php');
}

// Check if user has permission to review this booking
if ($booking['student_id'] != $_SESSION['user_id']) {
    setMessage('You do not have permission to review this booking', 'error');
    redirect('dashboard.php');
}

// Check if booking is confirmed
if ($booking['status'] !== 'confirmed') {
    setMessage('You can only review confirmed bookings', 'error');
    redirect('dashboard.php');
}

// Check if booking already has a review
if ($bookingModel->hasReview($bookingId)) {
    setMessage('You have already reviewed this booking', 'info');
    redirect('booking.php?id=' . $bookingId);
}

// Process review form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
include_once 'views/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Leave a Review</h6>
                    <a href="booking.php?id=<?= $bookingId ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Booking
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Session Details</h5>
                        <p>
                            <strong>Date:</strong> <?= formatDate($booking['date']) ?><br>
                            <strong>Time:</strong> <?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?><br>
                            <strong>Tutor:</strong> <?= htmlspecialchars($booking['tutor_name']) ?><br>
                            <strong>Subject:</strong> <?= htmlspecialchars($booking['subject']) ?>
                        </p>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <p class="mb-0">Your honest feedback helps other students find great tutors and helps tutors improve their teaching.</p>
                    </div>
                    
                    <form action="review.php?booking_id=<?= $bookingId ?>" method="post">
                        <div class="mb-4">
                            <label class="form-label">How would you rate your session with <?= htmlspecialchars($booking['tutor_name']) ?>?</label>
                            
                            <div class="rating-select mb-3">
                                <div class="d-flex justify-content-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="mx-2 text-center">
                                            <input type="radio" class="btn-check" name="rating" id="rating<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?> required>
                                            <label class="btn btn-outline-warning btn-lg" for="rating<?= $i ?>">
                                                <i class="fas fa-star"></i><br>
                                                <span class="small"><?= $i ?></span>
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Poor</small>
                                    <small class="text-muted">Excellent</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="comment" class="form-label">Your Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="5" placeholder="Share your experience about the tutor, the session, and what you learned"></textarea>
                            <small class="text-muted">Optional, but very helpful for other students</small>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="booking.php?id=<?= $bookingId ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </div>
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