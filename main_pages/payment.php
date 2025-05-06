<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'models/booking.model.php';
require_once ROOT_PATH . 'models/tutor.model.php';
require_once ROOT_PATH . 'models/payment.model.php';

// Require login and student role
requireLogin('student');

// Initialize models
$bookingModel = new Booking($conn);
$tutorModel = new Tutor($conn);
$paymentModel = new Payment($conn);

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

// Check if user has permission to access this booking
if ($booking['student_id'] != $_SESSION['user_id']) {
    setMessage('You do not have permission to access this booking', 'error');
    redirect('dashboard.php');
}

// Check if booking is confirmed
if ($booking['status'] !== 'confirmed') {
    setMessage('Only confirmed bookings can be paid', 'error');
    redirect('booking.php?id=' . $bookingId);
}


// This file would be included in the payment.php page to handle specific payment methods
// For a real application, you would integrate with actual payment gateways

/**
 * Process GoPay payment
 * 
 * @param array $paymentData Payment data
 * @return array Result with status and message
 */
function processGopayPayment($paymentData) {
    // In a real application, you would integrate with the GoPay API
    // For this example, we'll simulate a successful payment
    
    return [
        'status' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => 'GP' . time() . rand(1000, 9999)
    ];
}

/**
 * Process DANA payment
 * 
 * @param array $paymentData Payment data
 * @return array Result with status and message
 */
function processDanaPayment($paymentData) {
    // In a real application, you would integrate with the DANA API
    // For this example, we'll simulate a successful payment
    
    return [
        'status' => true,
        'message' => 'Payment processed successfully',
        'transaction_id' => 'DA' . time() . rand(1000, 9999)
    ];
}

/**
 * Process bank transfer payment
 * 
 * @param array $paymentData Payment data
 * @return array Result with status and message
 */
function processBankTransferPayment($paymentData) {
    // In a real application, you would provide bank details and verify later
    // For this example, we'll simulate a successful payment
    
    return [
        'status' => true,
        'message' => 'Bank transfer instructions sent. Please complete the transfer within 24 hours.',
        'reference_number' => 'BT' . time() . rand(1000, 9999)
    ];
}

/**
 * Process payment based on method
 * 
 * @param string $method Payment method
 * @param array $paymentData Payment data
 * @return array Result with status and message
 */
function processPayment($method, $paymentData) {
    switch ($method) {
        case 'gopay':
            return processGopayPayment($paymentData);
        case 'dana':
            return processDanaPayment($paymentData);
        case 'bank_transfer':
            return processBankTransferPayment($paymentData);
        default:
            return [
                'status' => false,
                'message' => 'Invalid payment method'
            ];
    }
}

// Calculate payment amount
$amount = $paymentModel->calculateAmount($booking);

// Check if payment already exists
$existingPayment = $paymentModel->getByBookingId($bookingId);

if ($existingPayment) {
    if ($existingPayment['status'] === 'completed') {
        setMessage('This booking has already been paid', 'info');
        redirect('booking.php?id=' . $bookingId);
    }
    
    // Use existing payment
    $paymentId = $existingPayment['id'];
    $paymentStatus = $existingPayment['status'];
    $paymentMethod = $existingPayment['payment_method'];
} else {
    // Create new payment record if not submitted yet
    $paymentId = null;
    $paymentStatus = null;
    $paymentMethod = null;
}

