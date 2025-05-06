<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'models/booking.model.php';
require_once ROOT_PATH . 'models/payment.model.php';

// Require login
requireLogin();

// Initialize models
$bookingModel = new Booking($conn);
$paymentModel = new Payment($conn);

// Get payments based on user role
if (isStudent()) {
    $payments = $paymentModel->getByStudent($_SESSION['user_id']);
} elseif (isTutor() && isset($_SESSION['tutor_id'])) {
    $payments = $paymentModel->getByTutor($_SESSION['tutor_id']);
} else {
    $payments = [];
}

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Payment History</h1>
                <a href="<?= BASE_URL ?>/main_pages/dashboard.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Your Payments</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="far fa-credit-card fa-3x text-muted"></i>
                            </div>
                            <h5 class="mb-1">No Payment Records Found</h5>
                            <p class="text-muted">
                                <?php if (isStudent()): ?>
                                    You haven't made any payments yet.
                                <?php else: ?>
                                    No payments have been made for your sessions yet.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Session</th>
                                        <th>
                                            <?php if (isStudent()): ?>
                                                Tutor
                                            <?php else: ?>
                                                Student
                                            <?php endif; ?>
                                        </th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?= formatDate($payment['created_at']) ?></td>
                                            <td><?= formatDate($payment['date']) ?>, <?= formatTime($payment['start_time']) ?> - <?= formatTime($payment['end_time']) ?></td>
                                            <td>
                                                <?php if (isStudent()): ?>
                                                    <?= htmlspecialchars($payment['tutor_name']) ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($payment['student_name']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= formatCurrency($payment['amount']) ?></td>
                                            <td>
                                                <?php 
                                                switch($payment['payment_method']) {
                                                    case 'gopay':
                                                        echo '<span class="badge bg-success text-white">GoPay</span>';
                                                        break;
                                                    case 'dana':
                                                        echo '<span class="badge bg-primary text-white">DANA</span>';
                                                        break;
                                                    case 'bank_transfer':
                                                        echo '<span class="badge bg-info text-white">Bank Transfer</span>';
                                                        break;
                                                    default:
                                                        echo htmlspecialchars(ucfirst($payment['payment_method']));
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($payment['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($payment['status'] === 'failed'): ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/main_pages/booking.php?id=<?= $payment['booking_id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View Booking
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Payment Information</h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">About Payments</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="bg-success rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-credit-card text-white d-flex justify-content-center"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Secure Payments</h6>
                                        <p class="mb-0 small text-muted">All payment transactions are encrypted and secure.</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="bg-info rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-hand-holding-usd text-white d-flex justify-content-center"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Transparent Pricing</h6>
                                        <p class="mb-0 small text-muted">No hidden fees. Pay only for the time spent.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush mb-0">
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="bg-warning rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-wallet text-white d-flex justify-content-center"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Multiple Payment Methods</h6>
                                        <p class="mb-0 small text-muted">Choose from GoPay, DANA, or bank transfer.</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="bg-primary rounded-circle p-2 me-3" style="width: 40px; height: 40px;">
                                        <i class="fas fa-shield-alt text-white d-flex justify-content-center"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Satisfaction Guarantee</h6>
                                        <p class="mb-0 small text-muted">If you're not satisfied, we'll help resolve the issue.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
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