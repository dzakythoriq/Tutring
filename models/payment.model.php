<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

class Payment {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get payment by ID
     * 
     * @param int $id Payment ID
     * @return array|false Payment data if found, false otherwise
     */
    public function getById($id) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Get payment by booking ID
     * 
     * @param int $bookingId Booking ID
     * @return array|false Payment data if found, false otherwise
     */
    public function getByBookingId($bookingId) {
        $sql = "SELECT * FROM payments WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Check if a booking has an existing payment
     * 
     * @param int $bookingId Booking ID
     * @return boolean True if payment exists, false otherwise
     */
    public function hasPayment($bookingId) {
        $sql = "SELECT COUNT(*) as count FROM payments WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Check if a booking has a completed payment
     * 
     * @param int $bookingId Booking ID
     * @return boolean True if payment is completed, false otherwise
     */
    public function isPaymentCompleted($bookingId) {
        $sql = "SELECT COUNT(*) as count FROM payments WHERE booking_id = ? AND status = 'completed'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Create a new payment record
     * 
     * @param array $paymentData Payment data (booking_id, amount, payment_method)
     * @return int|false New payment ID if created, false otherwise
     */
    public function create($paymentData) {
        // Check if payment already exists for this booking
        if ($this->hasPayment($paymentData['booking_id'])) {
            return false;
        }
        
        $sql = "INSERT INTO payments (booking_id, amount, payment_method, status) 
                VALUES (?, ?, ?, 'pending')";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("ids", 
            $paymentData['booking_id'], 
            $paymentData['amount'], 
            $paymentData['payment_method']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update payment status
     * 
     * @param int $id Payment ID
     * @param string $status New status (pending, completed, failed)
     * @return boolean True if updated, false otherwise
     */
    public function updateStatus($id, $status) {
        $sql = "UPDATE payments SET status = ?";
        
        // If status is completed, update paid_at timestamp
        if ($status === 'completed') {
            $sql .= ", paid_at = CURRENT_TIMESTAMP";
        }
        
        $sql .= " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("si", $status, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Process payment based on payment method
     * 
     * @param int $paymentId Payment ID
     * @param string $paymentMethod Payment method
     * @param array $paymentDetails Additional payment details (e.g., transaction ID)
     * @return boolean True if processed successfully, false otherwise
     */
    public function processPayment($paymentId, $paymentMethod, $paymentDetails = []) {
        // This would typically integrate with a payment gateway
        // For now, we'll simulate a successful payment
        
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Get payment details
            $payment = $this->getById($paymentId);
            
            if (!$payment) {
                return false;
            }
            
            // Update payment status to completed
            $this->updateStatus($paymentId, 'completed');
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Get all payments by student
     * 
     * @param int $studentId Student ID
     * @return array List of payments
     */
    public function getByStudent($studentId) {
        $sql = "SELECT p.*, b.id as booking_id, s.date, s.start_time, s.end_time,
                t.subject, t.hourly_rate, 
                u.name as tutor_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                JOIN schedules s ON b.schedule_id = s.id
                JOIN tutors t ON s.tutor_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE b.student_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
    
    /**
     * Get all payments for a tutor
     * 
     * @param int $tutorId Tutor ID
     * @return array List of payments
     */
    public function getByTutor($tutorId) {
        $sql = "SELECT p.*, b.id as booking_id, s.date, s.start_time, s.end_time,
                u.name as student_name, u.email as student_email
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                JOIN schedules s ON b.schedule_id = s.id
                JOIN users u ON b.student_id = u.id
                WHERE s.tutor_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tutorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
    
    /**
     * Calculate amount for a booking
     * 
     * @param array $booking Booking data
     * @return float Total amount to pay
     */
    public function calculateAmount($booking) {
        // Get tutor hourly rate
        $hourlyRate = $booking['hourly_rate'];
        
        // Calculate session duration in hours
        $startTime = new DateTime($booking['start_time']);
        $endTime = new DateTime($booking['end_time']);
        $interval = $startTime->diff($endTime);
        $hours = $interval->h + ($interval->i / 60);
        
        // Calculate total amount
        $amount = $hourlyRate * $hours;
        
        return round($amount, 2);
    }
}
?>