// Process payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'process_payment') {
        $paymentMethod = sanitize($_POST['payment_method']);
        
        // Validate inputs
        if (empty($paymentMethod) || !in_array($paymentMethod, ['gopay', 'dana', 'bank_transfer'])) {
            setMessage('Invalid payment method', 'error');
        } else {
            // Create payment if doesn't exist yet
            if (!$existingPayment) {
                $paymentData = [
                    'booking_id' => $bookingId,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod
                ];
                
                $paymentId = $paymentModel->create($paymentData);
                
                if (!$paymentId) {
                    setMessage('Failed to create payment record', 'error');
                    redirect('payment.php?booking_id=' . $bookingId);
                }
            } else {
                $paymentId = $existingPayment['id'];
            }
            
            // For simplicity, in this implementation we'll automatically mark the payment as completed
            // In a real application, you would handle payment gateway integration here
            if ($paymentModel->processPayment($paymentId, $paymentMethod)) {
                setMessage('Payment completed successfully', 'success');
                redirect('booking.php?id=' . $bookingId);
            } else {
                setMessage('Payment processing failed', 'error');
            }
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
                    <h5 class="m-0 font-weight-bold text-primary">Payment for Tutoring Session</h5>
                    <a href="<?= BASE_URL ?>/main_pages/booking.php?id=<?= $bookingId ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Booking
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Session Information</h6>
                            <p>
                                <strong>Date:</strong> <?= formatDate($booking['date']) ?><br>
                                <strong>Time:</strong> <?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?><br>
                                <strong>Subject:</strong> <?= htmlspecialchars($booking['subject']) ?><br>
                                <strong>Tutor:</strong> <?= htmlspecialchars($booking['tutor_name']) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Details</h6>
                            <?php
                            // Calculate duration and price
                            $startTime = new DateTime($booking['start_time']);
                            $endTime = new DateTime($booking['end_time']);
                            $interval = $startTime->diff($endTime);
                            $hours = $interval->h;
                            $minutes = $interval->i;
                            $totalHours = $hours + ($minutes / 60);
                            $totalPrice = $amount;
                            ?>
                            <p>
                                <strong>Session Duration:</strong> 
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
                                ?><br>
                                <strong>Hourly Rate:</strong> <?= formatCurrency($booking['hourly_rate']) ?><br>
                                <strong>Total Amount:</strong> <span class="h4 text-primary"><?= formatCurrency($totalPrice) ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($existingPayment && $existingPayment['status'] === 'completed'): ?>
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading">Payment Completed</h5>
                                    <p class="mb-0">Your payment has been successfully processed. Thank you!</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="<?= BASE_URL ?>/main_pages/booking.php?id=<?= $bookingId ?>" class="btn btn-primary">
                                Return to Booking Details
                            </a>
                        </div>
                    <?php elseif ($existingPayment && $existingPayment['status'] === 'failed'): ?>
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-exclamation-circle fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading">Payment Failed</h5>
                                    <p class="mb-0">Your previous payment attempt failed. Please try again.</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php include_once 'payment_form.php'; ?>
                    <?php else: ?>
                        <form action="<?= BASE_URL ?>/main_pages/payment.php?booking_id=<?= $bookingId ?>" method="post" id="payment-form">
                            <input type="hidden" name="action" value="process_payment">
                            
                            <div class="mb-4">
                                <h5>Select Payment Method</h5>
                                
                                <div class="row mt-3">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check payment-method-option">
                                            <input class="form-check-input" type="radio" name="payment_method" id="gopay" value="gopay" <?= (isset($paymentMethod) && $paymentMethod === 'gopay') ? 'checked' : '' ?> required>
                                            <label class="form-check-label d-flex align-items-center" for="gopay">
                                                <div class="payment-icon bg-success text-white rounded me-2">
                                                    <i class="fas fa-wallet"></i>
                                                </div>
                                                <div>
                                                    <strong>GoPay</strong>
                                                    <small class="d-block text-muted">Instant payment</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check payment-method-option">
                                            <input class="form-check-input" type="radio" name="payment_method" id="dana" value="dana" <?= (isset($paymentMethod) && $paymentMethod === 'dana') ? 'checked' : '' ?> required>
                                            <label class="form-check-label d-flex align-items-center" for="dana">
                                                <div class="payment-icon bg-primary text-white rounded me-2">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                </div>
                                                <div>
                                                    <strong>DANA</strong>
                                                    <small class="d-block text-muted">e-Wallet payment</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check payment-method-option">
                                            <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer" <?= (isset($paymentMethod) && $paymentMethod === 'bank_transfer') ? 'checked' : '' ?> required>
                                            <label class="form-check-label d-flex align-items-center" for="bank_transfer">
                                                <div class="payment-icon bg-info text-white rounded me-2">
                                                    <i class="fas fa-university"></i>
                                                </div>
                                                <div>
                                                    <strong>Bank Transfer</strong>
                                                    <small class="d-block text-muted">Manual verification</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-4">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="fas fa-info-circle fa-2x"></i>
                                    </div>
                                    <div>
                                        <h6>Payment Information</h6>
                                        <p class="mb-0">Your payment will be securely processed. In a real application, you would be redirected to the selected payment gateway.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/main_pages/booking.php?id=<?= $bookingId ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-credit-card me-1"></i> Pay Now <?= formatCurrency($totalPrice) ?>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method-option {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.payment-method-option:hover {
    background-color: #f8f9fc;
}

.payment-method-option .form-check-input:checked ~ .form-check-label {
    font-weight: 600;
}

.payment-method-option:has(.form-check-input:checked) {
    border-color: #4e73df;
    background-color: #f8f9fc;
    box-shadow: 0 0 0 1px #4e73df;
}

.payment-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

#payment-form .btn-lg {
    padding: 0.75rem 1.5rem;
}
</style>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>