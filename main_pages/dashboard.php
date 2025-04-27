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

<!-- Rest of your dashboard.php HTML content remains the same but update all links and form actions to use BASE_URL -->

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>